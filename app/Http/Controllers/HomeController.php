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

        if ($request->filled('mwc_id')) {
            $query->where('mwc_id', $request->mwc_id);
        }

        $members = $query->latest('verified_at')->paginate(12);
        $mwcs = Mwc::orderBy('name')->get();

        return view('daftar_anggota', compact('members', 'mwcs'));
    }
}
