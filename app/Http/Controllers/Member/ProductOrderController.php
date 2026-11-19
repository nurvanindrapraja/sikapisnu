<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductOrderController extends Controller
{
    /**
     * Memproses checkout pemesanan merchandise oleh anggota ISNU.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Validasi status keanggotaan
        $member = $user->member;
        if (! $user->is_active || ($member && $member->membership_status === 'menunggu_verifikasi')) {
            return back()->with('error', 'Akun Anda belum aktif atau masih dalam proses verifikasi admin. Pemesanan merchandise resmi hanya dapat dilakukan oleh anggota yang telah terverifikasi.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'variant_note' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'product_id.required' => 'Pilih produk yang ingin dipesan.',
            'quantity.min' => 'Jumlah pesanan minimal 1 buah.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'recipient_phone.required' => 'Nomor WhatsApp / telepon penerima wajib diisi.',
            'shipping_address.required' => 'Alamat pengiriman lengkap wajib diisi.',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if (! $product->is_active) {
            return back()->with('error', 'Mohon maaf, produk ini saat ini sedang tidak aktif.');
        }

        if ($product->stock < $validated['quantity']) {
            return back()->with('error', "Mohon maaf, stok produk tidak mencukupi. Sisa stok tersedia: {$product->stock} buah.");
        }

        DB::beginTransaction();
        try {
            $quantity = (int) $validated['quantity'];
            $totalAmount = (float) $product->price * $quantity;
            $shippingCost = 0.00; // Pengiriman default / gratis atau diinfokan tersendiri
            $grandTotal = $totalAmount + $shippingCost;

            $order = ProductOrder::create([
                'order_number' => ProductOrder::generateOrderNumber(),
                'user_id' => $user->id,
                'member_id' => $member?->id,
                'total_amount' => $totalAmount,
                'shipping_cost' => $shippingCost,
                'grand_total' => $grandTotal,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
                'status' => ProductOrder::STATUS_PENDING_PAYMENT,
                'payment_method' => 'transfer_bank',
            ]);

            ProductOrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => $totalAmount,
                'variant_note' => $validated['variant_note'] ?? null,
            ]);

            // Kurangi stok barang
            $product->decrement('stock', $quantity);

            AuditLog::record(
                $user->id,
                'Pemesanan Merchandise',
                "Membuat pesanan baru {$order->order_number} untuk {$product->name} ({$quantity} pcs)."
            );

            DB::commit();

            return redirect()->route('member.orders.show', $order->id)->with(
                'success',
                'Pesanan Anda berhasil dibuat! Silakan lakukan pembayaran non-tunai dan unggah bukti transfer agar pesanan segera diproses oleh Bendahara.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal memproses pesanan: '.$e->getMessage());
        }
    }

    /**
     * Menampilkan riwayat pesanan merchandise milik anggota.
     */
    public function index(Request $request): View
    {
        $query = ProductOrder::where('user_id', auth()->id())
            ->with(['items.product'])
            ->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('member.orders.index', compact('orders'));
    }

    /**
     * Menampilkan rincian pesanan, petunjuk pembayaran non-tunai, dan tracking.
     */
    public function show(int $id): View
    {
        $order = ProductOrder::where('user_id', auth()->id())
            ->with(['items.product', 'reviews'])
            ->findOrFail($id);

        return view('member.orders.show', compact('order'));
    }

    /**
     * Mengunggah bukti pembayaran non-tunai oleh pembeli.
     */
    public function uploadPayment(Request $request, int $id): RedirectResponse
    {
        $order = ProductOrder::where('user_id', auth()->id())->findOrFail($id);

        if (! $order->canUploadPaymentProof()) {
            return back()->with('error', 'Pesanan ini sudah tidak dalam tahap upload pembayaran.');
        }

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'payment_proof.required' => 'File bukti pembayaran wajib dipilih.',
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.max' => 'Ukuran foto bukti pembayaran maksimal 5MB.',
        ]);

        if ($request->hasFile('payment_proof')) {
            // Hapus bukti lama jika ada
            if ($order->payment_proof_path && Storage::disk('public')->exists($order->payment_proof_path)) {
                Storage::disk('public')->delete($order->payment_proof_path);
            }

            $path = $request->file('payment_proof')->store('merchandise_payments', 'public');

            $order->update([
                'payment_proof_path' => $path,
                'payment_uploaded_at' => now(),
                'status' => ProductOrder::STATUS_PAYMENT_UPLOADED,
                'rejection_reason' => null, // Reset pesan tolak jika sebelumnya ditolak
            ]);

            AuditLog::record(
                auth()->id(),
                'Upload Bukti Pembayaran',
                "Mengunggah bukti pembayaran untuk pesanan {$order->order_number}."
            );

            return back()->with(
                'success',
                'Bukti transfer pembayaran berhasil diunggah! Pengurus (Bendahara/Wakil Bendahara) akan memverifikasi pembayaran Anda.'
            );
        }

        return back()->with('error', 'Gagal memproses file bukti pembayaran.');
    }

    /**
     * Konfirmasi bahwa barang sudah berhasil diterima oleh pembeli / penerima.
     * Status barang berhasil diterima (selesai), dapat dibuktikan dengan foto penerimaan (opsional).
     */
    public function confirmReceived(Request $request, int $id): RedirectResponse
    {
        $order = ProductOrder::where('user_id', auth()->id())->findOrFail($id);

        if (! $order->canConfirmReceipt()) {
            return back()->with('error', 'Konfirmasi penerimaan hanya dapat dilakukan jika barang dalam status dikirim.');
        }

        $request->validate([
            'receipt_proof' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'receipt_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'receipt_proof.image' => 'File bukti penerimaan harus berupa gambar (foto).',
            'receipt_proof.max' => 'Ukuran foto bukti penerimaan maksimal 5MB.',
        ]);

        $receiptProofPath = null;
        if ($request->hasFile('receipt_proof')) {
            $receiptProofPath = $request->file('receipt_proof')->store('merchandise_receipts', 'public');
        }

        $order->update([
            'status' => ProductOrder::STATUS_RECEIVED,
            'received_at' => now(),
            'receipt_proof_path' => $receiptProofPath,
            'receipt_notes' => $request->receipt_notes,
        ]);

        AuditLog::record(
            auth()->id(),
            'Konfirmasi Penerimaan Barang',
            "Menyatakan telah menerima barang untuk pesanan {$order->order_number}."
        );

        return back()->with(
            'success',
            'Alhamdulillah! Anda telah mengonfirmasi bahwa barang telah diterima dengan baik. Pesanan telah selesai. Silakan berikan review dan bintang 5 untuk produk yang Anda terima!'
        );
    }

    /**
     * Memberikan rating bintang (1-5) dan komentar ulasan untuk produk yang dibeli.
     */
    public function submitReview(Request $request, int $id): RedirectResponse
    {
        $order = ProductOrder::where('user_id', auth()->id())->findOrFail($id);

        if (! $order->canReview()) {
            return back()->with('error', 'Ulasan hanya dapat diberikan setelah barang selesai diterima.');
        }

        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rating.required' => 'Pilih bintang penilaian produk.',
            'comment.required' => 'Tuliskan komentar atau ulasan Anda mengenai produk ini.',
        ]);

        // Simpan atau update review
        ProductReview::updateOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
            ],
            [
                'rating' => (int) $request->rating,
                'comment' => $request->comment,
                'is_approved' => true,
            ]
        );

        AuditLog::record(
            auth()->id(),
            'Review Produk Merchandise',
            "Memberikan review {$request->rating} bintang pada produk id {$request->product_id} dari pesanan {$order->order_number}."
        );

        return back()->with('success', 'Terima kasih atas ulasan dan penilaian bintang yang Anda berikan!');
    }
}
