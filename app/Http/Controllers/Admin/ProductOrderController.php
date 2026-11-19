<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductOrderController extends Controller
{
    /**
     * Memeriksa otorisasi: pengelolaan pemesanan dan pengiriman barang dilakukan
     * oleh Pengurus NU jabatan Bendahara / Wakil Bendahara (serta Superadmin & Admin Kota).
     */
    protected function checkAccess(): void
    {
        if (! auth()->check() || ! auth()->user()->canManageProductOrders()) {
            abort(403, 'Hanya Pengurus dengan jabatan Bendahara / Wakil Bendahara atau Admin yang berhak mengelola pemesanan dan pengiriman barang.');
        }
    }

    /**
     * Menampilkan daftar pesanan merchandise ISNU.
     */
    public function index(Request $request): View
    {
        $this->checkAccess();

        $query = ProductOrder::with(['user.member', 'items.product'])->latest();

        // Filter Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        // Statistik Ringkasan untuk Bendahara
        $stats = [
            'total' => ProductOrder::count(),
            'pending_payment' => ProductOrder::where('status', ProductOrder::STATUS_PENDING_PAYMENT)->count(),
            'payment_uploaded' => ProductOrder::where('status', ProductOrder::STATUS_PAYMENT_UPLOADED)->count(),
            'paid' => ProductOrder::where('status', ProductOrder::STATUS_PAID)->count(),
            'shipped' => ProductOrder::where('status', ProductOrder::STATUS_SHIPPED)->count(),
            'received' => ProductOrder::where('status', ProductOrder::STATUS_RECEIVED)->count(),
            'total_income' => ProductOrder::whereIn('status', [ProductOrder::STATUS_PAID, ProductOrder::STATUS_SHIPPED, ProductOrder::STATUS_RECEIVED])->sum('grand_total'),
        ];

        return view('admin.product_orders.index', compact('orders', 'stats'));
    }

    /**
     * Menampilkan rincian pesanan merchandise, bukti transfer, dan kontrol status.
     */
    public function show(int $id): View
    {
        $this->checkAccess();

        $order = ProductOrder::with([
            'user.member',
            'items.product',
            'verifier',
            'shippedByUser',
            'reviews',
        ])->findOrFail($id);

        return view('admin.product_orders.show', compact('order'));
    }

    /**
     * Verifikasi bukti pembayaran non-tunai oleh Bendahara / Wakil Bendahara.
     */
    public function verifyPayment(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $order = ProductOrder::findOrFail($id);

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->action === 'approve') {
            $order->update([
                'status' => ProductOrder::STATUS_PAID,
                'payment_verified_at' => now(),
                'payment_verified_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            AuditLog::record(
                auth()->id(),
                'Verifikasi Pembayaran Merchandise',
                "Bendahara menyetujui bukti pembayaran untuk pesanan {$order->order_number} sebesar {$order->formatted_grand_total}."
            );

            return back()->with('success', "Pembayaran untuk pesanan {$order->order_number} berhasil diverifikasi dan disetujui! Barang siap disiapkan dan dikirim.");
        } else {
            $order->update([
                'status' => ProductOrder::STATUS_PENDING_PAYMENT,
                'rejection_reason' => $request->rejection_reason ?: 'Bukti pembayaran tidak terbaca atau jumlah transfer tidak sesuai. Silakan upload ulang.',
            ]);

            AuditLog::record(
                auth()->id(),
                'Penolakan Pembayaran Merchandise',
                "Bendahara menolak bukti pembayaran pesanan {$order->order_number}. Alasan: {$order->rejection_reason}"
            );

            return back()->with('warning', "Bukti pembayaran pesanan {$order->order_number} telah ditolak. Status pesanan dikembalikan ke Menunggu Pembayaran.");
        }
    }

    /**
     * Pengelolaan pengiriman barang oleh Bendahara / Wakil Bendahara.
     * Mengisi nama kurir/ekspedisi, nomor resi pengiriman, dan catatan pengiriman.
     */
    public function updateShipping(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $order = ProductOrder::findOrFail($id);

        $request->validate([
            'courier_name' => ['required', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'shipping_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'courier_name.required' => 'Nama kurir / ekspedisi pengiriman wajib diisi (misal: JNE, J&T, SiCepat, atau Kurir Kantor PC ISNU).',
        ]);

        $order->update([
            'courier_name' => $request->courier_name,
            'tracking_number' => $request->tracking_number,
            'shipping_notes' => $request->shipping_notes,
            'status' => ProductOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
            'shipped_by' => auth()->id(),
        ]);

        AuditLog::record(
            auth()->id(),
            'Pengiriman Barang Merchandise',
            "Memperbarui pengiriman pesanan {$order->order_number} via {$order->courier_name} (Resi: ".($order->tracking_number ?: '-').').'
        );

        return back()->with('success', "Informasi pengiriman untuk pesanan {$order->order_number} berhasil disimpan. Status pesanan kini berubah menjadi Sedang Dikirim.");
    }

    /**
     * Mengonfirmasi barang diterima langsung oleh Bendahara / Admin jika diserahterimakan langsung di kantor PCNU.
     */
    public function markReceived(Request $request, int $id): RedirectResponse
    {
        $this->checkAccess();

        $order = ProductOrder::findOrFail($id);

        $order->update([
            'status' => ProductOrder::STATUS_RECEIVED,
            'received_at' => now(),
            'receipt_notes' => $request->input('receipt_notes', 'Diserahterimakan langsung oleh Pengurus / Bendahara PC ISNU.'),
        ]);

        AuditLog::record(
            auth()->id(),
            'Selesai Serah Terima Barang',
            "Menyelesaikan serah terima pesanan merchandise {$order->order_number}."
        );

        return back()->with('success', "Pesanan {$order->order_number} telah diselesaikan.");
    }
}
