@extends('layouts.member')

@section('title', 'Rincian Pesanan ' . $order->order_number . ' - SIKAP ISNU')
@section('header_title', 'Rincian Pesanan ' . $order->order_number)

@section('styles')
<style>
    .stepper-wrapper {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    .stepper-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        text-align: center;
    }
    .stepper-item::before {
        position: absolute;
        content: "";
        border-bottom: 2px solid #e2e8f0;
        width: 100%;
        top: 20px;
        left: -50%;
        z-index: 1;
    }
    .stepper-item:first-child::before {
        content: none;
    }
    .stepper-item .step-counter {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 6px;
        transition: all 0.3s;
    }
    .stepper-item.completed .step-counter {
        background: var(--isnu-green);
        color: #ffffff;
    }
    .stepper-item.completed::before {
        border-color: var(--isnu-green);
    }
    .stepper-item.active .step-counter {
        background: #f59e0b;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
    }
    .star-rating-select {
        display: inline-flex;
        flex-direction: row-reverse;
        gap: 0.25rem;
    }
    .star-rating-select input {
        display: none;
    }
    .star-rating-select label {
        cursor: pointer;
        font-size: 1.8rem;
        color: #cbd5e1;
        transition: color 0.2s;
    }
    .star-rating-select label:hover,
    .star-rating-select label:hover ~ label,
    .star-rating-select input:checked ~ label {
        color: #f59e0b;
    }
</style>
@endsection

@section('content')
<div class="mb-4">
    <a href="{{ route('member.orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-2">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat Pesanan
    </a>
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-0">Pesanan: {{ $order->order_number }}</h3>
            <small class="text-muted">Tanggal Transaksi: {{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</small>
        </div>
        <div>
            <span id="memberOrderStatusBadge" class="badge {{ $order->status_badge_class }} rounded-pill px-3 py-2 fw-bold fs-6 shadow-sm">
                {{ $order->status_label }}
            </span>
        </div>
    </div>
</div>

<!-- Alert Messages (Toast Container & Regular Alert) -->
<div id="ajaxAlertContainer"></div>
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Top Loading Bar -->
<div id="memberOrderLoadingBar" class="progress rounded-pill mb-3" style="height: 4px; display: none;">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
</div>

<!-- Order Stepper Progress -->
<div class="card border-0 rounded-4 shadow-sm mb-4">
    <div class="card-body p-4">
        @php
            $steps = [
                'pending' => ['label' => 'Pesanan Dibuat', 'completed' => true],
                'uploaded' => ['label' => 'Bukti Bayar Diunggah', 'completed' => in_array($order->status, [\App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED, \App\Models\ProductOrder::STATUS_PAID, \App\Models\ProductOrder::STATUS_SHIPPED, \App\Models\ProductOrder::STATUS_RECEIVED])],
                'verified' => ['label' => 'Diverifikasi Bendahara', 'completed' => in_array($order->status, [\App\Models\ProductOrder::STATUS_PAID, \App\Models\ProductOrder::STATUS_SHIPPED, \App\Models\ProductOrder::STATUS_RECEIVED])],
                'shipped' => ['label' => 'Barang Dikirim', 'completed' => in_array($order->status, [\App\Models\ProductOrder::STATUS_SHIPPED, \App\Models\ProductOrder::STATUS_RECEIVED])],
                'received' => ['label' => 'Selesai Diterima', 'completed' => ($order->status === \App\Models\ProductOrder::STATUS_RECEIVED)],
            ];
        @endphp

        <div class="stepper-wrapper">
            <div id="stepPending" class="stepper-item {{ $steps['pending']['completed'] ? 'completed' : '' }}">
                <div class="step-counter"><i class="bi bi-cart-check"></i></div>
                <small class="fw-semibold text-dark">Dipesan</small>
            </div>
            <div id="stepUploaded" class="stepper-item {{ $steps['uploaded']['completed'] ? 'completed' : ($order->status === \App\Models\ProductOrder::STATUS_PENDING_PAYMENT ? 'active' : '') }}">
                <div class="step-counter"><i class="bi bi-credit-card"></i></div>
                <small class="fw-semibold text-dark">Upload Bayar</small>
            </div>
            <div id="stepVerified" class="stepper-item {{ $steps['verified']['completed'] ? 'completed' : ($order->status === \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED ? 'active' : '') }}">
                <div class="step-counter"><i class="bi bi-patch-check"></i></div>
                <small class="fw-semibold text-dark">Verifikasi Kas</small>
            </div>
            <div id="stepShipped" class="stepper-item {{ $steps['shipped']['completed'] ? 'completed' : ($order->status === \App\Models\ProductOrder::STATUS_PAID ? 'active' : '') }}">
                <div class="step-counter"><i class="bi bi-truck"></i></div>
                <small class="fw-semibold text-dark">Pengiriman</small>
            </div>
            <div id="stepReceived" class="stepper-item {{ $steps['received']['completed'] ? 'completed' : ($order->status === \App\Models\ProductOrder::STATUS_SHIPPED ? 'active' : '') }}">
                <div class="step-counter"><i class="bi bi-box2-heart"></i></div>
                <small class="fw-semibold text-dark">Selesai</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Order Items & Delivery Status -->
    <div class="col-lg-7">
        <!-- Card Items -->
        <div class="card border-0 rounded-4 shadow-sm mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-basket me-2 text-success"></i> Rincian Barang Dipesan</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <thead class="table-light rounded-3">
                            <tr class="small text-muted text-uppercase">
                                <th>Produk</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr class="border-bottom">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-light p-1 border text-center flex-shrink-0" style="width: 50px; height: 50px; overflow: hidden;">
                                                @if($item->product && $item->product->image_url)
                                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-100 h-100 object-fit-cover rounded-2">
                                                @else
                                                    <i class="bi bi-box-seam text-success fs-4 lh-lg"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0">{{ $item->product_name }}</h6>
                                                @if($item->variant_note)
                                                    <small class="text-primary fw-semibold"><i class="bi bi-tag me-1"></i> {{ $item->variant_note }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center fw-semibold">{{ $item->quantity }}</td>
                                    <td class="text-end text-muted">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end text-muted pt-3">Total Belanja:</td>
                                <td class="text-end fw-semibold text-dark pt-3">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end text-muted">Biaya Pengiriman:</td>
                                <td class="text-end fw-semibold text-dark">
                                    {{ $order->shipping_cost > 0 ? 'Rp ' . number_format($order->shipping_cost, 0, ',', '.') : 'Gratis / Kantor PCNU' }}
                                </td>
                            </tr>
                            <tr class="border-top">
                                <td colspan="3" class="text-end fw-bold fs-6 text-dark">Total Pembayaran Non-Tunai:</td>
                                <td class="text-end fw-extrabold fs-5 text-success">{{ $order->formatted_grand_total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Shipping & Receipt Confirmation Card -->
        <div class="card border-0 rounded-4 shadow-sm mb-4" id="konfirmasi">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-truck me-2 text-primary"></i> Pengiriman & Penerimaan Barang</h5>
                <span id="shippingStatusBadge" class="badge {{ $order->status === \App\Models\ProductOrder::STATUS_RECEIVED ? 'bg-success' : ($order->status === \App\Models\ProductOrder::STATUS_SHIPPED ? 'bg-primary' : 'bg-secondary') }} rounded-pill px-3 py-1">
                    {{ $order->status_label }}
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Penerima & Kontak</small>
                        <strong class="text-dark d-block">{{ $order->recipient_name }}</strong>
                        <span class="small text-muted">{{ $order->recipient_phone }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Ekspedisi / Kurir</small>
                        <strong class="text-dark d-block">{{ $order->courier_name ?: 'Sedang dipersiapkan oleh Bendahara' }}</strong>
                        @if($order->tracking_number)
                            <span class="badge bg-secondary-subtle text-dark border">Resi: {{ $order->tracking_number }}</span>
                        @endif
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Alamat Tujuan Lengkap</small>
                        <span class="text-dark small">{{ $order->shipping_address }}</span>
                    </div>
                    @if($order->notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Catatan Pemesan</small>
                            <span class="text-secondary small fst-italic">"{{ $order->notes }}"</span>
                        </div>
                    @endif
                </div>

                <!-- Konfirmasi Penerimaan Barang oleh Pembeli -->
                <div id="sectionConfirmReceipt" class="{{ $order->canConfirmReceipt() ? '' : 'd-none' }}">
                    <div class="p-4 rounded-4 bg-light border border-success mt-3">
                        <div class="d-flex align-items-center gap-2 mb-2 text-success">
                            <i class="bi bi-box2-heart fs-4"></i>
                            <h6 class="fw-bold mb-0">Konfirmasi Barang Diterima</h6>
                        </div>
                        <p class="small text-secondary mb-3">
                            Barang Anda telah dikirim oleh Pengurus/Bendahara. Jika Anda telah menerima paket merchandise tersebut secara fisik, silakan konfirmasi bahwa barang sudah berhasil diterima (selesai).
                        </p>

                        <form id="formConfirmReceived" action="{{ route('member.orders.confirm_received', $order->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="receipt_proof" class="form-label small fw-semibold text-dark">
                                    Foto Bukti Penerimaan Barang (Opsional)
                                </label>
                                <input type="file" id="receipt_proof" name="receipt_proof" class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Unggah foto barang / unboxing jika ada (Format: JPG, PNG, WEBP maks 5MB).</small>

                                <!-- Live Preview for Receipt Proof -->
                                <div id="receiptProofPreviewContainer" class="mt-2 d-none">
                                    <div class="position-relative d-inline-block border rounded-3 p-1 bg-white">
                                        <img id="receiptProofPreview" src="" alt="Preview Bukti Terima" class="rounded-2" style="max-height: 120px; object-fit: contain;">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="receipt_notes" class="form-label small fw-semibold text-dark">
                                    Catatan Penerimaan (Opsional)
                                </label>
                                <input type="text" id="receipt_notes" name="receipt_notes" class="form-control form-control-sm" placeholder="Contoh: Paket diterima dalam kondisi baik & lengkap.">
                            </div>

                            <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalConfirmReceiptOrder">
                                <i class="bi bi-check-circle-fill me-1"></i> Saya Sudah Menerima Barang Ini
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Status Barang Selesai Diterima -->
                <div id="sectionOrderCompleted" class="{{ $order->status === \App\Models\ProductOrder::STATUS_RECEIVED ? '' : 'd-none' }}">
                    <div class="p-3 rounded-4 bg-success-subtle border border-success mt-3">
                        <div class="d-flex align-items-center gap-2 text-success mb-1">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <h6 class="fw-bold mb-0">Pesanan Selesai</h6>
                        </div>
                        <p class="small text-success-emphasis mb-1">
                            Barang telah berhasil diterima pada <strong id="memberReceivedDateText">{{ $order->received_at?->translatedFormat('d F Y, H:i') }} WIB</strong>.
                        </p>
                        <p class="small text-muted mb-2 {{ $order->receipt_notes ? '' : 'd-none' }}" id="memberReceiptNotesP">
                            <em id="memberReceiptNotesText">"{{ $order->receipt_notes }}"</em>
                        </p>

                        <div id="memberReceiptProofContainer" class="{{ $order->receipt_proof_url ? '' : 'd-none' }} mt-2">
                            <small class="text-muted d-block mb-1">Foto Bukti Penerimaan:</small>
                            <button type="button" class="btn p-0 border-0" data-bs-toggle="modal" data-bs-target="#modalViewReceiptProof">
                                <img id="memberReceiptProofImg" src="{{ $order->receipt_proof_url ?? '' }}" alt="Bukti Penerimaan" class="rounded-3 border" style="max-height: 120px; object-fit: cover;">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review & Rating Bintang 5 Section -->
        <div class="card border-0 rounded-4 shadow-sm mb-4 {{ $order->status === \App\Models\ProductOrder::STATUS_RECEIVED ? '' : 'd-none' }}" id="sectionReviews">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-star-fill text-warning me-2"></i> Review & Penilaian Bintang 5
                </h5>
            </div>
            <div class="card-body p-4">
                <p class="small text-secondary mb-4">
                    Sebagai pembeli, penilaian bintang dan komentar Anda sangat berarti bagi pengembangan produk merchandise resmi PC ISNU Kota Surabaya.
                </p>

                @foreach($order->items as $item)
                    @php
                        $existingReview = $order->reviews->firstWhere('product_id', $item->product_id);
                    @endphp

                    <div class="p-3 rounded-4 bg-light border mb-3">
                        <h6 class="fw-bold text-dark mb-2">{{ $item->product_name }}</h6>

                        <form class="form-submit-review" action="{{ route('member.orders.submit_review', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark d-block mb-1">Penilaian Bintang (1 - 5 Bintang):</label>
                                <div class="star-rating-select">
                                    <input type="radio" id="star5_{{ $item->id }}" name="rating" value="5" {{ ($existingReview?->rating == 5 || ! $existingReview) ? 'checked' : '' }} required>
                                    <label for="star5_{{ $item->id }}" title="5 Bintang (Sangat Puas)">★</label>

                                    <input type="radio" id="star4_{{ $item->id }}" name="rating" value="4" {{ $existingReview?->rating == 4 ? 'checked' : '' }}>
                                    <label for="star4_{{ $item->id }}" title="4 Bintang (Puas)">★</label>

                                    <input type="radio" id="star3_{{ $item->id }}" name="rating" value="3" {{ $existingReview?->rating == 3 ? 'checked' : '' }}>
                                    <label for="star3_{{ $item->id }}" title="3 Bintang (Cukup)">★</label>

                                    <input type="radio" id="star2_{{ $item->id }}" name="rating" value="2" {{ $existingReview?->rating == 2 ? 'checked' : '' }}>
                                    <label for="star2_{{ $item->id }}" title="2 Bintang (Kurang)">★</label>

                                    <input type="radio" id="star1_{{ $item->id }}" name="rating" value="1" {{ $existingReview?->rating == 1 ? 'checked' : '' }}>
                                    <label for="star1_{{ $item->id }}" title="1 Bintang (Kecewa)">★</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="comment_{{ $item->id }}" class="form-label small fw-bold text-dark mb-1">Ulasan / Komentar Anda:</label>
                                <textarea id="comment_{{ $item->id }}" name="comment" class="form-control rounded-3" rows="3" placeholder="Tuliskan ulasan mengenai kualitas bahan, ukuran, jahitan, atau kemasan produk ini..." required>{{ old('comment', $existingReview?->comment) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-warning text-dark btn-sm rounded-pill px-4 fw-bold btn-submit-review">
                                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                <i class="bi bi-send-fill me-1"></i> <span class="btn-label">{{ $existingReview ? 'Perbarui Ulasan Bintang' : 'Kirim Ulasan & Bintang 5' }}</span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right Column: Non-Cash Payment Instructions & Proof Upload -->
    <div class="col-lg-5">
        <div class="card border-0 rounded-4 shadow-sm mb-4" id="pembayaran">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-credit-card-2-front-fill me-2 text-warning"></i> Pembayaran Non-Tunai
                </h5>
            </div>
            <div class="card-body p-4">
                <!-- Nominal Tagihan -->
                <div class="p-3 rounded-3 bg-light border mb-4 text-center">
                    <small class="text-muted d-block" style="font-size: 0.75rem;">Total Tagihan Non-Tunai</small>
                    <h3 class="fw-extrabold text-success mb-0">{{ $order->formatted_grand_total }}</h3>
                </div>

                <!-- Rekening Tujuan Transfer PC ISNU Surabaya -->
                <div class="p-3 rounded-4 mb-4" style="background: linear-gradient(135deg, #003e21 0%, #006837 100%); color: white;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning text-dark fw-bold">Rekening Resmi Kas PC ISNU</span>
                        <i class="bi bi-bank fs-4 text-warning"></i>
                    </div>
                    <small class="text-white-50 d-block">Bank Tujuan Transfer:</small>
                    <h5 class="fw-bold text-white mb-2">Bank Syariah Indonesia (BSI)</h5>
                    
                    <div class="bg-white p-2 px-3 rounded-3 text-dark d-flex justify-content-between align-items-center mb-2">
                        <strong class="fs-5 text-dark" id="accountNumber">7123456789</strong>
                        <button class="btn btn-sm btn-outline-success rounded-pill py-0 px-2" type="button" onclick="navigator.clipboard.writeText('7123456789'); alert('Nomor rekening disalin!');">
                            <i class="bi bi-clipboard"></i> Salin
                        </button>
                    </div>
                    <small class="text-white-50 d-block">Atas Nama:</small>
                    <strong class="text-white">PC ISNU KOTA SURABAYA</strong>
                </div>

                <!-- Info Penolakan Pembayaran Jika Ditolak -->
                <div id="rejectionAlert" class="{{ $order->rejection_reason ? '' : 'd-none' }}">
                    <div class="alert alert-warning rounded-4 border-0 shadow-sm p-3 mb-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="d-block text-dark">Catatan dari Bendahara:</strong>
                                <span class="small text-secondary" id="rejectionText">{{ $order->rejection_reason }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Bukti Transfer Form -->
                <div id="uploadPaymentSection" class="{{ $order->canUploadPaymentProof() ? '' : 'd-none' }}">
                    <form id="formUploadPayment" action="{{ route('member.orders.upload_payment', $order->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="payment_proof" class="form-label fw-bold small text-dark">
                                Unggah Foto Bukti Transfer <span class="text-danger">*</span>
                            </label>
                            <input type="file" id="payment_proof" name="payment_proof" class="form-control rounded-3" accept="image/*" required>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Format: JPG, PNG, WEBP (maksimal 5MB). Pastikan struk atau bukti transfer terlihat jelas.</small>

                            <!-- Live Preview Foto Sebelum Upload -->
                            <div id="paymentProofPreviewContainer" class="mt-3 p-3 bg-light rounded-3 border d-none text-center">
                                <small class="text-muted d-block fw-semibold mb-2">Preview Foto Bukti Transfer:</small>
                                <img id="paymentProofPreview" src="" alt="Preview Bukti" class="img-fluid rounded-2 shadow-sm border mb-2" style="max-height: 180px; object-fit: contain;">
                                <div class="d-flex justify-content-between align-items-center small text-muted px-2">
                                    <span id="paymentProofFileName" class="text-truncate" style="max-width: 180px;"></span>
                                    <button type="button" id="btnCancelPaymentProof" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0" style="font-size: 0.75rem;">
                                        <i class="bi bi-x-circle me-1"></i> Ganti File
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitPaymentProof" class="btn btn-isnu-primary w-100 rounded-pill py-2 fw-bold">
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-upload me-1"></i> Unggah Bukti Pembayaran
                        </button>
                    </form>
                </div>

                <!-- Tampilan Bukti yang Sudah Diunggah -->
                <div id="uploadedProofDisplay" class="{{ $order->payment_proof_url ? '' : 'd-none' }} mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="fw-bold text-dark">Bukti Transfer Terunggah:</small>
                        <small class="text-muted" id="uploadedProofDate">{{ $order->payment_uploaded_at?->translatedFormat('d M Y, H:i') }}</small>
                    </div>
                    <button type="button" class="btn p-0 border-0 d-block w-100 text-center" data-bs-toggle="modal" data-bs-target="#modalViewPaymentProof">
                        <div class="border rounded-3 p-2 bg-light shadow-sm">
                            <img id="uploadedProofImg" src="{{ $order->payment_proof_url ?? '' }}" alt="Bukti Transfer" class="img-fluid rounded-2" style="max-height: 220px; object-fit: contain;">
                            <small class="d-block mt-2 text-primary fw-semibold"><i class="bi bi-arrows-fullscreen me-1"></i> Klik untuk Perbesar Bukti Transfer (Modal)</small>
                        </div>
                    </button>

                    <div id="proofUnderReviewAlert" class="{{ $order->status === \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED ? '' : 'd-none' }} alert alert-info py-2 px-3 rounded-3 small mt-3 mb-0">
                        <i class="bi bi-hourglass-split me-1 text-primary"></i>
                        Bukti transfer sedang ditinjau dan diverifikasi oleh <strong>Bendahara / Wakil Bendahara PCNU</strong>.
                    </div>

                    <div id="proofVerifiedAlert" class="{{ in_array($order->status, [\App\Models\ProductOrder::STATUS_PAID, \App\Models\ProductOrder::STATUS_SHIPPED, \App\Models\ProductOrder::STATUS_RECEIVED]) ? '' : 'd-none' }} alert alert-success py-2 px-3 rounded-3 small mt-3 mb-0">
                        <i class="bi bi-check-circle-fill me-1 text-success"></i>
                        Pembayaran non-tunai telah diverifikasi dan disetujui oleh <strong>Bendahara</strong>.
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <small class="text-muted d-block mb-1"><i class="bi bi-info-circle me-1 text-success"></i> Pengelolaan Pesanan & Ekspedisi:</small>
                    <p class="small text-secondary mb-0">
                        Seluruh verifikasi pembayaran dan ekspedisi pengiriman merchandise dilakukan langsung oleh <strong>Bendahara / Wakil Bendahara PC ISNU Kota Surabaya</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Popup Lihat Bukti Transfer Member -->
<div class="modal fade" id="modalViewPaymentProof" tabindex="-1" aria-labelledby="modalViewPaymentProofLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalViewPaymentProofLabel">
                    <i class="bi bi-image text-success me-2"></i> Bukti Transfer Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-2 border rounded-4 bg-light mb-3">
                    <img id="modalViewPaymentProofImg" src="{{ $order->payment_proof_url ?? '' }}" alt="Bukti Transfer" class="img-fluid rounded-3 shadow-sm" style="max-height: 480px; object-fit: contain;">
                </div>
                <div class="d-flex justify-content-between align-items-center small text-muted px-2">
                    <span>No. Pesanan: <strong>{{ $order->order_number }}</strong></span>
                    <a id="modalViewPaymentProofLink" href="{{ $order->payment_proof_url ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Ukuran Penuh
                    </a>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Popup Lihat Bukti Penerimaan Barang Member -->
<div class="modal fade" id="modalViewReceiptProof" tabindex="-1" aria-labelledby="modalViewReceiptProofLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalViewReceiptProofLabel">
                    <i class="bi bi-box2-heart text-success me-2"></i> Foto Bukti Penerimaan Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-2 border rounded-4 bg-light mb-3">
                    <img id="modalViewReceiptProofImg" src="{{ $order->receipt_proof_url ?? '' }}" alt="Bukti Penerimaan" class="img-fluid rounded-3 shadow-sm" style="max-height: 480px; object-fit: contain;">
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Terima Barang (Menggantikan confirm()) -->
<div class="modal fade" id="modalConfirmReceiptOrder" tabindex="-1" aria-labelledby="modalConfirmReceiptLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-success" id="modalConfirmReceiptLabel">
                    <i class="bi bi-check2-circle me-2"></i> Konfirmasi Penerimaan Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="rounded-circle bg-success-subtle text-success mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                        <i class="bi bi-box-seam fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Paket Telah Anda Terima?</h6>
                    <small class="text-muted">No. Pesanan: {{ $order->order_number }}</small>
                </div>
                <p class="small text-secondary mb-0">
                    Apakah Anda yakin telah menerima paket barang merchandise ini secara fisik dengan kondisi baik dan lengkap? Status pesanan akan diselesaikan dan Anda dapat memberikan ulasan bintang 5.
                </p>
            </div>
            <div class="modal-footer border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnExecuteConfirmReceived" class="btn btn-success rounded-pill px-4 fw-bold">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <i class="bi bi-check-circle me-1"></i> Ya, Selesai Diterima
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const alertContainer = document.getElementById('ajaxAlertContainer');
    const loadingBar = document.getElementById('memberOrderLoadingBar');
    const statusBadge = document.getElementById('memberOrderStatusBadge');

    function showNotification(message, type = 'success') {
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 toast-slide-in" role="alert">
                <i class="bi ${icon} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertContainer.innerHTML = alertHtml;
        setTimeout(() => {
            const el = alertContainer.querySelector('.alert');
            if (el) bootstrap.Alert.getOrCreateInstance(el).close();
        }, 5000);
    }

    function setProgressBar(show) {
        loadingBar.style.display = show ? 'block' : 'none';
    }

    // 1. Live Preview Bukti Transfer Saat Memilih File
    const inputPaymentProof = document.getElementById('payment_proof');
    const previewContainer = document.getElementById('paymentProofPreviewContainer');
    const previewImg = document.getElementById('paymentProofPreview');
    const previewFileName = document.getElementById('paymentProofFileName');
    const btnCancelProof = document.getElementById('btnCancelPaymentProof');

    if (inputPaymentProof) {
        inputPaymentProof.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const sizeKb = Math.round(file.size / 1024);
                previewFileName.textContent = `${file.name} (${sizeKb} KB)`;

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('d-none');
            }
        });
    }

    if (btnCancelProof) {
        btnCancelProof.addEventListener('click', function() {
            inputPaymentProof.value = '';
            previewImg.src = '';
            previewContainer.classList.add('d-none');
        });
    }

    // Live Preview Bukti Terima Barang Saat Memilih File
    const inputReceiptProof = document.getElementById('receipt_proof');
    const receiptPreviewContainer = document.getElementById('receiptProofPreviewContainer');
    const receiptPreviewImg = document.getElementById('receiptProofPreview');

    if (inputReceiptProof) {
        inputReceiptProof.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    receiptPreviewImg.src = e.target.result;
                    receiptPreviewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                receiptPreviewContainer.classList.add('d-none');
            }
        });
    }

    // 2. AJAX: Upload Bukti Transfer Form
    const formUploadPayment = document.getElementById('formUploadPayment');
    const btnSubmitPayment = document.getElementById('btnSubmitPaymentProof');

    if (formUploadPayment) {
        formUploadPayment.addEventListener('submit', function(e) {
            e.preventDefault();
            btnSubmitPayment.disabled = true;
            const spinner = btnSubmitPayment.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            setProgressBar(true);

            const formData = new FormData(formUploadPayment);

            fetch(formUploadPayment.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => { throw errData; });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');

                    // Update Status Badge
                    if (data.order) {
                        statusBadge.className = `badge ${data.order.status_badge_class} rounded-pill px-3 py-2 fw-bold fs-6 shadow-sm`;
                        statusBadge.textContent = data.order.status_label;
                    }

                    // Update Stepper
                    const stepUp = document.getElementById('stepUploaded');
                    const stepVer = document.getElementById('stepVerified');
                    if (stepUp) {
                        stepUp.classList.remove('active');
                        stepUp.classList.add('completed');
                    }
                    if (stepVer) {
                        stepVer.classList.add('active');
                    }

                    // Hide rejection alert if any
                    document.getElementById('rejectionAlert')?.classList.add('d-none');

                    // Update uploaded display
                    const display = document.getElementById('uploadedProofDisplay');
                    display.classList.remove('d-none');
                    document.getElementById('uploadedProofImg').src = data.proof_url;
                    document.getElementById('modalViewPaymentProofImg').src = data.proof_url;
                    document.getElementById('modalViewPaymentProofLink').href = data.proof_url;
                    document.getElementById('uploadedProofDate').textContent = data.uploaded_at;
                    document.getElementById('proofUnderReviewAlert')?.classList.remove('d-none');

                    // Reset and hide form preview
                    previewContainer.classList.add('d-none');
                    formUploadPayment.reset();
                } else {
                    showNotification(data.message || 'Gagal mengunggah bukti pembayaran.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification(err.message || 'Terjadi kesalahan saat mengunggah bukti transfer.', 'danger');
            })
            .finally(() => {
                btnSubmitPayment.disabled = false;
                spinner.classList.add('d-none');
                setProgressBar(false);
            });
        });
    }

    // 3. AJAX: Confirm Received Modal Execution
    const formConfirmReceived = document.getElementById('formConfirmReceived');
    const btnExecuteConfirm = document.getElementById('btnExecuteConfirmReceived');

    if (btnExecuteConfirm && formConfirmReceived) {
        btnExecuteConfirm.addEventListener('click', function() {
            btnExecuteConfirm.disabled = true;
            const spinner = btnExecuteConfirm.querySelector('.spinner-border');
            spinner.classList.remove('d-none');
            setProgressBar(true);

            const formData = new FormData(formConfirmReceived);

            fetch(formConfirmReceived.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => { throw errData; });
                }
                return res.json();
            })
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmReceiptOrder')).hide();
                if (data.success) {
                    showNotification(data.message, 'success');

                    // Update Badge
                    if (data.order) {
                        statusBadge.className = `badge ${data.order.status_badge_class} rounded-pill px-3 py-2 fw-bold fs-6 shadow-sm`;
                        statusBadge.textContent = data.order.status_label;
                    }

                    // Update Stepper
                    const stepShip = document.getElementById('stepShipped');
                    const stepRec = document.getElementById('stepReceived');
                    if (stepShip) {
                        stepShip.classList.remove('active');
                        stepShip.classList.add('completed');
                    }
                    if (stepRec) {
                        stepRec.classList.add('completed');
                    }

                    // Switch Confirm section to Completed section
                    document.getElementById('sectionConfirmReceipt')?.classList.add('d-none');
                    const completedSection = document.getElementById('sectionOrderCompleted');
                    completedSection?.classList.remove('d-none');
                    document.getElementById('memberReceivedDateText').textContent = data.received_at;

                    if (data.receipt_notes) {
                        document.getElementById('memberReceiptNotesP')?.classList.remove('d-none');
                        document.getElementById('memberReceiptNotesText').textContent = `"${data.receipt_notes}"`;
                    }

                    if (data.receipt_proof_url) {
                        document.getElementById('memberReceiptProofContainer')?.classList.remove('d-none');
                        document.getElementById('memberReceiptProofImg').src = data.receipt_proof_url;
                        document.getElementById('modalViewReceiptProofImg').src = data.receipt_proof_url;
                    }

                    // Reveal review section
                    document.getElementById('sectionReviews')?.classList.remove('d-none');
                } else {
                    showNotification(data.message || 'Gagal mengonfirmasi penerimaan barang.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification(err.message || 'Terjadi kesalahan saat memproses konfirmasi.', 'danger');
            })
            .finally(() => {
                btnExecuteConfirm.disabled = false;
                spinner.classList.add('d-none');
                setProgressBar(false);
            });
        });
    }

    // 4. AJAX: Review Forms Submission
    document.querySelectorAll('.form-submit-review').forEach(reviewForm => {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn-submit-review');
            const spinner = btn.querySelector('.spinner-border');
            const label = btn.querySelector('.btn-label');
            const originalLabel = label.textContent;

            btn.disabled = true;
            spinner.classList.remove('d-none');
            setProgressBar(true);

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => { throw errData; });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    label.textContent = 'Perbarui Ulasan Bintang';
                } else {
                    showNotification(data.message || 'Gagal menyimpan ulasan.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification(err.message || 'Terjadi kesalahan saat mengirim ulasan.', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
                setProgressBar(false);
            });
        });
    });
});
</script>
@endsection
