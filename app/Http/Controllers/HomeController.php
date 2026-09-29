<?php

namespace App\App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Mwc;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $totalMembers = Member::whereIn('membership_status', ['terverifikasi', 'pengurus'])->count();
        $totalPengurus = Member::where('membership_status', 'pengurus')->count();
        $totalMwc = Mwc::count();

        $recentMembers = Member::whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->with(['mwc'])
            ->latest('verified_at')
            ->take(6)
            ->get();

        return view('home', compact('totalMembers', 'totalPengurus', 'totalMwc', 'recentMembers'));
    }

    public function tentang()
    {
        return view('tentang');
    }

    public function daftarAnggota(Request $request)
    {
        $query = Member::whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->with(['mwc', 'activePosition']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('occupation', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kecamatan')) {
            $kec = $request->kecamatan;
            $query->where(function ($q) use ($kec) {
                $q->where('kecamatan', $kec)
                    ->orWhereHas('mwc', function ($mq) use ($kec) {
                        $mq->where('name', 'like', "%{$kec}%");
                    });
            });
        } elseif ($request->filled('mwc_id')) {
            $query->where('mwc_id', $request->mwc_id);
        }

        $members = $query->latest('verified_at')->paginate(12);

        $fileSby = base_path('data/master_lokasi_sby.csv');
        $kecamatans = [];
        if (file_exists($fileSby)) {
            foreach (file($fileSby, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $i => $line) {
                if ($i === 0) {
                    continue;
                }
                $parts = explode(';', $line);
                if (count($parts) >= 2) {
                    $code = trim($parts[0], ' "');
                    $name = trim($parts[1], ' "');
                    if (strlen($code) === 8 && str_starts_with($code, '35.78')) {
                        $kecamatans[] = strtoupper($name);
                    }
                }
            }
        }
        $fromMembers = Member::whereNotNull('kecamatan')->pluck('kecamatan')->toArray();
        $fromMwc = Mwc::pluck('name')->map(fn ($n) => strtoupper(str_replace('MWC NU ', '', $n)))->toArray();
        $kecamatans = array_values(array_unique(array_filter(array_map('trim', array_merge($kecamatans, $fromMembers, $fromMwc)))));
        sort($kecamatans);

        return view('daftar_anggota', compact('members', 'kecamatans'));
    }
}
