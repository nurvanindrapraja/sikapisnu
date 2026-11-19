@extends('layouts.admin')

@section('title', 'Kelola Pesanan ' . $order->order_number . ' - Bendahara PCNU')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('admin.product_orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
        </a>
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold text-dark mb-0">Pesanan: {{ $order->order_number }}</h4>
                <small class="text-muted">Dibuat pada: {{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</small>
            </div>
            <div>
                <span class="badge {{ $order->status_badge_class }} rounded-pill px-3 py-2 fw-bold fs-6">
                    {{ $order->status_label }}
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Items & Shipping Management -->
        <div class="col-lg-7">
            <!-- Items Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam me-2 text-success"></i> Rincian Barang Dipesan</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-muted text-uppercase">
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $it)
                                    <tr class="border-bottom">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-3 bg-light p-1 border text-center flex-shrink-0" style="width: 45px; height: 45px; overflow: hidden;">
                                                    @if($it->product && $it->product->image_url)
                                                        <img src="{{ $it->product->image_url }}" alt="{{ $it->product_name }}" class="w-100 h-100 object-fit-cover rounded-2">
                                                    @else
                                                        <i class="bi bi-box-seam text-success fs-4"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block">{{ $it->product_name }}</span>
                                                    @if($it->variant_note)
                                                        <small class="text-primary fw-semibold">{{ $it->variant_note }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold">{{ $it->quantity }}</td>
                                        <td class="text-end text-muted">Rp {{ number_format($it->price, 0, ',', '.') }}</td>
                                        <td class="text-end fw-bold text-dark">Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end text-muted pt-3">Total Belanja:</td>
                                    <td class="text-end fw-semibold text-dark pt-3">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end text-muted">Ongkir / Pengiriman:</td>
                                    <td class="text-end fw-semibold text-dark">
                                        {{ $order->shipping_cost > 0 ? 'Rp ' . number_format($order->shipping_cost, 0, ',', '.') : 'Gratis / Kantor' }}
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td colspan="3" class="text-end fw-bold text-dark">Grand Total Tagihan:</td>
                                    <td class="text-end fw-extrabold text-success fs-5">{{ $order->formatted_grand_total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Destination Address Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-geo-alt-fill me-2 text-danger"></i> Tujuan Pengiriman Pembeli</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Nama Penerima</small>
                            <strong class="text-dark">{{ $order->recipient_name }}</strong>
                            @if($order->user && $order->user->member)
                                <small class="text-muted d-block">NPA ISNU: {{ $order->user->member->member_number }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Kontak WhatsApp</small>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->recipient_phone) }}" target="_blank" class="text-success fw-bold text-decoration-none">
                                <i class="bi bi-whatsapp me-1"></i> {{ $order->recipient_phone }}
                            </a>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Alamat Pengiriman</small>
                            <span class="text-dark">{{ $order->shipping_address }}</span>
                        </div>
                        @if($order->notes)
                            <div class="col-12">
                                <small class="text-muted d-block">Catatan Pemesan</small>
                                <span class="text-secondary small fst-italic">"{{ $order->notes }}"</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Shipping Dispatch Card (Handled by Bendahara) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-truck me-2 text-primary"></i> Pengelolaan Pengiriman (Bendahara)</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">Tugas Bendahara NU</span>
                </div>
                <div class="card-body p-4">
                    @if($order->isPendingPayment() || $order->isPaymentUploaded())
                        <div class="alert alert-light border rounded-3 p-3 text-center text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i> Form pengiriman dapat diisi setelah pembayaran diverifikasi (Lunas).
                        </div>
                    @else
                        <!-- Form Update Pengiriman -->
                        <form action="{{ route('admin.product_orders.update_shipping', $order->id) }}" method="POST">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="courier_name" class="form-label fw-bold small text-dark">Nama Kurir / Ekspedisi <span class="text-danger">*</span></label>
                                    <input type="text" id="courier_name" name="courier_name" class="form-control rounded-3" value="{{ old('courier_name', $order->courier_name) }}" placeholder="Misal: JNE REG / J&T / Kurir Kantor PC ISNU" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tracking_number" class="form-label fw-bold small text-dark">Nomor Resi / Bukti Pengiriman</label>
                                    <input type="text" id="tracking_number" name="tracking_number" class="form-control rounded-3" value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="Nomor Resi Pelacakan">
                                </div>
                                <div class="col-12">
                                    <label for="shipping_notes" class="form-label fw-bold small text-dark">Catatan Pengiriman (Opsional)</label>
                                    <input type="text" id="shipping_notes" name="shipping_notes" class="form-control rounded-3" value="{{ old('shipping_notes', $order->shipping_notes) }}" placeholder="Keterangan estimasi sampai atau kontak pengantar...">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                                    <i class="bi bi-send-check me-1"></i> Simpan Info Pengiriman & Update Status (Dikirim)
                                </button>

                                @if($order->status === \App\Models\ProductOrder::STATUS_SHIPPED)
                                    <button type="button" class="btn btn-outline-success rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDirectReceipt">
                                        <i class="bi bi-check-all me-1"></i> Serah Terima Langsung di Kantor
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endif

                    @if($order->shipped_at)
                        <div class="mt-3 p-3 bg-light rounded-3 small">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            Barang dikirim pada: <strong>{{ $order->shipped_at->translatedFormat('d F Y, H:i') }} WIB</strong>
                            @if($order->shippedByUser)
                                oleh Pengurus <strong>{{ $order->shippedByUser->name }}</strong>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Reviews (If Submitted) -->
            @if($order->reviews->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-star-fill text-warning me-2"></i> Review & Penilaian Bintang Pembeli</h5>
                    </div>
                    <div class="card-body p-4">
                        @foreach($order->reviews as $rev)
                            <div class="p-3 bg-light rounded-3 mb-2 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark">{{ $rev->product->name ?? 'Produk' }}</strong>
                                    <div class="text-warning">
                                        @for($s = 1; $s <= 5; $s++)
                                            <i class="bi {{ $s <= $rev->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                        <span class="text-dark fw-bold ms-1">({{ $rev->rating }}/5)</span>
                                    </div>
                                </div>
                                <p class="text-secondary small mb-0">"{{ $rev->comment }}"</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Payment Verification & Receipt Proof -->
        <div class="col-lg-5">
            <!-- Payment Proof Verification Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cash-coin me-2 text-warning"></i> Verifikasi Bukti Pembayaran</h5>
                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">Bendahara</span>
                </div>
                <div class="card-body p-4">
                    <div class="p-3 rounded-3 bg-light border text-center mb-3">
                        <small class="text-muted d-block">Nominal Wajib Transfer</small>
                        <h3 class="fw-extrabold text-success mb-0">{{ $order->formatted_grand_total }}</h3>
                    </div>

                    @if($order->payment_proof_url)
                        <div class="mb-3 text-center">
                            <small class="text-muted d-block mb-2">Foto Bukti Transfer Diunggah Pembeli:</small>
                            <a href="{{ $order->payment_proof_url }}" target="_blank" class="d-block border rounded-3 p-2 bg-light">
                                <img src="{{ $order->payment_proof_url }}" alt="Bukti Transfer" class="img-fluid rounded-2" style="max-height: 280px; object-fit: contain;">
                                <small class="d-block mt-2 text-primary fw-semibold"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Foto Ukuran Penuh</small>
                            </a>
                            <small class="text-muted d-block mt-1">Diunggah: {{ $order->payment_uploaded_at?->translatedFormat('d M Y, H:i') }} WIB</small>
                        </div>

                        <!-- Verification Actions for Bendahara -->
                        @if($order->isPaymentUploaded() || $order->isPendingPayment())
                            <div class="p-3 rounded-4 bg-light border mt-3">
                                <strong class="d-block text-dark mb-2">Aksi Verifikasi Kas Bendahara:</strong>

                                <div class="d-grid gap-2">
                                    <form action="{{ route('admin.product_orders.verify_payment', $order->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold" onclick="return confirm('Apakah Anda sudah memeriksa mutasi rekening dan menyetujui bukti pembayaran ini?')">
                                            <i class="bi bi-check-circle-fill me-1"></i> Setujui Pembayaran (Lunas)
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalRejectPayment">
                                        <i class="bi bi-x-circle me-1"></i> Tolak Bukti Transfer
                                    </button>
                                </div>
                            </div>
                        @elseif($order->isPaid() || $order->isShipped() || $order->isReceived())
                            <div class="alert alert-success rounded-4 border-0 p-3 mb-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                    <strong>Pembayaran Terverifikasi Lunas</strong>
                                </div>
                                <small class="d-block text-success-emphasis">
                                    Diverifikasi pada: {{ $order->payment_verified_at?->translatedFormat('d M Y, H:i') }} WIB
                                    @if($order->verifier)
                                        oleh <strong>{{ $order->verifier->name }}</strong>
                                    @endif
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning rounded-4 border-0 p-3 text-center mb-0">
                            <i class="bi bi-hourglass-top display-6 text-warning mb-2 d-block"></i>
                            <strong class="d-block text-dark">Belum Ada Bukti Pembayaran</strong>
                            <p class="small text-muted mb-0">Pembeli belum mengunggah foto struk/bukti transfer pembayaran non-tunai.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Receipt Confirmation Status from Buyer -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box2-heart me-2 text-success"></i> Status Penerimaan Barang</h5>
                </div>
                <div class="card-body p-4">
                    @if($order->status === \App\Models\ProductOrder::STATUS_RECEIVED)
                        <div class="p-3 rounded-4 bg-success-subtle border border-success">
                            <div class="d-flex align-items-center gap-2 text-success mb-2">
                                <i class="bi bi-check2-circle fs-4"></i>
                                <strong class="fs-6">Barang Sudah Berhasil Diterima (Selesai)</strong>
                            </div>
                            <small class="text-muted d-block">Tanggal Diterima:</small>
                            <span class="fw-bold text-dark d-block mb-2">{{ $order->received_at?->translatedFormat('d F Y, H:i') }} WIB</span>

                            @if($order->receipt_notes)
                                <small class="text-muted d-block">Catatan Penerima:</small>
                                <p class="small text-dark fst-italic mb-2">"{{ $order->receipt_notes }}"</p>
                            @endif

                            @if($order->receipt_proof_url)
                                <small class="text-muted d-block mb-1">Foto Bukti Penerimaan Barang:</small>
                                <a href="{{ $order->receipt_proof_url }}" target="_blank" class="d-inline-block border rounded-3 p-1 bg-white">
                                    <img src="{{ $order->receipt_proof_url }}" alt="Foto Diterima" class="rounded-2" style="max-height: 140px; object-fit: cover;">
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-light border rounded-3 p-3 text-center text-muted mb-0">
                            <i class="bi bi-clock-history me-1"></i>
                            Menunggu konfirmasi penerimaan barang dari pihak pembeli/penerima.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tolak Pembayaran -->
<div class="modal fade" id="modalRejectPayment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-1"></i> Tolak Bukti Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.product_orders.verify_payment', $order->id) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">
                        Berikan alasan penolakan agar pembeli mengetahui perbaikan yang harus dilakukan (misal: nominal transfer kurang, struk blur, atau rekening tujuan salah).
                    </p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label fw-bold small text-dark">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea id="rejection_reason" name="rejection_reason" class="form-control rounded-3" rows="3" placeholder="Contoh: Foto struk transfer blur / nominal tidak sesuai total tagihan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Tolak Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Serah Terima Langsung -->
<div class="modal fade" id="modalDirectReceipt" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-check-all text-success me-1"></i> Selesaikan Serah Terima di Kantor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.product_orders.mark_received', $order->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">
                        Gunakan ini jika anggota / pembeli mengambil barang merchandise secara langsung di kantor Sekretariat PC ISNU Kota Surabaya.
                    </p>
                    <div class="mb-3">
                        <label for="receipt_notes_admin" class="form-label fw-bold small text-dark">Catatan Serah Terima</label>
                        <input type="text" id="receipt_notes_admin" name="receipt_notes" class="form-control rounded-3" value="Diserahterimakan langsung kepada pembeli di Sekretariat PC ISNU Surabaya.">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Konfirmasi Selesai</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
