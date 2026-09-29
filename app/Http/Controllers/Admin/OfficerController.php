<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use App\Models\Pac;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfficerController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::where('membership_status', 'pengurus')
            ->with(['activePosition.pac', 'activeCard']);

        // Search Text
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter Level Kepengurusan (PC ISNU / PAC ISNU)
        if ($request->filled('level')) {
            $level = $request->level;
            $query->whereHas('activePosition', function ($q) use ($level) {
                if ($level === 'PC ISNU' || $level === 'Kota') {
                    $q->whereIn('level', ['PC ISNU', 'Kota', 'PC']);
                } elseif ($level === 'PAC ISNU' || $level === 'PAC') {
                    $q->whereIn('level', ['PAC ISNU', 'PAC']);
                }
            });
        }

        // Filter PAC Location
        if ($request->filled('pac_id')) {
            $pacId = $request->pac_id;
            $query->whereHas('activePosition', function ($q) use ($pacId) {
                $q->where('pac_id', $pacId);
            });
        }

        // Filter Jabatan Pengurus
        if ($request->filled('position_title')) {
            $positionTitle = $request->position_title;
            $query->whereHas('activePosition', function ($q) use ($positionTitle) {
                $q->where('position_title', 'like', "%{$positionTitle}%");
            });
        }

        $officers = $query->latest()->paginate(15)->withQueryString();
        $pacs = Pac::orderBy('name')->get();
        $eligibleMembers = Member::whereIn('membership_status', ['terverifikasi', 'pengurus'])
            ->orderBy('full_name')
            ->get();

        if ($request->ajax()) {
            return view('admin.pengurus.partials.officer_list', compact('officers'));
        }

        return view('admin.pengurus.index', compact('officers', 'pacs', 'eligibleMembers'));
    }

    public function promote(Request $request, $member_id)
    {
        $request->validate([
            'position_title' => 'required|string|max:255',
            'level' => 'required|in:PC ISNU,PAC ISNU,Kota,PAC',
            'pac_id' => 'nullable|exists:pac,id',
            'period' => 'required|string|max:100', // e.g. 2026-2030
            'sk_number' => 'nullable|string|max:100',
            'sk_file' => 'nullable|file|mimes:pdf,jpg,png|max:5048',
        ]);

        $member = Member::findOrFail($member_id);

        DB::beginTransaction();
        try {
            // Upload SK jika ada
            $skPath = null;
            if ($request->hasFile('sk_file')) {
                $skPath = $request->file('sk_file')->store('sk_pengurus', 'public');
            }

            // Non-aktifkan posisi lama jika ada
            Position::where('member_id', $member->id)->update(['is_active' => false]);

            // Normalisasi Level untuk DB (Kota / PAC)
            $levelDb = in_array($request->level, ['PC ISNU', 'Kota', 'PC']) ? 'Kota' : 'PAC';

            // Buat Posisi Pengurus Baru
            $position = Position::create([
                'member_id' => $member->id,
                'position_title' => $request->position_title,
                'level' => $levelDb,
                'pac_id' => $levelDb === 'PAC' ? $request->pac_id : null,
                'period' => $request->period,
                'sk_number' => $request->sk_number,
                'sk_file' => $skPath,
                'is_active' => true,
            ]);

            $oldStatus = $member->membership_status;
            $member->membership_status = 'pengurus';
            if ($levelDb === 'PAC' && $request->pac_id) {
                $member->pac_id = $request->pac_id;
            }
            $member->save();

            // Ubah / Buat Kartu Pengurus Digital (OFFICER)
            $activeCard = Card::where('member_id', $member->id)->where('is_active', true)->first();
            if ($activeCard) {
                $activeCard->card_type = 'OFFICER';
                $activeCard->save();
            } else {
                Card::create([
                    'member_id' => $member->id,
                    'card_number' => $member->member_number ?? 'ISNU-SBY-26-000000',
                    'card_type' => 'OFFICER',
                    'qr_token' => (string) Str::uuid(),
                    'issued_at' => now(),
                    'is_active' => true,
                ]);
            }

            $displayLevel = $levelDb === 'Kota' ? 'PC ISNU' : 'PAC ISNU';

            // Record History & Audit
            MembershipStatusHistory::create([
                'member_id' => $member->id,
                'status_from' => $oldStatus,
                'status_to' => 'pengurus',
                'changed_by' => auth()->id(),
                'notes' => "Ditetapkan sebagai Pengurus ({$request->position_title} - {$displayLevel} - Periode {$request->period})",
                'created_at' => now(),
            ]);

            AuditLog::record(auth()->id(), 'Penetapan Pengurus', "Ditetapkan Pengurus: {$member->full_name} sebagai {$request->position_title} ({$displayLevel})");

            DB::commit();

            return redirect()->route('admin.pengurus.index')->with('success', "{$member->full_name} berhasil ditetapkan sebagai Pengurus ({$request->position_title})!");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menetapkan pengurus: '.$e->getMessage());
        }
    }

    public function demote(Request $request, $member_id)
    {
        $member = Member::findOrFail($member_id);

        DB::beginTransaction();
        try {
            $oldStatus = $member->membership_status;
            $member->membership_status = 'terverifikasi';
            $member->save();

            // Non-aktifkan semua posisi pengurus
            Position::where('member_id', $member->id)->update(['is_active' => false]);

            // Kembalikan jenis kartu digital ke MEMBER
            $activeCard = Card::where('member_id', $member->id)->where('is_active', true)->first();
            if ($activeCard) {
                $activeCard->card_type = 'MEMBER';
                $activeCard->save();
            }

            MembershipStatusHistory::create([
                'member_id' => $member->id,
                'status_from' => $oldStatus,
                'status_to' => 'terverifikasi',
                'changed_by' => auth()->id(),
                'notes' => 'Status kepengurusan dibatalkan. Kembali menjadi Anggota Terverifikasi biasa.',
                'created_at' => now(),
            ]);

            AuditLog::record(auth()->id(), 'Pembatalan Pengurus', "Status kepengurusan {$member->full_name} dibatalkan (kembali menjadi Anggota Terverifikasi).");

            DB::commit();

            return redirect()->back()->with('success', "Status kepengurusan {$member->full_name} berhasil dibatalkan. Anggota kembali ke status Terverifikasi.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membatalkan status pengurus: '.$e->getMessage());
        }
    }
}
