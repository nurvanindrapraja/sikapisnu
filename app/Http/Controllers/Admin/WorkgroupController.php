<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Pac;
use App\Models\Workgroup;
use App\Models\WorkgroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkgroupController extends Controller
{
    /**
     * Menampilkan daftar Master Kelompok Kerja.
     */
    public function index(Request $request): View
    {
        $query = Workgroup::with('pac')->withCount('members');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('pac_id')) {
            $query->where('pac_id', $request->pac_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $workgroups = $query->orderBy('level')->orderBy('name')->paginate(15)->withQueryString();
        $pacs = Pac::orderBy('name')->get();

        $metrics = [
            'total' => Workgroup::count(),
            'pc' => Workgroup::where('level', 'PC ISNU')->count(),
            'pac' => Workgroup::where('level', 'PAC ISNU')->count(),
            'members' => WorkgroupMember::distinct('member_id')->count('member_id'),
        ];

        if ($request->ajax()) {
            return view('admin.workgroups.partials.workgroup_list', compact('workgroups', 'pacs'));
        }

        return view('admin.workgroups.index', compact('workgroups', 'pacs', 'metrics'));
    }

    /**
     * Menyimpan Kelompok Kerja baru.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['level'] === 'PC ISNU') {
            $validated['pac_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $workgroup = Workgroup::create($validated);
        AuditLog::record(auth()->id(), 'Master Kelompok Kerja', "Menambahkan Kelompok Kerja Baru [{$workgroup->level}]: {$workgroup->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kelompok Kerja '{$workgroup->name}' berhasil ditambahkan.",
            ]);
        }

        return back()->with('success', "Kelompok Kerja '{$workgroup->name}' berhasil ditambahkan.");
    }

    /**
     * Menampilkan detail Kelompok Kerja dan pengelolaan anggotanya.
     */
    public function show(int $id): View
    {
        $workgroup = Workgroup::with([
            'pac',
            'members' => function ($q) {
                $q->with(['activePosition.section', 'activePosition.pac', 'pac']);
            },
        ])->findOrFail($id);

        $pacs = Pac::orderBy('name')->get();

        // Mengambil kandidat pengurus yang SAH masuk ke Pokja:
        // Syarat mutlak: Status pengurus, memiliki jabatan aktif, dan BERADA DI SEKSI (bukan di luar seksi).
        $eligibleQuery = Member::where('membership_status', 'pengurus')
            ->whereHas('activePosition', function ($q) {
                $q->whereNotNull('section_id');
            })
            ->whereDoesntHave('workgroups', function ($q) use ($id) {
                $q->where('workgroups.id', $id);
            })
            ->with(['activePosition.section', 'activePosition.pac', 'pac']);

        // Jika user admin PAC, batasi sesuai wilayah PAC
        if (auth()->user()->isAdminPac()) {
            $pacId = auth()->user()->getManagedPacId();
            if ($pacId) {
                $eligibleQuery->where(function ($q) use ($pacId) {
                    $q->where('pac_id', $pacId)->orWhereHas('activePosition', fn ($p) => $p->where('pac_id', $pacId));
                });
            }
        }

        $eligibleMembers = $eligibleQuery->orderBy('full_name')->get();

        return view('admin.workgroups.show', compact('workgroup', 'eligibleMembers', 'pacs'));
    }

    /**
     * Memperbarui data Kelompok Kerja.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $workgroup = Workgroup::findOrFail($id);

        $validated = $request->validate([
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['level'] === 'PC ISNU') {
            $validated['pac_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $workgroup->update($validated);
        AuditLog::record(auth()->id(), 'Master Kelompok Kerja', "Memperbarui Kelompok Kerja [{$workgroup->level}]: {$workgroup->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kelompok Kerja '{$workgroup->name}' berhasil diperbarui.",
            ]);
        }

        return back()->with('success', "Kelompok Kerja '{$workgroup->name}' berhasil diperbarui.");
    }

    /**
     * Menghapus Kelompok Kerja.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $workgroup = Workgroup::findOrFail($id);
        $name = $workgroup->name;
        $workgroup->delete();

        AuditLog::record(auth()->id(), 'Master Kelompok Kerja', "Menghapus Kelompok Kerja: {$name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kelompok Kerja '{$name}' berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Kelompok Kerja '{$name}' berhasil dihapus.");
    }

    /**
     * Menambahkan Pengurus Seksi ke dalam Kelompok Kerja.
     */
    public function addMember(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $workgroup = Workgroup::findOrFail($id);

        $request->validate([
            'member_id' => 'required|exists:members,id',
            'role' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $member = Member::with(['activePosition.section'])->findOrFail($request->member_id);

        // Validasi Aturan Bisnis: Pengurus HARUS berada di seksi, bukan di luar seksi.
        if (! $member->activePosition || ! $member->activePosition->section_id) {
            $msg = 'Gagal: Pengurus yang bersangkutan belum terdaftar dalam Seksi. Kelompok Kerja hanya dapat beranggotakan pengurus yang ada di seksi-seksi.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        // Cek apakah sudah terdaftar dalam Pokja ini
        if ($workgroup->members()->where('member_id', $member->id)->exists()) {
            $msg = "Pengurus {$member->full_name} sudah terdaftar dalam Kelompok Kerja ini.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $workgroup->members()->attach($member->id, [
            'role' => $request->role,
            'notes' => $request->notes,
        ]);

        $seksiName = $member->activePosition->section?->name ?? 'Seksi';
        AuditLog::record(
            auth()->id(),
            'Master Kelompok Kerja',
            "Menambahkan Pengurus {$member->full_name} (dari {$seksiName}) ke Kelompok Kerja {$workgroup->name} sebagai {$request->role}"
        );

        $successMsg = "Pengurus {$member->full_name} ({$seksiName}) berhasil ditambahkan ke {$workgroup->name} sebagai {$request->role}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Memperbarui peran / catatan anggota di dalam Kelompok Kerja.
     */
    public function updateMember(Request $request, int $id, int $member_id): JsonResponse|RedirectResponse
    {
        $workgroup = Workgroup::findOrFail($id);
        $member = Member::findOrFail($member_id);

        $request->validate([
            'role' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $workgroup->members()->updateExistingPivot($member_id, [
            'role' => $request->role,
            'notes' => $request->notes,
        ]);

        AuditLog::record(
            auth()->id(),
            'Master Kelompok Kerja',
            "Memperbarui peran {$member->full_name} di Kelompok Kerja {$workgroup->name} menjadi {$request->role}"
        );

        $successMsg = "Peran {$member->full_name} di Kelompok Kerja berhasil diperbarui.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Mengeluarkan pengurus dari Kelompok Kerja (posisi di Seksi tetap aktif).
     */
    public function removeMember(Request $request, int $id, int $member_id): JsonResponse|RedirectResponse
    {
        $workgroup = Workgroup::findOrFail($id);
        $member = Member::findOrFail($member_id);

        $workgroup->members()->detach($member_id);

        AuditLog::record(
            auth()->id(),
            'Master Kelompok Kerja',
            "Mengeluarkan Pengurus {$member->full_name} dari Kelompok Kerja {$workgroup->name}"
        );

        $successMsg = "Pengurus {$member->full_name} berhasil dikeluarkan dari {$workgroup->name}. Status di Seksi tetap aktif.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return back()->with('success', $successMsg);
    }
}
