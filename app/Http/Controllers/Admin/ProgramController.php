<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Pac;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Models\Workgroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProgramController extends Controller
{
    protected function checkAccess(): void
    {
        if (auth()->user()?->isPengurusAnggotaSeksi()) {
            abort(403, 'Anggota Seksi tidak memiliki hak akses ke Master Program Kerja.');
        }
    }

    /**
     * Mengambil daftar Seksi dan Pokja yang boleh dipilih user pada form Tambah/Edit Program Kerja:
     * - Admin Sistem: semua Seksi & Pokja.
     * - Pengurus PC: hanya Seksi PC ISNU + Pokja PC ISNU (sesuai lingkup jabatan).
     * - Pengurus PAC: hanya Seksi PAC-nya + Pokja PAC-nya (sesuai lingkup jabatan).
     *
     * @return array{0: Collection<int, Section>, 1: Collection<int, Workgroup>}
     */
    protected function resolveFormOwners(User $user): array
    {
        if ($user->isSuperAdmin() || $user->isAdminKota() || $user->role === 'admin') {
            return [
                Section::with('pac')->orderBy('level')->orderBy('name')->get(),
                Workgroup::with('pac')->where('is_active', true)->orderBy('level')->orderBy('name')->get(),
            ];
        }

        if ($user->isOfficerPac()) {
            $pacId = $user->getManagedPacId();
            if (! $pacId) {
                return [collect(), collect()];
            }

            if ($user->isKetuaOrSekretarisPac()) {
                return [
                    Section::with('pac')
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->orderBy('name')
                        ->get(),
                    Workgroup::with('pac')
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(),
                ];
            }

            if ($user->isSectionOfficer()) {
                $mySectionId = $user->getOfficerSectionId();
                $memberId = $user->member?->id;

                $sections = $mySectionId
                    ? Section::with('pac')->where('id', $mySectionId)->where('pac_id', $pacId)->get()
                    : collect();

                $workgroups = $memberId
                    ? Workgroup::with('pac')
                        ->where('level', 'PAC ISNU')
                        ->where('pac_id', $pacId)
                        ->where('is_active', true)
                        ->whereHas('members', fn ($q) => $q->where('members.id', $memberId))
                        ->orderBy('name')
                        ->get()
                    : collect();

                return [$sections, $workgroups];
            }

            return [collect(), collect()];
        }

        // Pengurus PC ISNU
        if ($user->isKetuaOrSekretarisPc()) {
            return [
                Section::with('pac')
                    ->where('level', 'PC ISNU')
                    ->orderBy('name')
                    ->get(),
                Workgroup::with('pac')
                    ->where('level', 'PC ISNU')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(),
            ];
        }

        if ($user->isSectionOfficer()) {
            $mySectionId = $user->getOfficerSectionId();
            $memberId = $user->member?->id;

            $sections = $mySectionId
                ? Section::with('pac')->where('id', $mySectionId)->where('level', 'PC ISNU')->get()
                : collect();

            $workgroups = $memberId
                ? Workgroup::with('pac')
                    ->where('level', 'PC ISNU')
                    ->where('is_active', true)
                    ->whereHas('members', fn ($q) => $q->where('members.id', $memberId))
                    ->orderBy('name')
                    ->get()
                : collect();

            return [$sections, $workgroups];
        }

        return [collect(), collect()];
    }

    /**
     * Memetakan input `owner_key` (`section:{id}` atau `workgroup:{id}`) ke `section_id` dan `workgroup_id`.
     */
    protected function normalizeOwnerInput(Request $request): void
    {
        if (! $request->filled('owner_key')) {
            return;
        }

        $ownerKey = trim((string) $request->input('owner_key'));
        if (str_starts_with($ownerKey, 'section:')) {
            $request->merge([
                'section_id' => (int) substr($ownerKey, strlen('section:')),
                'workgroup_id' => null,
            ]);
        } elseif (str_starts_with($ownerKey, 'workgroup:')) {
            $request->merge([
                'section_id' => null,
                'workgroup_id' => (int) substr($ownerKey, strlen('workgroup:')),
            ]);
        }
    }

    /**
     * Menampilkan daftar Master Program Kerja Seksi.
     */
    public function index(Request $request): View
    {
        $this->checkAccess();

        $user = auth()->user();
        $isPacScoped = $user->isOfficerPac() && ! ($user->isSuperAdmin() || $user->isAdminKota() || $user->role === 'admin');
        $managedPacId = $isPacScoped ? $user->getManagedPacId() : null;

        $query = Program::with(['section.pac', 'workgroup.pac'])->withCount('events');

        if ($isPacScoped) {
            $query->where(function ($q) use ($managedPacId) {
                $q->whereHas('section', fn ($sq) => $sq->where('pac_id', $managedPacId))
                    ->orWhereHas('workgroup', fn ($wq) => $wq->where('pac_id', $managedPacId));
            });
        } else {
            if ($request->filled('level')) {
                $level = $request->level;
                $query->where(function ($q) use ($level) {
                    $q->whereHas('section', fn ($sq) => $sq->where('level', $level))
                        ->orWhereHas('workgroup', fn ($wq) => $wq->where('level', $level));
                });
            }

            if ($request->filled('pac_id')) {
                $pacId = (int) $request->pac_id;
                $query->where(function ($q) use ($pacId) {
                    $q->whereHas('section', fn ($sq) => $sq->where('pac_id', $pacId))
                        ->orWhereHas('workgroup', fn ($wq) => $wq->where('pac_id', $pacId));
                });
            }
        }

        if ($request->filled('owner_key')) {
            $ownerKey = trim((string) $request->owner_key);
            if (str_starts_with($ownerKey, 'section:')) {
                $query->where('section_id', (int) substr($ownerKey, strlen('section:')));
            } elseif (str_starts_with($ownerKey, 'workgroup:')) {
                $query->where('workgroup_id', (int) substr($ownerKey, strlen('workgroup:')));
            }
        } elseif ($request->filled('section_id')) {
            $query->where('section_id', (int) $request->section_id);
        } elseif ($request->filled('workgroup_id')) {
            $query->where('workgroup_id', (int) $request->workgroup_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('indicator', 'like', "%{$search}%")
                    ->orWhere('target', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('section', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('workgroup', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $programs = $query->orderBy('name')->paginate(15)->withQueryString();

        // Daftar Seksi & Pokja untuk filter tabel
        if ($isPacScoped) {
            $sections = Section::with('pac')->where('pac_id', $managedPacId)->orderBy('name')->get();
            $workgroups = Workgroup::with('pac')->where('pac_id', $managedPacId)->where('is_active', true)->orderBy('name')->get();
            $pacs = collect();
        } else {
            $sections = Section::with('pac')->orderBy('level')->orderBy('name')->get();
            $workgroups = Workgroup::with('pac')->where('is_active', true)->orderBy('level')->orderBy('name')->get();
            $pacs = Pac::orderBy('name')->get();
        }

        // Daftar Seksi & Pokja khusus untuk form Tambah/Edit (PC hanya PC + Pokja PC, PAC hanya PAC + Pokja PAC)
        [$formSections, $formWorkgroups] = $this->resolveFormOwners($user);

        if ($isPacScoped) {
            $pacCount = Program::where(function ($q) use ($managedPacId) {
                $q->whereHas('section', fn ($sq) => $sq->where('pac_id', $managedPacId))
                    ->orWhereHas('workgroup', fn ($wq) => $wq->where('pac_id', $managedPacId));
            })->count();

            $metrics = [
                'total' => $pacCount,
                'pc' => 0,
                'pac' => $pacCount,
                'events_count' => Event::where(function ($q) use ($managedPacId) {
                    $q->whereHas('section', fn ($sq) => $sq->where('pac_id', $managedPacId))
                        ->orWhereHas('workgroup', fn ($wq) => $wq->where('pac_id', $managedPacId));
                })->whereNotNull('program_id')->count(),
            ];
        } else {
            $metrics = [
                'total' => Program::count(),
                'pc' => Program::where(function ($q) {
                    $q->whereHas('section', fn ($sq) => $sq->where('level', 'PC ISNU'))
                        ->orWhereHas('workgroup', fn ($wq) => $wq->where('level', 'PC ISNU'));
                })->count(),
                'pac' => Program::where(function ($q) {
                    $q->whereHas('section', fn ($sq) => $sq->where('level', 'PAC ISNU'))
                        ->orWhereHas('workgroup', fn ($wq) => $wq->where('level', 'PAC ISNU'));
                })->count(),
                'events_count' => Event::whereNotNull('program_id')->count(),
            ];
        }

        if ($request->ajax()) {
            return view('admin.programs.partials.program_list', compact('programs', 'sections', 'formSections', 'formWorkgroups'));
        }

        return view('admin.programs.index', compact('programs', 'sections', 'workgroups', 'formSections', 'formWorkgroups', 'pacs', 'metrics'));
    }

    /**
     * Menyimpan Program Kerja baru.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->normalizeOwnerInput($request);

        $validated = $request->validate([
            'section_id' => 'nullable|required_without:workgroup_id|exists:sections,id',
            'workgroup_id' => 'nullable|required_without:section_id|exists:workgroups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'indicator' => 'nullable|string',
            'target' => 'nullable|string',
            'description' => 'nullable|string',
            'year' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ], [
            'section_id.required_without' => 'Pilih Seksi / Bidang atau Pokja pengampu program kerja.',
            'workgroup_id.required_without' => 'Pilih Seksi / Bidang atau Pokja pengampu program kerja.',
        ]);

        if (! empty($validated['workgroup_id'])) {
            $validated['section_id'] = null;
            $authorized = auth()->user()->canManageWorkgroupProgramOrEvent((int) $validated['workgroup_id']);
        } else {
            $validated['workgroup_id'] = null;
            $authorized = auth()->user()->canManageSectionProgramOrEvent((int) $validated['section_id']);
        }

        if (! $authorized) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menambahkan program kerja pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan program kerja pada seksi / pokja ini.');
        }

        $validated['description'] = $this->sanitizeDescriptionHtml($validated['description'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        $program = Program::create($validated);
        $ownerName = $program->owner_name;

        AuditLog::record(auth()->id(), 'Master Program', "Menambahkan Program Kerja [{$ownerName}]: {$program->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Program '{$program->name}' berhasil ditambahkan.",
            ]);
        }

        return back()->with('success', "Program '{$program->name}' berhasil ditambahkan.");
    }

    /**
     * Memperbarui Program Kerja.
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $program = Program::findOrFail($id);
        $this->normalizeOwnerInput($request);

        $validated = $request->validate([
            'section_id' => 'nullable|required_without:workgroup_id|exists:sections,id',
            'workgroup_id' => 'nullable|required_without:section_id|exists:workgroups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'indicator' => 'nullable|string',
            'target' => 'nullable|string',
            'description' => 'nullable|string',
            'year' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ], [
            'section_id.required_without' => 'Pilih Seksi / Bidang atau Pokja pengampu program kerja.',
            'workgroup_id.required_without' => 'Pilih Seksi / Bidang atau Pokja pengampu program kerja.',
        ]);

        if (! empty($validated['workgroup_id'])) {
            $validated['section_id'] = null;
            $canManageNewOwner = auth()->user()->canManageWorkgroupProgramOrEvent((int) $validated['workgroup_id']);
        } else {
            $validated['workgroup_id'] = null;
            $canManageNewOwner = auth()->user()->canManageSectionProgramOrEvent((int) $validated['section_id']);
        }

        if (! auth()->user()->canManageProgram($program) || ! $canManageNewOwner) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk memperbarui program kerja pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui program kerja pada seksi / pokja ini.');
        }

        $validated['description'] = $this->sanitizeDescriptionHtml($validated['description'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        $program->update($validated);
        $ownerName = $program->fresh()->owner_name;

        AuditLog::record(auth()->id(), 'Master Program', "Memperbarui Program Kerja [{$ownerName}]: {$program->name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Program '{$program->name}' berhasil diperbarui.",
            ]);
        }

        return back()->with('success', "Program '{$program->name}' berhasil diperbarui.");
    }

    /**
     * Sanitasi HTML deskripsi program kerja dari editor rich-text (TinyMCE)
     * agar hanya tag dan atribut pemformatan aman yang disimpan.
     */
    protected function sanitizeDescriptionHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        if ($trimmed === '') {
            return null;
        }

        // Hapus blok script/style beserta isinya
        $clean = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $trimmed) ?? $trimmed;

        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><table><thead><tbody><tfoot><tr><th><td><a><span><div><hr><sub><sup>';
        $clean = strip_tags($clean, $allowedTags);

        // Hapus atribut event handler (onclick, onerror, dll.)
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;

        // Netralkan skema berbahaya pada href/src (javascript:, vbscript:, data:text/html)
        $clean = preg_replace('/(href|src)\s*=\s*(["\'])\s*(?:javascript|vbscript|data\s*:\s*text\/html):.*?\2/i', '$1=$2#$2', $clean) ?? $clean;

        // Jika konten HTML kosong melompong (hanya tag kosong & spasi)
        $plainText = trim(str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($clean), ENT_QUOTES, 'UTF-8')));
        if ($plainText === '' && stripos($clean, '<table') === false) {
            return null;
        }

        return trim($clean);
    }

    /**
     * Menghapus Program Kerja.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $program = Program::findOrFail($id);

        if (! auth()->user()->canManageProgram($program)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menghapus program kerja pada seksi / pokja ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus program kerja pada seksi / pokja ini.');
        }

        // Program yang sudah ada kegiatannya tidak dapat dihapus
        if ($program->events()->exists()) {
            $msg = "Program '{$program->name}' tidak dapat dihapus karena sudah memiliki kegiatan yang terhubung.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            return back()->with('error', $msg);
        }

        $name = $program->name;
        $program->delete();

        AuditLog::record(auth()->id(), 'Master Program', "Menghapus Program Kerja: {$name}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Program '{$name}' berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Program '{$name}' berhasil dihapus.");
    }

    /**
     * Endpoint API untuk mengambil daftar program aktif berdasarkan ID Seksi.
     */
    public function getBySection(int $section_id): JsonResponse
    {
        if (auth()->user()->isKetuaOrSekretarisPac()) {
            $section = Section::find($section_id);
            if (! $section || (int) $section->pac_id !== (int) auth()->user()->getManagedPacId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke seksi ini.',
                    'programs' => [],
                ], 403);
            }
        }

        $programs = Program::where('section_id', $section_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'section_id', 'name', 'code', 'indicator', 'target']);

        return response()->json([
            'success' => true,
            'programs' => $programs,
        ]);
    }
}
