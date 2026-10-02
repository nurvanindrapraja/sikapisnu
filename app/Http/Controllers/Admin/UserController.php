<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('member.pac');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('admin.users.partials.user_list', compact('users'));
        }

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'role' => 'required|in:super_admin,admin_kota,admin_pac,member',
            'password' => 'required|string|min:8',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;

        $user = User::create($validated);

        AuditLog::record(auth()->id(), 'Tambah User', "Menambahkan akun user baru: {$user->name} ({$user->email}) - Role: {$user->role}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akun user '{$user->name}' ({$user->email}) berhasil ditambahkan.",
            ]);
        }

        return back()->with('success', "Akun user '{$user->name}' ({$user->email}) berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'phone' => 'nullable|string|max:30|unique:users,phone,'.$id,
            'role' => 'required|in:super_admin,admin_kota,admin_pac,member',
            'password' => 'nullable|string|min:8',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->is_active : false;

        $user->update($validated);

        AuditLog::record(auth()->id(), 'Edit User', "Memperbarui akun user: {$user->name} ({$user->email})");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akun user '{$user->name}' berhasil diperbarui.",
            ]);
        }

        return back()->with('success', "Akun user '{$user->name}' berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang digunakan.',
            ], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLog::record(auth()->id(), 'Toggle Status User', "Akun user {$user->email} {$statusText}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Status akun '{$user->name}' berhasil {$statusText}.",
                'is_active' => $user->is_active,
            ]);
        }

        return back()->with('success', "Status akun '{$user->name}' berhasil {$statusText}.");
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $name = $user->name;
        $user->delete();

        AuditLog::record(auth()->id(), 'Hapus User', "Menghapus akun user: {$name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akun user '{$name}' berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Akun user '{$name}' berhasil dihapus.");
    }
}
