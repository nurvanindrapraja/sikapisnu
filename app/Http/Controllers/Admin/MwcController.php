<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Mwc;
use App\Models\Pac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MwcController extends Controller
{
    public function index()
    {
        $pacs = Pac::withCount('members')->latest()->get();

        return view('admin.mwc.index', compact('pacs'));
    }

    public function storeMwc(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:mwc,code',
            'name' => 'required|string',
            'city' => 'required|string',
        ]);

        $mwc = Mwc::create($validated);
        AuditLog::record(auth()->id(), 'Tambah MWC', "Menambahkan MWC: {$mwc->name}");

        Cache::forget('master_mwc_pacs');
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', 'MWC baru berhasil ditambahkan.');
    }

    public function storePac(Request $request)
    {
        $validated = $request->validate([
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'kecamatan' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:pac,code',
            'name' => 'required|string|max:255',
        ]);

        $mwc = Mwc::firstOrCreate(
            ['name' => 'MWC NU '.$validated['kecamatan']],
            ['code' => 'MWC-'.strtoupper(Str::slug($validated['kecamatan'])), 'city' => $validated['city'] ?? 'Kota Surabaya']
        );
        $validated['mwc_id'] = $mwc->id;

        $pac = Pac::create($validated);
        AuditLog::record(auth()->id(), 'Tambah PAC', "Menambahkan PAC ISNU: {$pac->name}");

        Cache::forget('master_mwc_pacs');
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', 'PAC ISNU '.$pac->name.' berhasil ditambahkan.');
    }

    public function updatePac(Request $request, $id)
    {
        $pac = Pac::findOrFail($id);

        $validated = $request->validate([
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'kecamatan' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:pac,code,'.$pac->id,
            'name' => 'required|string|max:255',
        ]);

        $pac->update($validated);
        AuditLog::record(auth()->id(), 'Edit PAC', "Memperbarui PAC ISNU: {$pac->name}");

        Cache::forget('master_mwc_pacs');
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', 'Data PAC ISNU '.$pac->name.' berhasil diperbarui.');
    }

    public function destroyPac($id)
    {
        $pac = Pac::findOrFail($id);
        $name = $pac->name;
        $pac->delete();
        AuditLog::record(auth()->id(), 'Hapus PAC', "Menghapus PAC ISNU: {$name}");

        Cache::forget('master_mwc_pacs');
        Cache::forget('admin_dashboard_stats');

        return back()->with('success', 'PAC ISNU '.$name.' berhasil dihapus.');
    }
}
