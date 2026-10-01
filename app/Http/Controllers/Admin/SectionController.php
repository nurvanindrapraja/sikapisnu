<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pac;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Section::with('pac');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('pac_id')) {
            $query->where('pac_id', $request->pac_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sections = $query->orderBy('level')->orderBy('name')->paginate(15)->withQueryString();
        $pacs = Pac::orderBy('name')->get();

        if ($request->ajax()) {
            return view('admin.sections.partials.section_list', compact('sections'));
        }

        return view('admin.sections.index', compact('sections', 'pacs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        if ($validated['level'] === 'PC ISNU') {
            $validated['pac_id'] = null;
        }

        $section = Section::create($validated);
        AuditLog::record(auth()->id(), 'Master Seksi', "Menambahkan Seksi Baru [{$section->level}]: {$section->name}");

        return back()->with('success', "Seksi '{$section->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $section = Section::findOrFail($id);

        $validated = $request->validate([
            'level' => 'required|in:PC ISNU,PAC ISNU',
            'pac_id' => 'nullable|required_if:level,PAC ISNU|exists:pac,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        if ($validated['level'] === 'PC ISNU') {
            $validated['pac_id'] = null;
        }

        $section->update($validated);
        AuditLog::record(auth()->id(), 'Master Seksi', "Memperbarui Seksi [{$section->level}]: {$section->name}");

        return back()->with('success', "Seksi '{$section->name}' berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $section = Section::findOrFail($id);
        $name = $section->name;
        $section->delete();

        AuditLog::record(auth()->id(), 'Master Seksi', "Menghapus Seksi: {$name}");

        return back()->with('success', "Seksi '{$name}' berhasil dihapus.");
    }
}
