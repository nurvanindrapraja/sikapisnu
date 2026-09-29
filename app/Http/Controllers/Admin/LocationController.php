<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CustomLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomLocation::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('parent_code', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%");
            });
        }

        $customLocations = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('admin.locations.partials.location_list', compact('customLocations'));
        }

        return view('admin.locations.index', compact('customLocations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|in:province,city,kecamatan,kelurahan',
            'parent_code' => 'nullable|string|max:30',
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:255',
        ]);

        $code = trim($validated['code']);
        $name = trim($validated['name']);

        // Check against default CSV locations and CustomLocation table
        $existingMap = $this->getExistingLocationsMap();

        if (isset($existingMap[$code]) || CustomLocation::where('code', $code)->exists()) {
            return back()->withErrors(['code' => "Kode lokasi '{$code}' sudah ada di sistem."])->withInput();
        }

        $upperName = mb_strtoupper($name);
        $nameExistsInCsv = in_array($upperName, array_map('mb_strtoupper', $existingMap), true);
        $nameExistsInDb = CustomLocation::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists();

        if ($nameExistsInCsv || $nameExistsInDb) {
            return back()->withErrors(['name' => "Nama lokasi '{$name}' sudah ada di sistem."])->withInput();
        }

        $custom = CustomLocation::create([
            'level' => $validated['level'],
            'parent_code' => $request->input('parent_code'),
            'code' => $code,
            'name' => $name,
        ]);

        // Invalidate location cache
        Cache::forget('master_locations_flat');

        AuditLog::record(auth()->id(), 'Master Lokasi', "Menambahkan lokasi baru [{$custom->level}]: {$custom->name} ({$custom->code})");

        return back()->with('success', "Lokasi '{$custom->name}' ({$custom->code}) berhasil ditambahkan!");
    }

    public function update(Request $request, $id)
    {
        $custom = CustomLocation::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:255',
        ]);

        $code = trim($validated['code']);
        $name = trim($validated['name']);

        $existingMap = $this->getExistingLocationsMap();

        // Check code uniqueness
        $codeExistsInCsv = isset($existingMap[$code]);
        $codeExistsInDb = CustomLocation::where('code', $code)->where('id', '!=', $id)->exists();

        if ($codeExistsInDb || ($codeExistsInCsv && $code !== $custom->code)) {
            return back()->withErrors(['code' => "Kode lokasi '{$code}' sudah digunakan di sistem."])->withInput();
        }

        // Check name uniqueness
        $upperName = mb_strtoupper($name);
        $nameExistsInCsv = in_array($upperName, array_map('mb_strtoupper', $existingMap), true);
        $nameExistsInDb = CustomLocation::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->where('id', '!=', $id)->exists();

        if ($nameExistsInDb || ($nameExistsInCsv && mb_strtoupper($name) !== mb_strtoupper($custom->name))) {
            return back()->withErrors(['name' => "Nama lokasi '{$name}' sudah ada di sistem."])->withInput();
        }

        $custom->update([
            'code' => $code,
            'name' => $name,
        ]);

        // Invalidate location cache
        Cache::forget('master_locations_flat');

        AuditLog::record(auth()->id(), 'Master Lokasi', "Memperbarui lokasi [{$custom->level}]: {$custom->name} ({$custom->code})");

        return back()->with('success', "Lokasi '{$custom->name}' berhasil diperbarui!");
    }

    public function destroy($id)
    {
        $custom = CustomLocation::findOrFail($id);
        $name = $custom->name;
        $code = $custom->code;

        $custom->delete();

        // Invalidate location cache
        Cache::forget('master_locations_flat');

        AuditLog::record(auth()->id(), 'Master Lokasi', "Menghapus lokasi [{$custom->level}]: {$name} ({$code})");

        return back()->with('success', "Lokasi '{$name}' ({$code}) berhasil dihapus dari sistem.");
    }

    private function getExistingLocationsMap(): array
    {
        $fileSby = base_path('data/master_lokasi_sby.csv');
        $fileAll = base_path('data/master_lokasi_all.csv');

        $locations = [];

        if (file_exists($fileSby)) {
            foreach (file($fileSby, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $line) {
                if ($i === 0) {
                    continue;
                }
                $parts = explode(';', $line);
                if (count($parts) >= 2) {
                    $c = trim($parts[0], ' "');
                    $n = trim($parts[1], ' "');
                    $locations[$c] = $n;
                }
            }
        }

        if (file_exists($fileAll)) {
            foreach (file($fileAll, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $line) {
                if ($i === 0) {
                    continue;
                }
                $parts = explode(';', $line);
                if (count($parts) >= 2) {
                    $c = trim($parts[0], ' "');
                    $n = trim($parts[1], ' "');
                    if (! isset($locations[$c])) {
                        $locations[$c] = $n;
                    }
                }
            }
        }

        return $locations;
    }
}
