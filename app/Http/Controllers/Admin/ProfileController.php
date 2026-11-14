<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

    public function updateProfile(Request $request)
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
            if ($user->stamp_path && Storage::disk('public')->exists($user->stamp_path)) {
                Storage::disk('public')->delete($user->stamp_path);
            }
            $stampPath = $request->file('stamp')->store('stamps', 'public');
            $validated['stamp_path'] = $stampPath;

            // Simpan salinan sebagai stempel resmi aktif organisasi untuk seluruh persuratan
            Storage::disk('public')->put('stamps/official_stamp.png', (string) file_get_contents($request->file('stamp')->getRealPath()));
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
