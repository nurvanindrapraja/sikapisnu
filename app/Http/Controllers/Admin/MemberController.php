<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberCertification;
use App\Models\MemberEducation;
use App\Models\MemberNuTraining;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['user', 'mwc', 'pac', 'activePosition']);

        if (auth()->user()->isAdminPac()) {
            $pacId = auth()->user()->getManagedPacId();
            $kecamatan = auth()->user()->member?->kecamatan;
            $query->where(function ($q) use ($pacId, $kecamatan) {
                if ($pacId) {
                    $q->where('pac_id', $pacId);
                }
                if ($kecamatan) {
                    $q->orWhere('kecamatan', 'like', "%{$kecamatan}%");
                }
            });
        }

        // Search Multi-Parameter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('membership_status', $request->status);
        }

        // Filter Lokasi Berjenjang
        if ($request->filled('province')) {
            $query->where('province', $request->province);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('kecamatan')) {
            $query->where('kecamatan', $request->kecamatan);
        }
        if ($request->filled('kelurahan')) {
            $query->where('kelurahan', $request->kelurahan);
        }

        // Filter Pendidikan
        if ($request->filled('education_level')) {
            $query->whereHas('educations', function ($q) use ($request) {
                $q->where('level', $request->education_level);
            });
        }

        // Filter Kaderisasi NU
        if ($request->filled('nu_training')) {
            $val = $request->nu_training;
            $query->whereHas('nuTrainings', function ($q) use ($val) {
                $q->where('training_type', 'like', "%{$val}%")
                    ->orWhere('organizer', 'like', "%{$val}%");
            });
        }

        // Filter Riwayat Organisasi
        if ($request->filled('organization')) {
            $val = $request->organization;
            $query->whereHas('organizations', function ($q) use ($val) {
                $q->where('organization_name', 'like', "%{$val}%")
                    ->orWhere('position', 'like', "%{$val}%");
            });
        }

        // Filter Riwayat Pekerjaan
        if ($request->filled('employment')) {
            $val = $request->employment;
            $query->whereHas('employments', function ($q) use ($val) {
                $q->where('company_name', 'like', "%{$val}%")
                    ->orWhere('position', 'like', "%{$val}%");
            });
        }

        // Filter Sertifikasi Keahlian
        if ($request->filled('certification')) {
            $val = $request->certification;
            $query->whereHas('certifications', function ($q) use ($val) {
                $q->where('certification_name', 'like', "%{$val}%")
                    ->orWhere('field', 'like', "%{$val}%");
            });
        }

        $members = $query->latest()->paginate(15)->withQueryString();
        $mwcs = Cache::remember('master_mwc_pacs', 3600, fn () => Mwc::with('pacs')->orderBy('name')->get());

        if ($request->ajax()) {
            return view('admin.anggota.partials.member_list', compact('members'));
        }

        return view('admin.anggota.index', compact('members', 'mwcs'));
    }

    public function show($id)
    {
        $member = Member::with([
            'user',
            'mwc',
            'pac',
            'educations',
            'organizations',
            'employments',
            'nuTrainings',
            'certifications',
            'positions.mwc',
            'positions.section',
            'activePosition.section',
            'workgroups',
            'activeCard',
            'statusHistories.changer',
        ])->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $mwcs = Mwc::orderBy('name')->get();
        $pacs = Pac::orderBy('name')->get();
        $sections = Section::with('pac')->orderBy('level')->orderBy('name')->get();

        return view('admin.anggota.show', compact('member', 'mwcs', 'pacs', 'sections'));
    }

    public function edit($id)
    {
        $member = Member::with(['user', 'mwc', 'pac', 'educations', 'organizations', 'employments', 'nuTrainings', 'certifications'])->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }
        $mwcs = Mwc::orderBy('name')->get();

        return view('admin.anggota.edit', compact('member', 'mwcs'));
    }

    public function addEducation($id, Request $request)
    {
        $member = Member::findOrFail($id);
        $validated = $request->validate([
            'level' => 'required|string',
            'institution_name' => 'required|string',
            'major' => 'nullable|string',
            'degree' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
        ]);

        $member->educations()->create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil ditambahkan.',
            ]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function updateEducation($id, $eduId, Request $request)
    {
        $member = Member::findOrFail($id);
        $edu = $member->educations()->where('id', $eduId)->firstOrFail();

        $validated = $request->validate([
            'level' => 'required|string',
            'institution_name' => 'required|string',
            'major' => 'nullable|string',
            'degree' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
        ]);

        $edu->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil diperbarui.');
    }

    public function deleteEducation($id, $eduId)
    {
        $member = Member::findOrFail($id);
        $member->educations()->where('id', $eduId)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }

    public function addOrganization($id, Request $request)
    {
        $member = Member::findOrFail($id);
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:100',
        ]);

        $member->organizations()->create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat organisasi berhasil ditambahkan.',
            ]);
        }

        return back()->with('success', 'Riwayat organisasi berhasil ditambahkan.');
    }

    public function updateOrganization($id, $orgId, Request $request)
    {
        $member = Member::findOrFail($id);
        $org = $member->organizations()->where('id', $orgId)->firstOrFail();

        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'period' => 'nullable|string|max:100',
        ]);

        $org->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat organisasi berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Riwayat organisasi berhasil diperbarui.');
    }

    public function deleteOrganization($id, $orgId)
    {
        $member = Member::findOrFail($id);
        $member->organizations()->where('id', $orgId)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat organisasi berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat organisasi berhasil dihapus.');
    }

    public function addEmployment($id, Request $request)
    {
        $member = Member::findOrFail($id);
        $validated = $request->validate([
            'company_name' => 'required|string',
            'position' => 'required|string',
            'employment_status' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
        ]);

        $member->employments()->create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan berhasil ditambahkan.',
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan berhasil ditambahkan.');
    }

    public function updateEmployment($id, $empId, Request $request)
    {
        $member = Member::findOrFail($id);
        $emp = $member->employments()->where('id', $empId)->firstOrFail();

        $validated = $request->validate([
            'company_name' => 'required|string',
            'position' => 'required|string',
            'employment_status' => 'nullable|string',
            'start_year' => 'nullable|integer',
            'end_year' => 'nullable|integer',
        ]);

        $emp->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan berhasil diperbarui.');
    }

    public function deleteEmployment($id, $empId)
    {
        $member = Member::findOrFail($id);
        $member->employments()->where('id', $empId)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan berhasil dihapus.');
    }

    public function addNuTraining($id, Request $request)
    {
        $member = Member::findOrFail($id);
        $validated = $request->validate([
            'training_type' => 'required|string',
            'organizer' => 'nullable|string',
            'certificate_number' => 'nullable|string',
            'year' => 'nullable|integer',
        ]);

        $member->nuTrainings()->create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kaderisasi NU berhasil ditambahkan.',
            ]);
        }

        return back()->with('success', 'Riwayat kaderisasi NU berhasil ditambahkan.');
    }

    public function updateNuTraining($id, $nuId, Request $request)
    {
        $member = Member::findOrFail($id);
        $nu = $member->nuTrainings()->where('id', $nuId)->firstOrFail();

        $validated = $request->validate([
            'training_type' => 'required|string',
            'organizer' => 'nullable|string',
            'certificate_number' => 'nullable|string',
            'year' => 'nullable|integer',
        ]);

        $nu->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kaderisasi NU berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Riwayat kaderisasi NU berhasil diperbarui.');
    }

    public function deleteNuTraining($id, $nuId)
    {
        $member = Member::findOrFail($id);
        $member->nuTrainings()->where('id', $nuId)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kaderisasi NU berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat kaderisasi NU berhasil dihapus.');
    }

    public function addCertification($id, Request $request)
    {
        $member = Member::findOrFail($id);
        $validated = $request->validate([
            'certification_name' => 'required|string',
            'field' => 'nullable|string',
            'certificate_number' => 'nullable|string',
            'issue_year' => 'nullable|integer',
        ]);

        $member->certifications()->create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sertifikasi keahlian berhasil ditambahkan.',
            ]);
        }

        return back()->with('success', 'Sertifikasi keahlian berhasil ditambahkan.');
    }

    public function updateCertification($id, $certId, Request $request)
    {
        $member = Member::findOrFail($id);
        $cert = $member->certifications()->where('id', $certId)->firstOrFail();

        $validated = $request->validate([
            'certification_name' => 'required|string',
            'field' => 'nullable|string',
            'certificate_number' => 'nullable|string',
            'issue_year' => 'nullable|integer',
        ]);

        $cert->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sertifikasi keahlian berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Sertifikasi keahlian berhasil diperbarui.');
    }

    public function deleteCertification($id, $certId)
    {
        $member = Member::findOrFail($id);
        $member->certifications()->where('id', $certId)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sertifikasi keahlian berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat sertifikasi keahlian berhasil dihapus.');
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'nik' => ['nullable', 'string', 'max:20', Rule::unique('members', 'nik')->ignore($member->id)->withoutTrashed()],
            'email' => ['required', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member->id)->withoutTrashed()],
            'phone' => 'required|string|max:20',
            'occupation' => 'required|string|max:255',
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'kecamatan' => 'required|string|max:100',
            'kelurahan' => 'required|string|max:100',
            'mwc_id' => 'nullable|exists:mwc,id',
            'membership_status' => 'required|in:calon,menunggu_verifikasi,terverifikasi,perbaikan,ditolak,pengurus',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $validated['photo'] = $path;
        }

        $member->update($validated);

        if ($member->user && $member->user->email !== $validated['email']) {
            $member->user->update(['email' => $validated['email']]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data anggota '.$member->full_name.' berhasil diperbarui.',
            ]);
        }

        return redirect()->route('admin.anggota.index')->with('success', 'Data anggota '.$member->full_name.' berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $name = $member->full_name;

        if ($member->user) {
            $member->user->delete();
        }
        $member->delete();

        return redirect()->route('admin.anggota.index')->with('success', 'Data anggota '.$name.' telah dipindahkan ke Data Sampah.');
    }

    public function trash(Request $request)
    {
        $query = Member::onlyTrashed()->with(['user', 'mwc']);

        if (auth()->user()->isAdminPac()) {
            $pacId = auth()->user()->getManagedPacId();
            $kecamatan = auth()->user()->member?->kecamatan;
            $query->where(function ($q) use ($pacId, $kecamatan) {
                if ($pacId) {
                    $q->where('pac_id', $pacId);
                }
                if ($kecamatan) {
                    $q->orWhere('kecamatan', 'like', "%{$kecamatan}%");
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $trashedMembers = $query->latest('deleted_at')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('admin.sampah.partials.trashed_list', compact('trashedMembers'));
        }

        return view('admin.sampah.index', compact('trashedMembers'));
    }

    public function restore($id)
    {
        $member = Member::onlyTrashed()->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $name = $member->full_name;

        // Requirement 5.7: Check email duplicate against active members
        if ($member->email) {
            $existingActiveMember = Member::where('email', $member->email)->whereNull('deleted_at')->exists();
            $existingActiveUser = User::where('email', $member->email)->whereNull('deleted_at')->where('id', '!=', $member->user_id)->exists();

            if ($existingActiveMember || $existingActiveUser) {
                return redirect()->route('admin.sampah.index')->with('error', "Data anggota {$name} tidak dapat dipulihkan karena email '{$member->email}' sudah digunakan oleh anggota/pengguna aktif.");
            }
        }

        $member->restore();

        if ($member->user_id) {
            User::withTrashed()->find($member->user_id)?->restore();
        }

        return redirect()->route('admin.sampah.index')->with('success', "Data anggota {$name} berhasil dikembalikan ke Data Aktif.");
    }

    public function forceDelete($id)
    {
        $member = Member::onlyTrashed()->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $name = $member->full_name;

        if ($member->user_id) {
            User::withTrashed()->find($member->user_id)?->forceDelete();
        }
        $member->forceDelete();

        return redirect()->route('admin.sampah.index')->with('success', 'Data anggota '.$name.' telah dihapus secara permanen.');
    }

    public function rekapPdf(Request $request)
    {
        $currentUser = auth()->user();
        $isPacAdmin = $currentUser->isAdminPac();
        $pacId = $isPacAdmin ? $currentUser->getManagedPacId() : null;
        $kecamatan = $isPacAdmin ? $currentUser->member?->kecamatan : null;

        $applyScope = function ($query) use ($isPacAdmin, $pacId, $kecamatan) {
            if ($isPacAdmin) {
                $query->where(function ($q) use ($pacId, $kecamatan) {
                    if ($pacId) {
                        $q->where('pac_id', $pacId);
                    }
                    if ($kecamatan) {
                        $q->orWhere('kecamatan', 'like', "%{$kecamatan}%");
                    }
                });
            }
        };

        // 1. Ringkasan Statistik Status Keanggotaan
        $stats = [
            'total' => Member::when($isPacAdmin, $applyScope)->count(),
            'terverifikasi' => Member::when($isPacAdmin, $applyScope)->where('membership_status', 'terverifikasi')->count(),
            'pengurus' => Member::when($isPacAdmin, $applyScope)->where('membership_status', 'pengurus')->count(),
            'pending' => Member::when($isPacAdmin, $applyScope)->where('membership_status', 'menunggu_verifikasi')->count(),
            'perbaikan' => Member::when($isPacAdmin, $applyScope)->where('membership_status', 'perbaikan')->count(),
            'ditolak' => Member::when($isPacAdmin, $applyScope)->where('membership_status', 'ditolak')->count(),
            'male' => Member::when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus'])->where('gender', 'L')->count(),
            'female' => Member::when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus'])->where('gender', 'P')->count(),
        ];

        // 2. Sebaran per Wilayah Domisili (Kota/Kab & Kecamatan)
        $districtStats = Member::when($isPacAdmin, $applyScope)
            ->whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->select('city', 'kecamatan', DB::raw('count(*) as total'))
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->groupBy('city', 'kecamatan')
            ->orderByDesc('total')
            ->get();

        // 3. Jenjang Pendidikan Terakhir
        $educationStats = MemberEducation::whereHas('member', function ($q) use ($applyScope, $isPacAdmin) {
            $q->when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        })
            ->select('level', DB::raw('count(distinct member_id) as total'))
            ->groupBy('level')
            ->orderByDesc('total')
            ->get();

        // 4. Persebaran Pekerjaan / Profesi
        $occupationStats = Member::when($isPacAdmin, $applyScope)
            ->whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->select('occupation', DB::raw('count(*) as total'))
            ->whereNotNull('occupation')
            ->where('occupation', '!=', '')
            ->groupBy('occupation')
            ->orderByDesc('total')
            ->get();

        // 5. Kaderisasi NU
        $nuTrainingStats = MemberNuTraining::whereHas('member', function ($q) use ($applyScope, $isPacAdmin) {
            $q->when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        })
            ->select('training_type', DB::raw('count(distinct member_id) as total'))
            ->whereNotNull('training_type')
            ->where('training_type', '!=', '')
            ->groupBy('training_type')
            ->orderByDesc('total')
            ->get();

        // 6. Rekap Bidang Keahlian & Sertifikasi
        $skillsStats = MemberCertification::whereHas('member', function ($q) use ($applyScope, $isPacAdmin) {
            $q->when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        })
            ->select('field', DB::raw('count(distinct member_id) as total_kader'), DB::raw('count(*) as total_sertifikat'))
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->groupBy('field')
            ->orderByDesc('total_kader')
            ->get();

        // Contoh sertifikasi kompetensi per bidang
        $fieldCertExamples = MemberCertification::whereHas('member', function ($q) use ($applyScope, $isPacAdmin) {
            $q->when($isPacAdmin, $applyScope)->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        })
            ->select('field', 'certification_name', 'issuing_organization')
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->get()
            ->groupBy('field');

        // 7. Daftar Pengurus ISNU
        $officers = Position::with(['member.educations', 'member.pac', 'section', 'pac'])
            ->where('is_active', true)
            ->whereHas('member', function ($q) use ($applyScope, $isPacAdmin) {
                $q->when($isPacAdmin, $applyScope);
            })
            ->get()
            ->sortBy(function ($p) {
                $order = [
                    'Ketua' => 1,
                    'Wakil Ketua' => 2,
                    'Sekretaris' => 3,
                    'Wakil Sekretaris' => 4,
                    'Bendahara' => 5,
                    'Wakil Bendahara' => 6,
                    'Koordinator Seksi' => 7,
                    'Anggota Seksi' => 8,
                    'Anggota' => 9,
                ];

                return $order[$p->position_title] ?? 99;
            })
            ->values();

        // 8. Daftar Anggota Terverifikasi
        $members = Member::when($isPacAdmin, $applyScope)
            ->whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->with(['pac', 'educations', 'certifications', 'activePosition.section'])
            ->orderBy('full_name')
            ->get();

        $logoPath = public_path('images/logo_isnu.png');
        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

        $downloadTimestamp = now()->translatedFormat('d F Y, H:i').' WIB';
        $downloadedBy = $currentUser->name.' ('.$currentUser->email.')';

        $pdf = Pdf::loadView('pdf.rekap_potensi', compact(
            'stats',
            'districtStats',
            'educationStats',
            'occupationStats',
            'nuTrainingStats',
            'skillsStats',
            'fieldCertExamples',
            'officers',
            'members',
            'isPacAdmin',
            'logoSrc',
            'downloadTimestamp',
            'downloadedBy'
        ))->setPaper('a4', 'portrait');

        $filename = 'Rekap_Potensi_Kader_ISNU_'.now()->format('Ymd_His').'.pdf';

        if ($request->boolean('stream') || $request->get('stream') == '1') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    public function downloadCv($id)
    {
        $member = Member::withTrashed()->with([
            'mwc',
            'pac',
            'activePosition',
            'educations',
            'organizations',
            'employments',
            'nuTrainings',
            'certifications',
        ])->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        $currentUser = auth()->user();
        $downloadTimestamp = now()->translatedFormat('d F Y, H:i:s').' WIB';
        $downloadedBy = 'Admin: '.$currentUser->name.' ('.$currentUser->email.')';

        $pdf = Pdf::loadView('pdf.cv_member', compact('member', 'downloadTimestamp', 'downloadedBy'))
            ->setPaper('a4', 'portrait');

        $filename = 'CV_ISNU_'.Str::slug($member->full_name).'.pdf';

        return $pdf->download($filename);
    }

    public function downloadCard($id, Request $request)
    {
        $member = Member::withTrashed()->with(['activeCard', 'activePosition', 'mwc', 'pac'])->findOrFail($id);

        if (! auth()->user()->canManageMember($member)) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengelola anggota di luar wilayah PAC Anda.');
        }

        if (! in_array($member->membership_status, ['terverifikasi', 'pengurus']) || ! $member->activeCard) {
            return back()->with('error', 'Kartu Digital belum tersedia karena status anggota belum terverifikasi.');
        }

        $card = $member->activeCard;
        $isOfficer = ($card && $card->card_type === 'OFFICER') || $member->membership_status === 'pengurus';
        $activePosition = $member->activePosition;

        $logoPath = public_path('images/logo_isnu.png');
        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';

        $suroboyoPath = public_path('images/suroboyo_icon.png');
        $suroboyoSrc = file_exists($suroboyoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($suroboyoPath)) : '';

        $photoSrc = '';
        if ($member->photo && file_exists(storage_path('app/public/'.$member->photo))) {
            $path = storage_path('app/public/'.$member->photo);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $photoSrc = 'data:image/'.$type.';base64,'.base64_encode(file_get_contents($path));
        } elseif ($member->photo_url) {
            try {
                $photoSrc = 'data:image/png;base64,'.base64_encode(file_get_contents($member->photo_url));
            } catch (\Throwable $e) {
                $photoSrc = '';
            }
        }

        $qrToken = $card->qr_token ?? 'preview';
        $verifyUrl = route('verify.card', ['qr_token' => $qrToken]);
        try {
            $qrSvg = QrCode::size(120)->format('svg')->margin(1)->generate($verifyUrl);
            $qrBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrBase64 = '';
        }

        $pdf = Pdf::loadView('pdf.card_digital', compact(
            'member',
            'card',
            'isOfficer',
            'activePosition',
            'logoSrc',
            'suroboyoSrc',
            'photoSrc',
            'qrBase64'
        ))->setPaper([0, 0, 480, 303]);

        $filename = 'Kartu_Digital_ISNU_'.Str::slug($member->full_name).'.pdf';

        return $pdf->download($filename);
    }
}
