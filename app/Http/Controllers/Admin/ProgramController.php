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
    /**
     * Menampilkan daftar Master Program Kerja Seksi.
     */
    public function index(Request $request): View
    {
        $query = Program::with(['section.pac'])->withCount('events');

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('level')) {
            $level = $request->level;
            $query->whereHas('section', function ($q) use ($level) {
                $q->where('level', $level);
            });
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
        $sections = Section::with('pac')->orderBy('level')->orderBy('name')->get();

        $metrics = [
            'total' => Program::count(),
            'pc' => Program::whereHas('section', fn ($q) => $q->where('level', 'PC ISNU'))->count(),
            'pac' => Program::whereHas('section', fn ($q) => $q->where('level', 'PAC ISNU'))->count(),
            'events_count' => Event::whereNotNull('program_id')->count(),
        ];

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
