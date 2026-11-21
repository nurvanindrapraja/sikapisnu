@php
    $isMemberLayout = request()->routeIs('member.*') || (auth()->check() && !auth()->user()->isAdmin() && !request()->has('public'));
    $panelLayout = (auth()->check() && auth()->user()->isAdmin()) ? 'layouts.admin' : 'layouts.member';
    $storeIndexRoute = $isMemberLayout ? 'member.store.index' : 'store.index';
    $storeShowRoute = $isMemberLayout ? 'member.store.show' : 'store.show';
@endphp
@extends($isMemberLayout ? $panelLayout : 'layouts.app')

@section('title', $product->name . ' - Toko Merchandise ISNU')
@section('header_title', $product->name)

@section('styles')
<style>
    .product-detail-img {
        width: 100%;
        max-height: 480px;
        object-fit: cover;
        border-radius: 1.25rem;
        background: #f8fafc;
    }
    .purchase-box {
        position: sticky;
        top: 90px;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        background: #ffffff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        padding: 1.75rem;
    }
    .star-rating-lg {
        color: #f59e0b;
        font-size: 1.2rem;
    }
    .review-card {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 1.25rem;
        margin-bottom: 1.25rem;
    }
    .review-card:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .user-avatar-initial {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, var(--isnu-green), var(--isnu-green-dark));
        color: white;
        border-radius: 50%;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumbs -->
<div class="{{ $isMemberLayout ? 'card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white' : 'bg-light border-bottom py-3 mb-4' }}">
    <div class="{{ $isMemberLayout ? '' : 'container px-3 px-sm-4' }}">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                @if($isMemberLayout)
                    <li class="breadcrumb-item"><a href="{{ route('member.dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('member.store.index') }}" class="text-decoration-none text-muted">Toko Merchandise</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('member.store.index', ['category' => $product->category]) }}" class="text-decoration-none text-muted">{{ $product->category_label }}</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('store.index') }}" class="text-decoration-none text-muted">Toko Merchandise</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('store.index', ['category' => $product->category]) }}" class="text-decoration-none text-muted">{{ $product->category_label }}</a></li>
                @endif
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">{{ Str::limit($product->name, 35) }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="{{ $isMemberLayout ? 'mb-4' : 'py-5' }}">
    <div class="{{ $isMemberLayout ? '' : 'container px-3 px-sm-4' }}">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-5">
            <!-- Left Column: Media & Product Details -->
            <div class="col-lg-7">
                <!-- Product Image -->
                <div class="bg-white p-2 rounded-4 border shadow-sm mb-4 text-center">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-detail-img img-fluid">
                    @else
                        <div class="p-5 d-flex flex-column align-items-center justify-content-center bg-light rounded-4" style="min-height: 350px;">
                            <i class="bi bi-box-seam display-1 text-success opacity-50 mb-3"></i>
                            <h5 class="fw-bold text-dark">Merchandise Resmi ISNU</h5>
                            <span class="badge bg-success-subtle text-success">{{ $product->category_label }}</span>
                        </div>
                    @endif
                </div>

                <!-- Product Description Tabs -->
                <div class="bg-white p-4 rounded-4 border shadow-sm mb-4">
                    <ul class="nav nav-tabs border-bottom" id="productTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab">
                                <i class="bi bi-file-text me-1"></i> Deskripsi & Rincian
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                                <i class="bi bi-star-fill text-warning me-1"></i> Ulasan & Penilaian ({{ $product->reviews->count() }})
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4" id="productTabContent">
                        <!-- Tab 1: Deskripsi -->
                        <div class="tab-pane fade show active" id="desc" role="tabpanel">
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                                        <i class="bi bi-tag-fill text-success fs-3"></i>
                                        <div>
                                            <small class="text-muted d-block">Kategori Atribut</small>
                                            <strong class="text-dark">{{ $product->category_label }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-3 bg-light border d-flex align-items-center gap-3">
                                        <i class="bi bi-box-seam-fill text-primary fs-3"></i>
                                        <div>
                                            <small class="text-muted d-block">Perkiraan Berat</small>
                                            <strong class="text-dark">{{ $product->weight_grams }} gram</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-2">Penjelasan Produk:</h6>
                            <div class="text-secondary lh-lg" style="white-space: pre-line;">
                                {{ $product->description ?: 'Atribut resmi PC ISNU Kota Surabaya. Dibuat dengan bahan berkualitas tinggi dan desain proporsional sesuai pedoman atribut Ikatan Sarjana Nahdlatul Ulama.' }}
                            </div>
                        </div>

                        <!-- Tab 2: Ulasan Bintang 5 Pembeli -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <!-- Overall Rating Summary -->
                            <div class="p-3 rounded-4 bg-light border mb-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-center text-sm-start">
                                <div>
                                    <div class="display-4 fw-extrabold text-dark lh-1 mb-1">
                                        {{ number_format($product->average_rating, 1) }}
                                        <span class="fs-4 text-muted fw-normal">/ 5.0</span>
                                    </div>
                                    <div class="star-rating-lg mb-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= round($product->average_rating))
                                                <i class="bi bi-star-fill text-warning"></i>
                                            @else
                                                <i class="bi bi-star text-muted opacity-50"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <small class="text-muted">Berdasarkan {{ $product->reviews->count() }} ulasan anggota pembeli</small>
                                </div>
                                <div class="text-sm-end">
                                    <span class="badge bg-success px-3 py-2 rounded-pill fw-semibold">
                                        <i class="bi bi-patch-check-fill me-1"></i> Ulasan Terverifikasi Pembeli
                                    </span>
                                </div>
                            </div>

                            <!-- List Reviews -->
                            @if($product->reviews->isEmpty())
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-chat-square-quote display-4 mb-2 d-block opacity-50"></i>
                                    <p class="mb-0">Belum ada ulasan untuk barang ini. Pembeli yang telah menerima barang dapat memberikan penilaian bintang 5 & komentar di menu Pesanan.</p>
                                </div>
                            @else
                                <div class="review-list">
                                    @foreach($product->reviews as $review)
                                        <div class="review-card">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="user-avatar-initial flex-shrink-0">
                                                    {{ strtoupper(substr($review->user->name ?? 'A', 0, 1)) }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-1">
                                                        <h6 class="fw-bold text-dark mb-0">{{ $review->user->name ?? 'Anggota ISNU' }}</h6>
                                                        <small class="text-muted">{{ $review->created_at->translatedFormat('d M Y') }}</small>
                                                    </div>
                                                    <div class="text-warning mb-2" style="font-size: 0.85rem;">
                                                        @for($s = 1; $s <= 5; $s++)
                                                            @if($s <= $review->rating)
                                                                <i class="bi bi-star-fill"></i>
                                                            @else
                                                                <i class="bi bi-star text-muted opacity-50"></i>
                                                            @endif
                                                        @endfor
                                                        <span class="text-dark fw-bold ms-1" style="font-size: 0.8rem;">({{ $review->rating }}/5)</span>
                                                    </div>
                                                    <p class="text-secondary mb-0 small lh-base">
                                                        {{ $review->comment }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Purchase Form & Membership Check -->
            <div class="col-lg-5">
                <div class="purchase-box">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold mb-2">
                        {{ $product->category_label }}
                    </span>
                    <h3 class="fw-extrabold text-dark mb-2 lh-sm">{{ $product->name }}</h3>

                    <!-- Rating & Stock preview -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="star-rating-badge">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span>{{ number_format($product->average_rating, 1) }}</span>
                            <span class="text-muted fw-normal">({{ $product->reviews->count() }} ulasan)</span>
                        </div>
                        <span class="text-muted">•</span>
                        @if($product->stock > 0)
                            <span class="text-success fw-bold small">
                                <i class="bi bi-check-circle-fill me-1"></i> Stok Tersedia: {{ $product->stock }}
                            </span>
                        @else
                            <span class="text-danger fw-bold small">
                                <i class="bi bi-x-circle-fill me-1"></i> Stok Habis
                            </span>
                        @endif
                    </div>

                    <!-- Price -->
                    <div class="p-3 bg-light rounded-3 mb-4">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Harga Resmi Merchandise</small>
                        <h2 class="fw-extrabold text-success mb-0">
                            {{ $product->formatted_price }}
                        </h2>
                    </div>

                    <!-- Syarat Pembelian & Form Pesan -->
                    @guest
                        <!-- Belum Login: Wajib Daftar Anggota ISNU -->
                        <div class="p-4 rounded-4 bg-light border border-warning text-center">
                            <i class="bi bi-lock-fill text-warning display-4 mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark mb-2">Khusus Anggota ISNU</h5>
                            <p class="text-secondary small mb-4">
                                Untuk memesan barang atau seragam resmi ini, Anda <strong>wajib terdaftar sebagai Anggota ISNU Kota Surabaya</strong>.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="{{ route('register') }}" class="btn btn-isnu-primary rounded-pill py-2 fw-semibold">
                                    <i class="bi bi-person-plus-fill me-1"></i> Daftar Jadi Anggota ISNU
                                </a>
                                <a href="{{ route('login') }}" class="btn btn-outline-success rounded-pill py-2 fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Akun
                                </a>
                            </div>
                        </div>
                    @else
                        @php
                            $user = auth()->user();
                            $member = $user->member;
                            $canBuy = $user->is_active && ($member && $member->isVerified());
                        @endphp

                        @if(! $canBuy)
                            <!-- Sudah Login tapi Belum Terverifikasi -->
                            <div class="p-4 rounded-4 bg-warning-subtle border border-warning text-center">
                                <i class="bi bi-clock-history text-warning display-4 mb-2 d-block"></i>
                                <h5 class="fw-bold text-dark mb-2">Akun Menunggu Verifikasi</h5>
                                <p class="text-secondary small mb-3">
                                    Halo, <strong>{{ $user->name }}</strong>! Data keanggotaan Anda saat ini masih dalam proses peninjauan / verifikasi oleh Admin PC ISNU Surabaya.
                                </p>
                                <p class="small text-muted mb-0">
                                    Pemesanan merchandise resmi akan otomatis terbuka begitu status keanggotaan Anda telah disetujui.
                                </p>
                            </div>
                        @elseif($product->stock <= 0)
                            <div class="p-4 rounded-4 bg-danger-subtle border border-danger text-center">
                                <i class="bi bi-emoji-frown text-danger display-4 mb-2 d-block"></i>
                                <h5 class="fw-bold text-danger mb-2">Mohon Maaf, Stok Habis</h5>
                                <p class="text-secondary small mb-0">
                                    Produk ini sedang tidak tersedia atau dalam proses restock oleh Admin PC ISNU.
                                </p>
                            </div>
                        @else
                            <!-- Anggota Terverifikasi: Form Checkout Pemesanan Langsung -->
                            <form action="{{ route('member.orders.checkout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">

                                <div class="mb-3">
                                    <label for="quantity" class="form-label fw-bold small text-dark">Jumlah Pesanan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary" type="button" onclick="decrementQty()">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" id="quantity" name="quantity" class="form-control text-center fw-bold" value="1" min="1" max="{{ $product->stock }}" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="incrementQty({{ $product->stock }})">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Maksimal {{ $product->stock }} buah per pesanan.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="variant_note" class="form-label fw-bold small text-dark">Catatan Ukuran / Varian</label>
                                    <input type="text" id="variant_note" name="variant_note" class="form-control rounded-3" placeholder="Contoh: Ukuran L (Jas), atau Ukuran XL (Kaos)" value="{{ old('variant_note') }}">
                                    <small class="text-muted" style="font-size: 0.72rem;">Isi jika produk memiliki pilihan ukuran (S/M/L/XL/XXL).</small>
                                </div>

                                <hr class="my-3">

                                <h6 class="fw-bold text-dark mb-2">Tujuan Pengiriman:</h6>

                                <div class="mb-2">
                                    <label for="recipient_name" class="form-label fw-semibold small text-dark mb-1">Nama Penerima <span class="text-danger">*</span></label>
                                    <input type="text" id="recipient_name" name="recipient_name" class="form-control form-control-sm rounded-3" value="{{ old('recipient_name', $member?->full_name ?? $user->name) }}" required>
                                </div>

                                <div class="mb-2">
                                    <label for="recipient_phone" class="form-label fw-semibold small text-dark mb-1">WhatsApp / No. HP <span class="text-danger">*</span></label>
                                    <input type="text" id="recipient_phone" name="recipient_phone" class="form-control form-control-sm rounded-3" value="{{ old('recipient_phone', $member?->phone_number) }}" placeholder="08xxxxxxxxxx" required>
                                </div>

                                <div class="mb-3">
                                    <label for="shipping_address" class="form-label fw-semibold small text-dark mb-1">Alamat Lengkap Pengiriman <span class="text-danger">*</span></label>
                                    <textarea id="shipping_address" name="shipping_address" class="form-control form-control-sm rounded-3" rows="3" placeholder="Jl. ..., RT/RW, Kelurahan, Kecamatan, Kota Surabaya, Kode Pos" required>{{ old('shipping_address', $member?->street_address) }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label fw-semibold small text-dark mb-1">Catatan Tambahan (Opsional)</label>
                                    <input type="text" id="notes" name="notes" class="form-control form-control-sm rounded-3" placeholder="Misal: titipkan ke satpam jika tidak ada orang" value="{{ old('notes') }}">
                                </div>

                                <div class="alert alert-info py-2 px-3 rounded-3 small mb-3">
                                    <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                                    <strong>Ketentuan Pembayaran:</strong> Pembayaran dilakukan secara non-tunai (transfer rekening PC ISNU) setelah form pesanan disubmit.
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-isnu-primary rounded-pill py-2 fw-bold fs-6">
                                        <i class="bi bi-bag-check-fill me-1"></i> Buat Pesanan (Non-Tunai)
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endguest

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2 small text-muted mb-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <span>Atribut Resmi & Berlogo Asli PC ISNU Surabaya</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-muted mb-2">
                            <i class="bi bi-truck text-primary fs-5"></i>
                            <span>Dikelola & Dikirim oleh Bendahara PCNU</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <i class="bi bi-star-fill text-warning fs-5"></i>
                            <span>Review bintang 5 & komentar setelah barang diterima</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products Section -->
        @if($relatedProducts->isNotEmpty())
            <div class="mt-5 pt-4 border-top">
                <h4 class="fw-bold text-dark mb-4">Produk Terkait Lainnya</h4>
                <div class="row g-4">
                    @foreach($relatedProducts as $rel)
                        <div class="col-6 col-md-3">
                            <div class="card h-100 border rounded-4 overflow-hidden shadow-sm">
                                <div style="height: 160px; background: #f8fafc; overflow: hidden; position: relative;">
                                    @if($rel->image_url)
                                        <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-box-seam fs-1 text-success opacity-50"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-3 d-flex flex-column flex-grow-1">
                                    <span class="badge bg-success-subtle text-success align-self-start rounded-pill px-2 py-1 mb-1" style="font-size: 0.68rem;">
                                        {{ $rel->category_label }}
                                    </span>
                                    <h6 class="fw-bold text-dark text-truncate mb-1" title="{{ $rel->name }}">
                                        <a href="{{ route($storeShowRoute, $rel->slug) }}" class="text-dark text-decoration-none">
                                            {{ $rel->name }}
                                        </a>
                                    </h6>
                                    <span class="fw-bold text-success mt-auto">{{ $rel->formatted_price }}</span>
                                    <a href="{{ route($storeShowRoute, $rel->slug) }}" class="btn btn-outline-success btn-sm rounded-pill mt-2">
                                        Lihat
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    function incrementQty(max) {
        const input = document.getElementById('quantity');
        let current = parseInt(input.value) || 1;
        if (current < max) {
            input.value = current + 1;
        }
    }

    function decrementQty() {
        const input = document.getElementById('quantity');
        let current = parseInt(input.value) || 1;
        if (current > 1) {
            input.value = current - 1;
        }
    }
</script>
@endsection
