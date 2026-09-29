<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CardOrder;
use Illuminate\Http\Request;

class CardOrderController extends Controller
{
    public function store(Request $request)
    {
        $member = auth()->user()->member;

        if (! $member || ($member->membership_status !== 'terverifikasi' && $member->membership_status !== 'pengurus')) {
            $msg = 'Pemesanan kartu fisik hanya berlaku untuk anggota yang telah terverifikasi.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $request->validate([
            'shipping_address' => 'required|string',
            'phone' => 'required|string|max:30',
            'notes' => 'nullable|string|max:500',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:3072',
        ]);

        // Cek jika sudah ada pemesanan aktif yang belum diterima
        $existingOrder = CardOrder::where('member_id', $member->id)
            ->where('status', '!=', 'received')
            ->first();

        if ($existingOrder) {
            $statusLabel = match ($existingOrder->status) {
                'pending' => 'DALAM PEMESANAN',
                'printed' => 'SUDAH JADI (DICETAK)',
                'shipped', 'delivered' => 'KARTU DIKIRIM',
                default => strtoupper($existingOrder->status),
            };
            $msg = "Anda sudah memiliki pemesanan kartu fisik yang sedang diproses (Status: {$statusLabel}).";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('info', $msg);
        }

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        }

        $order = CardOrder::create([
            'member_id' => $member->id,
            'status' => 'pending',
            'shipping_address' => $request->shipping_address,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'payment_proof' => $paymentProofPath,
            'ordered_at' => now(),
        ]);

        AuditLog::record(auth()->id(), 'Pemesanan Kartu Fisik', "Anggota {$member->full_name} melakukan pemesanan kartu fisik ISNU");

        $msg = 'Pemesanan Kartu Anggota ISNU Fisik berhasil dikirim! Silakan pantau status pemesanan Anda.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function receive(Request $request, $id)
    {
        $member = auth()->user()->member;

        if (! $member) {
            $msg = 'Anggota tidak ditemukan.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }

            return back()->with('error', $msg);
        }

        $order = CardOrder::where('id', $id)
            ->where('member_id', $member->id)
            ->firstOrFail();

        if ($order->status !== 'received') {
            $order->status = 'received';
            $order->received_at = now();
            if (! $order->printed_at) {
                $order->printed_at = now();
            }
            if (! $order->shipped_at && ! $order->delivered_at) {
                $order->shipped_at = now();
                $order->delivered_at = now();
            }
            $order->save();

            AuditLog::record(auth()->id(), 'Konfirmasi Terima Kartu Fisik', "Anggota {$member->full_name} mengonfirmasi bahwa kartu fisik telah diterima.");
        }

        $msg = 'Terima kasih! Pemesanan kartu anggota fisik Anda telah ditandai sebagai DITERIMA.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
