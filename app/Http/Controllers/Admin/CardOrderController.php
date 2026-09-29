<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CardOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CardOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = CardOrder::with(['member.user', 'member.pac', 'member.activeCard']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('ordered_at')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('admin.card_orders.partials.order_list', compact('orders'));
        }

        return view('admin.card_orders.index', compact('orders'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,printed,shipped,delivered,received',
        ]);

        $order = CardOrder::findOrFail($id);
        $oldStatus = $order->status;
        $order->status = $request->status;

        if ($request->status === 'printed') {
            if (! $order->printed_at) {
                $order->printed_at = now();
            }
        } elseif ($request->status === 'shipped' || $request->status === 'delivered') {
            if (! $order->printed_at) {
                $order->printed_at = now();
            }
            if (! $order->shipped_at) {
                $order->shipped_at = now();
            }
            if (! $order->delivered_at) {
                $order->delivered_at = now();
            }
        } elseif ($request->status === 'received') {
            if (! $order->printed_at) {
                $order->printed_at = now();
            }
            if (! $order->shipped_at) {
                $order->shipped_at = now();
            }
            if (! $order->delivered_at) {
                $order->delivered_at = now();
            }
            if (! $order->received_at) {
                $order->received_at = now();
            }
        }

        $order->save();

        AuditLog::record(auth()->id(), 'Update Status Kartu Fisik', "Status pemesanan kartu anggota {$order->member->full_name} diubah dari {$oldStatus} ke {$request->status}");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Status pemesanan kartu fisik berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Status pemesanan kartu fisik berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $order = CardOrder::with('member')->findOrFail($id);
        $memberName = $order->member->full_name ?? 'Anggota';

        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        $order->delete();

        AuditLog::record(auth()->id(), 'Hapus Pemesanan Kartu Fisik', "Pemesanan kartu fisik anggota {$memberName} telah dihapus.");

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pemesanan kartu fisik berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data pemesanan kartu fisik berhasil dihapus.');
    }
}
