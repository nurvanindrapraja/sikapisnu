<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Mwc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $member = auth()->user()->member()->with(['educations', 'organizations', 'employments', 'nuTrainings', 'certifications'])->firstOrFail();
        $mwcs = Mwc::with('pacs')->orderBy('name')->get();

        return view('member.profile_edit', compact('member', 'mwcs'));
    }

    public function update(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'birth_place' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'gender' => 'required|in:L,P',
            'address' => 'required|string',
            'kelurahan' => 'required|string',
            'kecamatan' => 'required|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'occupation' => 'required|string',
            'mwc_id' => 'nullable|exists:mwc,id',
            'pac_id' => 'nullable|exists:pac,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($member->photo && Storage::disk('public')->exists($member->photo)) {
                Storage::disk('public')->delete($member->photo);
            }
            $validated['photo'] = $request->file('photo')->store('member_photos', 'public');
        }

        // Jika status sebelumnya perbaikan, kembalikan ke menunggu_verifikasi saat di-submit ulang
        if ($member->membership_status === 'perbaikan') {
            $validated['membership_status'] = 'menunggu_verifikasi';
        }

        $member->update($validated);
        auth()->user()->update(['name' => $validated['full_name']]);

        AuditLog::record(auth()->id(), 'Perbarui Profil', 'Memperbarui biodata anggota.');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profil Anda berhasil diperbarui!',
            ]);
        }

        return redirect()->route('member.dashboard')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    public function addEducation(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

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

    public function updateEducation(Request $request, $id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $edu = $member->educations()->where('id', $id)->firstOrFail();

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

    public function deleteEducation($id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $member->educations()->where('id', $id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pendidikan dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat pendidikan dihapus.');
    }

    public function addOrganization(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

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

    public function updateOrganization(Request $request, $id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $org = $member->organizations()->where('id', $id)->firstOrFail();

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

    public function deleteOrganization($id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $member->organizations()->where('id', $id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat organisasi dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat organisasi dihapus.');
    }

    public function addEmployment(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

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

    public function updateEmployment(Request $request, $id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $emp = $member->employments()->where('id', $id)->firstOrFail();

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

    public function deleteEmployment($id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $member->employments()->where('id', $id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan dihapus.');
    }

    public function addNuTraining(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

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

    public function updateNuTraining(Request $request, $id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $nu = $member->nuTrainings()->where('id', $id)->firstOrFail();

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

    public function deleteNuTraining($id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $member->nuTrainings()->where('id', $id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kaderisasi NU dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat kaderisasi NU dihapus.');
    }

    public function addCertification(Request $request)
    {
        $member = auth()->user()->member()->firstOrFail();

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

    public function updateCertification(Request $request, $id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $cert = $member->certifications()->where('id', $id)->firstOrFail();

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

    public function deleteCertification($id)
    {
        $member = auth()->user()->member()->firstOrFail();
        $member->certifications()->where('id', $id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sertifikasi keahlian dihapus.',
            ]);
        }

        return back()->with('success', 'Sertifikasi keahlian dihapus.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini yang Anda masukkan salah.',
                ], 422);
            }

            return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::record($user->id, 'Ubah Password', 'Berhasil mengubah password akun member.');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Password akun Anda berhasil diperbarui!',
            ]);
        }

        return back()->with('success', 'Password akun Anda berhasil diperbarui!');
    }
}
