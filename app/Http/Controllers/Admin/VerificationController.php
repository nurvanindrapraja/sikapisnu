<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Card;
use App\Models\Member;
use App\Models\MembershipStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'menunggu_verifikasi');

        $query = Member::with(['user', 'mwc', 'pac']);

        if ($status !== 'all') {
            $query->where('membership_status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $members = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('admin.verifikasi.partials.member_list', compact('members', 'status'));
        }

        return view('admin.verifikasi.index', compact('members', 'status'));
    }

    public function show($id)
    {
        $member = Member::with([
            'user',
            'mwc',
            'pac',
            'educations',
            'organizations',
            'employments',
            'nuTrainings',
            'certifications',
            'statusHistories.changer',
        ])->findOrFail($id);

        return view('admin.verifikasi.show', compact('member'));
    }

    public function verifyAccount(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        DB::beginTransaction();
        try {
            $oldStatus = $member->membership_status;

            if ($member->membership_status === 'menunggu_verifikasi') {
                $member->membership_status = 'calon';
                $member->save();
            }

            if ($member->user) {
                $member->user->is_active = true;
                $member->user->email_verified_at = $member->user->email_verified_at ?? now();
                $member->user->save();
            }

            MembershipStatusHistory::create([
                'member_id' => $member->id,
                'status_from' => $oldStatus,
                'status_to' => $member->membership_status,
                'changed_by' => auth()->id(),
                'notes' => 'Verifikasi pendaftaran akun berhasil. Akun user diaktifkan untuk login.',
                'created_at' => now(),
            ]);

            AuditLog::record(auth()->id(), 'Verifikasi Akun', "Akun diaktifkan: {$member->full_name} ({$member->email})");

            DB::commit();

            return redirect()->route('admin.verifikasi.show', $member->id)
                ->with('success', "Akun Pendaftaran {$member->full_name} berhasil diverifikasi! User kini telah dapat masuk (login) ke aplikasi.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memverifikasi akun pendaftaran: '.$e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        DB::beginTransaction();
        try {
            $currentYearShort = date('y'); // e.g. 26

            // Format Nomor Anggota: ISNU-SBY-26-XXXXXX
            if (! $member->member_number) {
                $maxNumber = Member::withTrashed()
                    ->whereNotNull('member_number')
                    ->get()
                    ->map(function ($m) {
                        if (preg_match('/(\d+)$/', $m->member_number, $matches)) {
                            return (int) $matches[1];
                        }

                        return 0;
                    })
                    ->max() ?? 0;

                $nextId = $maxNumber + 1;
                $memberNumber = sprintf('ISNU-SBY-%s-%06d', $currentYearShort, $nextId);

                while (Member::withTrashed()->where('member_number', $memberNumber)->exists()) {
                    $nextId++;
                    $memberNumber = sprintf('ISNU-SBY-%s-%06d', $currentYearShort, $nextId);
                }
                $member->member_number = $memberNumber;
            }

            $oldStatus = $member->membership_status;
            $member->membership_status = 'terverifikasi';
            $member->verified_at = now();
            $member->verified_by = auth()->id();
            $member->rejection_note = null;
            $member->save();

            if ($member->user) {
                $member->user->is_active = true;
                $member->user->email_verified_at = now();
                $member->user->save();
            }

            // Generate Kartu Anggota Digital jika belum ada
            if (! $member->cards()->where('is_active', true)->exists()) {
                Card::create([
                    'member_id' => $member->id,
                    'card_number' => $member->member_number,
                    'card_type' => 'MEMBER',
                    'qr_token' => (string) Str::uuid(),
                    'issued_at' => now(),
                    'is_active' => true,
                ]);
            }

            // Record History
            MembershipStatusHistory::create([
                'member_id' => $member->id,
                'status_from' => $oldStatus,
                'status_to' => 'terverifikasi',
                'changed_by' => auth()->id(),
                'notes' => 'Disetujui dan diverifikasi oleh Admin. Nomor Anggota: '.$member->member_number,
                'created_at' => now(),
            ]);

            AuditLog::record(auth()->id(), 'Verifikasi Anggota', "Disetujui: {$member->full_name} ({$member->member_number})");

            DB::commit();

            return redirect()->route('admin.verifikasi.index')
                ->with('success', "Anggota {$member->full_name} berhasil diverifikasi! Nomor Anggota: {$member->member_number}");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memverifikasi anggota: '.$e->getMessage());
        }
    }

    public function requestRevision(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|min:5',
        ]);

        $member = Member::findOrFail($id);
        $oldStatus = $member->membership_status;

        $member->membership_status = 'perbaikan';
        $member->rejection_note = $request->notes;
        $member->save();

        if ($member->user) {
            $member->user->is_active = true;
            $member->user->save();
        }

        MembershipStatusHistory::create([
            'member_id' => $member->id,
            'status_from' => $oldStatus,
            'status_to' => 'perbaikan',
            'changed_by' => auth()->id(),
            'notes' => 'Dikembalikan untuk perbaikan: '.$request->notes,
            'created_at' => now(),
        ]);

        AuditLog::record(auth()->id(), 'Minta Perbaikan', "Dikembalikan untuk perbaikan: {$member->full_name}");

        return redirect()->route('admin.verifikasi.index')
            ->with('info', "Pendaftaran {$member->full_name} dikembalikan ke anggota untuk perbaikan data.");
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|min:5',
        ]);

        $member = Member::findOrFail($id);
        $oldStatus = $member->membership_status;

        $member->membership_status = 'ditolak';
        $member->rejection_note = $request->notes;
        $member->save();

        if ($member->user) {
            $member->user->is_active = false;
            $member->user->save();
        }

        MembershipStatusHistory::create([
            'member_id' => $member->id,
            'status_from' => $oldStatus,
            'status_to' => 'ditolak',
            'changed_by' => auth()->id(),
            'notes' => 'Ditolak: '.$request->notes,
            'created_at' => now(),
        ]);

        AuditLog::record(auth()->id(), 'Tolak Pendaftaran', "Ditolak: {$member->full_name}");

        return redirect()->route('admin.verifikasi.index')
            ->with('warning', "Pendaftaran {$member->full_name} telah ditolak.");
    }
}
