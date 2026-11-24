<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Program;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Menampilkan daftar Master Program Kerja Seksi.
     */
    public function index(Request $request): View
    {
        $this->checkAccess();

        $query = Program::with(['section.pac'])->withCount('events');

        if (auth()->user()->isKetuaOrSekretarisPac()) {
            $pacId = auth()->user()->getManagedPacId();
            $query->whereHas('section', fn ($q) => $q->where('pac_id', $pacId));
        } else {
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            if ($request->filled('level')) {
                $level = $request->level;
                $query->whereHas('section', function ($q) use ($level) {
                    $q->where('level', $level);
                });
            }
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
                    });
            });
        }

        $programs = $query->orderBy('name')->paginate(15)->withQueryString();
        $sections = auth()->user()->isKetuaOrSekretarisPac()
            ? Section::with('pac')->where('pac_id', auth()->user()->getManagedPacId())->orderBy('name')->get()
            : Section::with('pac')->orderBy('level')->orderBy('name')->get();

        if (auth()->user()->isKetuaOrSekretarisPac()) {
            $pacId = auth()->user()->getManagedPacId();
            $metrics = [
                'total' => Program::whereHas('section', fn ($q) => $q->where('pac_id', $pacId))->count(),
                'pc' => 0,
                'pac' => Program::whereHas('section', fn ($q) => $q->where('pac_id', $pacId))->count(),
                'events_count' => Event::whereHas('section', fn ($q) => $q->where('pac_id', $pacId))->whereNotNull('program_id')->count(),
            ];
        } else {
            $metrics = [
                'total' => Program::count(),
                'pc' => Program::whereHas('section', fn ($q) => $q->where('level', 'PC ISNU'))->count(),
                'pac' => Program::whereHas('section', fn ($q) => $q->where('level', 'PAC ISNU'))->count(),
                'events_count' => Event::whereNotNull('program_id')->count(),
            ];
        }

        if ($request->ajax()) {
            return view('admin.programs.partials.program_list', compact('programs', 'sections'));
        }

        return view('admin.programs.index', compact('programs', 'sections', 'metrics'));
    }

    /**
     * Menyimpan Program Kerja baru.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'indicator' => 'nullable|string',
            'target' => 'nullable|string',
            'description' => 'nullable|string',
            'year' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if (! auth()->user()->canManageSectionProgramOrEvent((int) $validated['section_id'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menambahkan program kerja pada seksi ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan program kerja pada seksi ini.');
        }

        $validated['description'] = $this->sanitizeDescriptionHtml($validated['description'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        $program = Program::create($validated);
        $sectionName = $program->section?->name ?? 'Seksi';

        AuditLog::record(auth()->id(), 'Master Program', "Menambahkan Program Kerja [{$sectionName}]: {$program->name}");

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

        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'indicator' => 'nullable|string',
            'target' => 'nullable|string',
            'description' => 'nullable|string',
            'year' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if (! auth()->user()->canManageSectionProgramOrEvent((int) $program->section_id) ||
            ! auth()->user()->canManageSectionProgramOrEvent((int) $validated['section_id'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk memperbarui program kerja pada seksi ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui program kerja pada seksi ini.');
        }

        $validated['description'] = $this->sanitizeDescriptionHtml($validated['description'] ?? null);
        $validated['is_active'] = $request->boolean('is_active', true);

        $program->update($validated);
        $sectionName = $program->section?->name ?? 'Seksi';

        AuditLog::record(auth()->id(), 'Master Program', "Memperbarui Program Kerja [{$sectionName}]: {$program->name}");

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

        if (! auth()->user()->canManageSectionProgramOrEvent((int) $program->section_id)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menghapus program kerja pada seksi ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus program kerja pada seksi ini.');
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
