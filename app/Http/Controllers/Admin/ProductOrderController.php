<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductOrder;
use Illuminate\Http\JsonResponse;
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
    public function index(Request $request): View|JsonResponse
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
            'total_income' => (float) ProductOrder::whereIn('status', [ProductOrder::STATUS_PAID, ProductOrder::STATUS_SHIPPED, ProductOrder::STATUS_RECEIVED])->sum('grand_total'),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.product_orders.partials.order_table', compact('orders'))->render(),
                'stats' => $stats,
                'total' => $orders->total(),
            ]);
        }

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
    public function verifyPayment(Request $request, int $id): RedirectResponse|JsonResponse
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

            $msg = "Pembayaran untuk pesanan {$order->order_number} berhasil diverifikasi dan disetujui! Barang siap disiapkan dan dikirim.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'action' => 'approve',
                    'message' => $msg,
                    'order' => [
                        'status' => $order->status,
                        'status_label' => $order->status_label,
                        'status_badge_class' => $order->status_badge_class,
                        'payment_verified_at' => $order->payment_verified_at?->translatedFormat('d M Y, H:i').' WIB',
                        'verifier_name' => auth()->user()->name,
                    ],
                ]);
            }

            return back()->with('success', $msg);
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

            $msg = "Bukti pembayaran pesanan {$order->order_number} telah ditolak. Status pesanan dikembalikan ke Menunggu Pembayaran.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'action' => 'reject',
                    'message' => $msg,
                    'order' => [
                        'status' => $order->status,
                        'status_label' => $order->status_label,
                        'status_badge_class' => $order->status_badge_class,
                        'rejection_reason' => $order->rejection_reason,
                    ],
                ]);
            }

            return back()->with('warning', $msg);
        }
    }

    /**
     * Pengelolaan pengiriman barang oleh Bendahara / Wakil Bendahara.
     * Mengisi nama kurir/ekspedisi, nomor resi pengiriman, dan catatan pengiriman.
     */
    public function updateShipping(Request $request, int $id): RedirectResponse|JsonResponse
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

        $msg = "Informasi pengiriman untuk pesanan {$order->order_number} berhasil disimpan. Status pesanan kini berubah menjadi Sedang Dikirim.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'order' => [
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'status_badge_class' => $order->status_badge_class,
                    'courier_name' => $order->courier_name,
                    'tracking_number' => $order->tracking_number,
                    'shipping_notes' => $order->shipping_notes,
                    'shipped_at' => $order->shipped_at?->translatedFormat('d F Y, H:i').' WIB',
                    'shipped_by_name' => auth()->user()->name,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Mengonfirmasi barang diterima langsung oleh Bendahara / Admin jika diserahterimakan langsung di kantor PCNU.
     */
    public function markReceived(Request $request, int $id): RedirectResponse|JsonResponse
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

        $msg = "Pesanan {$order->order_number} telah diselesaikan.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'order' => [
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'status_badge_class' => $order->status_badge_class,
                    'received_at' => $order->received_at?->translatedFormat('d F Y, H:i').' WIB',
                    'receipt_notes' => $order->receipt_notes,
                ],
            ]);
        }

        return back()->with('success', $msg);
    }
}
