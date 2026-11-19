@extends('layouts.member')

@section('title', 'Pesanan Merchandise Saya - SIKAP ISNU')
@section('header_title', 'Pesanan Merchandise Saya')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-bag-check-fill text-success me-2"></i> Pesanan Merchandise Saya
        </h3>
        <p class="text-secondary small mb-0">
            Daftar pemesanan atribut, seragam, batik, dan cinderamata resmi PC ISNU Kota Surabaya.
        </p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <a href="{{ route('store.index') }}" class="btn btn-isnu-primary rounded-pill px-4 py-2 fw-semibold">
            <i class="bi bi-shop me-1"></i> Beli Merchandise Lainnya
        </a>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="card border-0 rounded-4 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex gap-2 overflow-x-auto pb-1" style="scrollbar-width: thin;">
            @php
                $currentStatus = request('status', 'all');
                $statusTabs = [
                    'all' => 'Semua Pesanan',
                    \App\Models\ProductOrder::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
                    \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED => 'Menunggu Verifikasi',
                    \App\Models\ProductOrder::STATUS_PAID => 'Diproses / Lunas',
                    \App\Models\ProductOrder::STATUS_SHIPPED => 'Sedang Dikirim',
                    \App\Models\ProductOrder::STATUS_RECEIVED => 'Selesai Diterima',
                ];
            @endphp

            @foreach($statusTabs as $st => $label)
                <a href="{{ route('member.orders.index', ['status' => $st]) }}" 
                   class="btn btn-sm rounded-pill px-3 py-2 text-nowrap fw-semibold {{ $currentStatus === $st ? 'btn-success text-white' : 'btn-light text-secondary border' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Order List -->
@if($orders->isEmpty())
    <div class="card border-0 rounded-4 shadow-sm text-center py-5">
        <div class="card-body p-5">
            <i class="bi bi-bag-x text-muted display-1 opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold text-dark mb-2">Belum Ada Pesanan</h5>
            <p class="text-secondary small max-w-md mx-auto mb-4">
                Anda belum memiliki riwayat pemesanan atribut merchandise resmi ISNU.
            </p>
            <a href="{{ route('store.index') }}" class="btn btn-isnu-primary rounded-pill px-4">
                <i class="bi bi-shop me-1"></i> Jelajahi Toko Merchandise ISNU
            </a>
        </div>
    </div>
@else
    <div class="d-flex flex-column gap-3">
        @foreach($orders as $order)
            <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                <div class="card-header bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-receipt text-success fs-4"></i>
                        <div>
                            <span class="fw-bold text-dark d-block">No. Pesanan: {{ $order->order_number }}</span>
                            <small class="text-muted">{{ $order->created_at->translatedFormat('d F Y, H:i') }} WIB</small>
                        </div>
                    </div>
                    <div>
                        <span class="badge {{ $order->status_badge_class }} rounded-pill px-3 py-2 fw-bold">
                            {{ $order->status_label }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-7">
                            @foreach($order->items as $item)
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <div class="rounded-3 bg-light p-1 border text-center flex-shrink-0" style="width: 55px; height: 55px; overflow: hidden;">
                                        @if($item->product && $item->product->image_url)
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-100 h-100 object-fit-cover rounded-2">
                                        @else
                                            <i class="bi bi-box-seam text-success fs-3 lh-lg"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">{{ $item->product_name }}</h6>
                                        <small class="text-muted">
                                            {{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                            @if($item->variant_note)
                                                <span class="text-primary ms-1">({{ $item->variant_note }})</span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                            <div class="small text-muted mt-2">
                                <i class="bi bi-geo-alt me-1"></i> Penerima: <strong>{{ $order->recipient_name }}</strong> ({{ $order->recipient_phone }})
                            </div>
                        </div>

                        <div class="col-lg-5 text-lg-end pt-3 pt-lg-0 border-top border-lg-top-0">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Total Tagihan Non-Tunai</small>
                            <h4 class="fw-extrabold text-success mb-3">{{ $order->formatted_grand_total }}</h4>

                            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                <a href="{{ route('member.orders.show', $order->id) }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                    <i class="bi bi-eye me-1"></i> Rincian Pesanan
                                </a>

                                @if($order->canUploadPaymentProof())
                                    <a href="{{ route('member.orders.show', $order->id) }}#pembayaran" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold">
                                        <i class="bi bi-upload me-1"></i> Upload Bukti Transfer
                                    </a>
                                @elseif($order->canConfirmReceipt())
                                    <a href="{{ route('member.orders.show', $order->id) }}#konfirmasi" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                        <i class="bi bi-check-circle-fill me-1"></i> Konfirmasi Diterima
                                    </a>
                                @elseif($order->canReview())
                                    <a href="{{ route('member.orders.show', $order->id) }}#review" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold">
                                        <i class="bi bi-star-fill me-1"></i> Beri Bintang & Ulasan
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-4 d-flex justify-content-center">
            {{ $orders->links() }}
        </div>
    </div>
@endif
@endsection
