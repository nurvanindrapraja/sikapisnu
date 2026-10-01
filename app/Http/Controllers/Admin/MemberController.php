<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Section;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['user', 'mwc', 'pac', 'activePosition']);

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
            'activeCard',
            'statusHistories.changer',
        ])->findOrFail($id);

        $mwcs = Mwc::orderBy('name')->get();
        $pacs = Pac::orderBy('name')->get();
        $sections = Section::with('pac')->orderBy('level')->orderBy('name')->get();

        return view('admin.anggota.show', compact('member', 'mwcs', 'pacs', 'sections'));
    }

    public function edit($id)
    {
        $member = Member::with(['user', 'mwc', 'pac', 'educations', 'organizations', 'employments', 'nuTrainings', 'certifications'])->findOrFail($id);
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
        $name = $member->full_name;

        if ($member->user_id) {
            User::withTrashed()->find($member->user_id)?->forceDelete();
        }
        $member->forceDelete();

        return redirect()->route('admin.sampah.index')->with('success', 'Data anggota '.$name.' telah dihapus secara permanen.');
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

        $currentUser = auth()->user();
        $downloadTimestamp = now()->translatedFormat('d F Y, H:i:s').' WIB';
        $downloadedBy = 'Admin: '.$currentUser->name.' ('.$currentUser->email.')';

        $pdf = Pdf::loadView('pdf.cv_member', compact('member', 'downloadTimestamp', 'downloadedBy'))
            ->setPaper('a4', 'portrait');

        $filename = 'CV_ISNU_'.Str::slug($member->full_name).'.pdf';

        return $pdf->download($filename);
    }
}
