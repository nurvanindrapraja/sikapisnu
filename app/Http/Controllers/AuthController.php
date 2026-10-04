<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationConfirmationMail;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberCertification;
use App\Models\MemberEducation;
use App\Models\MemberEmployment;
use App\Models\MemberNuTraining;
use App\Models\Mwc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('member.dashboard');
        }

        if ($request->has('redirect')) {
            session()->put('url.intended', $request->query('redirect'));
        } elseif (url()->previous() && str_contains(url()->previous(), '/presensi/')) {
            session()->put('url.intended', url()->previous());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isMember()) {
                $member = $user->member;
                if ($member && $member->membership_status === 'ditolak') {
                    Auth::logout();

                    return back()->withErrors([
                        'email' => 'Pendaftaran Anda ditolak oleh Admin. Catatan: '.($member->rejection_note ?? 'Tidak ada catatan.'),
                    ])->onlyInput('email');
                }

                if (! $user->is_active || ($member && $member->membership_status === 'menunggu_verifikasi')) {
                    Auth::logout();

                    return back()->withErrors([
                        'email' => 'Akun Anda belum diverifikasi oleh Admin PC ISNU Kota Surabaya. Silakan tunggu hingga pendaftaran Anda disetujui oleh Admin.',
                    ])->onlyInput('email');
                }
            }

            AuditLog::record($user->id, 'Login', 'User berhasil login ke dalam sistem.');

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('member.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email/No HP atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::record(Auth::id(), 'Logout', 'User logout dari sistem.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('member.dashboard');
        }
        $mwcs = Cache::remember('master_mwc_pacs', 3600, fn () => Mwc::with('pacs')->orderBy('name')->get());

        return view('auth.register', compact('mwcs'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            // User Auth
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->withoutTrashed()],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Biodata Member
            'nik' => ['nullable', 'string', 'max:16', Rule::unique('members', 'nik')->withoutTrashed()],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'in:L,P'],
            'address' => ['required', 'string'],
            'kelurahan' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'occupation' => ['required', 'string', 'max:100'],
            'mwc_id' => ['nullable', 'exists:mwc,id'],
            'pac_id' => ['nullable', 'exists:pac,id'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],

            // Step 3: Pendidikan Terakhir (Wajib)
            'education_level' => ['required', 'string', 'max:50'],
            'education_institution' => ['required', 'string', 'max:255'],
            'education_major' => ['required', 'string', 'max:255'],
            'education_start_year' => ['nullable', 'numeric'],
            'education_end_year' => ['nullable', 'numeric'],

            'employment_company' => ['nullable', 'string'],
            'employment_position' => ['nullable', 'string'],
            'employment_status' => ['nullable', 'string'],
            'employment_start_year' => ['nullable', 'numeric'],

            'nu_training' => ['nullable', 'string'],
            'nu_organizer' => ['nullable', 'string'],
            'nu_year' => ['nullable', 'numeric'],

            'certification_name' => ['nullable', 'string'],
            'certification_field' => ['nullable', 'string'],
            'certification_year' => ['nullable', 'numeric'],
        ]);

        DB::beginTransaction();
        try {
            // Force delete soft-deleted Users and Members with matching email, phone, or NIK
            User::onlyTrashed()->where(function ($q) use ($validated) {
                $q->where('email', $validated['email'])
                    ->orWhere('phone', $validated['phone']);
            })->forceDelete();

            Member::onlyTrashed()->where(function ($q) use ($validated) {
                $q->where('email', $validated['email'])
                    ->orWhere('phone', $validated['phone']);
                if (! empty($validated['nik'])) {
                    $q->orWhere('nik', $validated['nik']);
                }
            })->forceDelete();

            $activationToken = Str::random(40);

            // 1. Create User (inactive until email verification)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                'is_active' => false,
                'activation_token' => $activationToken,
            ]);

            // Handle Photo upload if provided
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $photoPath = $file->store('member_photos', 'public');
                $fullPath = storage_path('app/public/'.$photoPath);
                if (file_exists($fullPath) && filesize($fullPath) > 400000) {
                    self::compressImageGD($fullPath);
                }
            }

            // 2. Create Member
            $member = Member::create([
                'user_id' => $user->id,
                'nik' => $validated['nik'] ?? null,
                'full_name' => $validated['name'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'kelurahan' => $validated['kelurahan'],
                'kecamatan' => $validated['kecamatan'],
                'city' => $validated['city'] ?? 'KOTA SURABAYA',
                'province' => $validated['province'] ?? 'JAWA TIMUR',
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'occupation' => $validated['occupation'],
                'photo' => $photoPath,
                'membership_status' => 'menunggu_verifikasi',
                'mwc_id' => $validated['mwc_id'] ?? null,
                'pac_id' => $validated['pac_id'] ?? null,
            ]);

            // 3. Save Optional Data
            if (! empty($validated['education_level']) || ! empty($validated['education_institution'])) {
                MemberEducation::create([
                    'member_id' => $member->id,
                    'level' => $validated['education_level'] ?? 'S1',
                    'institution_name' => $validated['education_institution'] ?? '-',
                    'major' => $validated['education_major'] ?? null,
                    'start_year' => $validated['education_start_year'] ?? null,
                    'end_year' => $validated['education_end_year'] ?? null,
                ]);
            }

            if (! empty($validated['employment_company']) || ! empty($validated['employment_position'])) {
                MemberEmployment::create([
                    'member_id' => $member->id,
                    'company_name' => $validated['employment_company'] ?? '-',
                    'position' => $validated['employment_position'] ?? '-',
                    'employment_status' => $validated['employment_status'] ?? null,
                    'start_year' => $validated['employment_start_year'] ?? null,
                ]);
            }

            if (! empty($validated['nu_training'])) {
                MemberNuTraining::create([
                    'member_id' => $member->id,
                    'training_type' => $validated['nu_training'],
                    'organizer' => $validated['nu_organizer'] ?? null,
                    'year' => $validated['nu_year'] ?? null,
                ]);
            }

            if (! empty($validated['certification_name'])) {
                MemberCertification::create([
                    'member_id' => $member->id,
                    'certification_name' => $validated['certification_name'],
                    'field' => $validated['certification_field'] ?? null,
                    'issue_year' => $validated['certification_year'] ?? null,
                ]);
            }

            AuditLog::record($user->id, 'Registrasi Anggota', 'Calon anggota mendaftar ke sistem.');

            DB::commit();

            Cache::forget('admin_dashboard_stats');

            // Send Notification Email to user (informational)
            try {
                Mail::to($user->email)->send(new RegistrationConfirmationMail($user));
            } catch (\Exception $mailEx) {
                Log::warning('Email pemberitahuan pendaftaran gagal terkirim: '.$mailEx->getMessage());
            }

            $msg = 'Pendaftaran Anda berhasil! Data pendaftaran Anda telah diterima dan saat ini sedang dalam proses verifikasi oleh Admin PC ISNU Kota Surabaya. Anda akan dapat masuk (login) setelah pendaftaran Anda disetujui oleh Admin.';

            return redirect()->route('login')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memproses pendaftaran: '.$e->getMessage());
        }
    }

    public function activateAccount($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Tautan aktivasi tidak ditemukan atau akun Anda sudah aktif.');
        }

        $user->is_active = true;
        $user->email_verified_at = now();
        $user->activation_token = null;
        $user->save();

        AuditLog::record($user->id, 'Aktivasi Email', 'Pengguna berhasil melakukan aktivasi email.');

        Auth::login($user);

        return redirect()->route('member.dashboard')->with('success', 'Selamat! Email Anda berhasil diverifikasi dan akun Anda telah aktif. Pendaftaran Anda saat ini sedang dalam proses verifikasi oleh Admin PC ISNU Kota Surabaya. Kartu digital akan diterbitkan setelah pendaftaran disetujui oleh Admin.');
    }

    protected static function compressImageGD(string $filePath): void
    {
        try {
            $info = getimagesize($filePath);
            if (! $info) {
                return;
            }

            $mime = $info['mime'];
            $image = null;
            if ($mime === 'image/jpeg') {
                $image = imagecreatefromjpeg($filePath);
            } elseif ($mime === 'image/png') {
                $image = imagecreatefrompng($filePath);
            } elseif ($mime === 'image/webp') {
                $image = imagecreatefromwebp($filePath);
            }

            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                $maxDim = 1200;

                if ($width > $maxDim || $height > $maxDim) {
                    if ($width > $height) {
                        $newWidth = $maxDim;
                        $newHeight = (int) ($height * ($maxDim / $width));
                    } else {
                        $newHeight = $maxDim;
                        $newWidth = (int) ($width * ($maxDim / $height));
                    }

                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    if ($mime === 'image/png') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                    }

                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                imagejpeg($image, $filePath, 80);
                imagedestroy($image);
            }
        } catch (\Throwable $e) {
            Log::warning('GD image compression failed: '.$e->getMessage());
        }
    }

    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        if (empty($email)) {
            return response()->json(['valid' => false, 'message' => 'Email tidak boleh kosong.']);
        }

        $exists = User::where('email', $email)->exists();
        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'Email "'.$email.'" sudah terdaftar dalam sistem SIKAP ISNU. Silakan gunakan email lain atau login.',
            ]);
        }

        return response()->json(['valid' => true, 'message' => 'Email dapat digunakan.']);
    }
}
