<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberCertification;
use App\Models\MemberEducation;
use App\Models\MemberNuTraining;
use App\Models\Mwc;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Member::count(),
            'terverifikasi' => Member::where('membership_status', 'terverifikasi')->count(),
            'pengurus' => Member::where('membership_status', 'pengurus')->count(),
            'pending' => Member::where('membership_status', 'menunggu_verifikasi')->count(),
            'perbaikan' => Member::where('membership_status', 'perbaikan')->count(),
            'ditolak' => Member::where('membership_status', 'ditolak')->count(),
        ];

        // Breakdown by MWC
        $mwcStats = Mwc::withCount(['members' => function ($q) {
            $q->whereIn('membership_status', ['terverifikasi', 'pengurus']);
        }])->get();

        // Breakdown by Occupation / Pekerjaan
        $occupationStats = Member::select('occupation', DB::raw('count(*) as total'))
            ->whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->groupBy('occupation')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        // Breakdown by Education / Pendidikan
        $educationStats = MemberEducation::select('level', DB::raw('count(distinct member_id) as total'))
            ->groupBy('level')
            ->orderByDesc('total')
            ->get();

        // Breakdown by NU Training / Kaderisasi NU
        $nuTrainingStats = MemberNuTraining::select('training_type', DB::raw('count(distinct member_id) as total'))
            ->groupBy('training_type')
            ->orderByDesc('total')
            ->get();

        // Breakdown by Skills Field / Sertifikasi
        $certificationStats = MemberCertification::select('field', DB::raw('count(distinct member_id) as total'))
            ->whereNotNull('field')
            ->groupBy('field')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        // Recent Registration Queue
        $pendingMembers = Member::where('membership_status', 'menunggu_verifikasi')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'mwcStats',
            'occupationStats',
            'educationStats',
            'nuTrainingStats',
            'certificationStats',
            'pendingMembers'
        ));
    }
}
