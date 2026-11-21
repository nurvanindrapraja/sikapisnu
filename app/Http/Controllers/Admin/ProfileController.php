<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pac;
use App\Services\LetterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('admin.profile.index', compact('user'));
    }

    public function updateProfile(Request $request, LetterService $letterService)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)->withoutTrashed()],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)->withoutTrashed()],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'stamp' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        if ($request->hasFile('signature')) {
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }
            $validated['signature_path'] = $request->file('signature')->store('signatures', 'public');
            if ($user->member) {
                $user->member->update(['signature_path' => $validated['signature_path']]);
            }
        }
        unset($validated['signature']);

        if ($request->hasFile('stamp')) {
            if (! $user->canUploadStamp()) {
                abort(403, 'Hanya Sekretaris PC ISNU (untuk stempel PC) atau Sekretaris PAC ISNU (untuk stempel PAC wilayahnya) yang berhak mengunggah stempel resmi.');
            }

            if ($user->isOfficerPac()) {
                $pacId = $user->getLetterPacId();
                $pac = $pacId ? Pac::find($pacId) : null;
                if (! $pac) {
                    abort(403, 'Akun Sekretaris PAC Anda belum terhubung dengan wilayah PAC tertentu.');
                }

                $validated['stamp_path'] = $letterService->saveOfficialPacStamp($pac, $request->file('stamp'), $user);
            } else {
                $validated['stamp_path'] = $letterService->saveOfficialPcStamp($request->file('stamp'), $user);
            }
        }
        unset($validated['stamp']);

        $user->update($validated);

        AuditLog::record($user->id, 'Perbarui Profil Admin', 'Memperbarui informasi profil admin dan berkas TTD/stempel.');

        return back()->with('success', 'Informasi profil Admin berhasil diperbarui!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::record($user->id, 'Ubah Password Admin', 'Berhasil mengubah password akun admin.');

        return back()->with('success', 'Password akun Admin Anda berhasil diperbarui!');
    }
}
