@extends('layouts.app')

@section('title', 'Toko Merchandise Resmi ISNU')

@section('styles')
<style>
    .store-hero {
        background: linear-gradient(135deg, #003e21 0%, #006837 50%, #0d8a4d 100%);
        color: white;
        padding: 3.5rem 0 3rem;
        position: relative;
        overflow: hidden;
    }
    .store-hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(212, 175, 55, 0.18) 0%, transparent 70%);
        border-radius: 50%;
    }
    .product-card {
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .product-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 30px rgba(0, 104, 55, 0.12);
        border-color: rgba(0, 104, 55, 0.3);
    }
    .product-img-wrapper {
        position: relative;
        width: 100%;
        padding-top: 75%; /* 4:3 Aspect Ratio */
        background: #f1f5f9;
        overflow: hidden;
    }
    .product-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .product-card:hover .product-img {
        transform: scale(1.05);
    }
    .category-pill {
        border-radius: 50rem;
        padding: 0.5rem 1.2rem;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .category-pill.active {
        background-color: var(--isnu-green);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 104, 55, 0.25);
    }
    .category-pill:not(.active) {
        background-color: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .category-pill:not(.active):hover {
        background-color: #f8fafc;
        border-color: var(--isnu-green);
        color: var(--isnu-green);
    }
    .star-rating-badge {
        background: #fffbeb;
        color: #b45309;
        font-weight: 700;
        font-size: 0.78rem;
        padding: 0.25rem 0.55rem;
        border-radius: 50rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>
@endsection

@section('content')
<!-- Hero Section Toko -->
<section class="store-hero">
    <div class="container px-3 px-sm-4 position-relative z-1">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.8px;">
                    <i class="bi bi-shop me-1"></i> Toko Resmi Merchandise & Atribut
                </span>
                <h1 class="display-5 fw-extrabold text-white mb-3" style="letter-spacing: -0.5px;">
                    Merchandise Resmi <span style="color: var(--isnu-gold-light);">PC ISNU Kota Surabaya</span>
                </h1>
                <p class="lead text-white-80 mb-4" style="color: rgba(255,255,255,0.9); font-size: 1.05rem;">
                    Dapatkan atribut seragam jas resmi, batik nusantara khas ISNU, polo shirt, pin lencana logam berlogo, serta buku karya ilmiah para sarjana & cendekiawan Nahdlatul Ulama.
                </p>
                <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2);">
                    <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                    <span class="small text-white">
                        <strong>Syarat Pembelian:</strong> Wajib mendaftar / terverifikasi sebagai <strong>Anggota Resmi ISNU</strong>.
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end text-center">
                <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                    <i class="bi bi-credit-card-2-front text-warning display-4 mb-2 d-block"></i>
                    <h5 class="text-white fw-bold mb-1">Pembayaran Non-Tunai</h5>
                    <p class="small text-white-50 mb-0">Transaksi aman melalui transfer bank / QRIS rekening resmi PC ISNU Surabaya & dikelola langsung oleh Bendahara.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter & Search Section -->
<section class="py-4 bg-light border-bottom">
    <div class="container px-3 px-sm-4">
        <!-- Search & Sort Controls -->
        <form method="GET" action="{{ route('store.index') }}" class="row g-3 align-items-center mb-3">
            <input type="hidden" name="category" value="{{ request('category', 'all') }}">

            <div class="col-md-7 col-lg-8">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white">
                    <span class="input-group-text bg-white border-0 ps-3 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control border-0 py-2" placeholder="Cari jas, batik, kaos, pin, buku karya sarjana ISNU..." aria-label="Cari barang">
                    @if(request('q'))
                        <a href="{{ route('store.index', ['category' => request('category', 'all')]) }}" class="btn btn-link text-muted text-decoration-none">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    @endif
                    <button class="btn btn-isnu-primary px-4 fw-semibold" type="submit">Cari</button>
                </div>
            </div>

            <div class="col-md-5 col-lg-4">
                <div class="d-flex align-items-center justify-content-md-end gap-2">
                    <label for="sortSelect" class="form-label text-muted small fw-bold mb-0 text-nowrap">Urutkan:</label>
                    <select id="sortSelect" name="sort" class="form-select form-select-sm rounded-pill shadow-sm bg-white" onchange="this.form.submit()" style="max-width: 220px;">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Rating Tertinggi ★</option>
                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Harga Terendah</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nama (A - Z)</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- Category Pills -->
        <div class="d-flex gap-2 overflow-x-auto py-2" style="scrollbar-width: thin;">
            @foreach($categories as $key => $label)
                <a href="{{ route('store.index', array_merge(request()->except(['category', 'page']), ['category' => $key])) }}" 
                   class="category-pill {{ (request('category', 'all') == $key) ? 'active' : '' }}">
                    @if($key === 'all')
                        <i class="bi bi-grid-fill"></i>
                    @elseif($key === 'seragam')
                        <i class="bi bi-person-badge"></i>
                    @elseif($key === 'batik')
                        <i class="bi bi-palette-fill"></i>
                    @elseif($key === 'kaos')
                        <i class="bi bi-file-person"></i>
                    @elseif($key === 'pin')
                        <i class="bi bi-award-fill"></i>
                    @elseif($key === 'buku')
                        <i class="bi bi-book-half"></i>
                    @else
                        <i class="bi bi-tag-fill"></i>
                    @endif
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Product Grid Section -->
<section class="py-5">
    <div class="container px-3 px-sm-4">
        @if($products->isEmpty())
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                <i class="bi bi-box2 text-muted display-1 mb-3 d-block opacity-50"></i>
                <h4 class="fw-bold text-dark">Barang Tidak Ditemukan</h4>
                <p class="text-secondary max-w-md mx-auto mb-4">
                    Belum ada produk merchandise yang sesuai dengan kata kunci pencarian atau kategori yang Anda pilih.
                </p>
                <a href="{{ route('store.index') }}" class="btn btn-isnu-primary rounded-pill px-4">
                    <i class="bi bi-arrow-repeat me-1"></i> Lihat Semua Produk
                </a>
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        Katalog Produk Merchandise
                        @if(request('category') && request('category') !== 'all')
                            <span class="text-success">— {{ $categories[request('category')] ?? '' }}</span>
                        @endif
                    </h5>
                    <p class="small text-muted mb-0">Menampilkan {{ $products->total() }} barang resmi ISNU</p>
                </div>
            </div>

            <div class="row g-4">
                @foreach($products as $product)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="product-card h-100">
                            <!-- Image -->
                            <div class="product-img-wrapper">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img" loading="lazy">
                                @else
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted bg-light">
                                        <i class="bi bi-box-seam display-4 opacity-50 text-success"></i>
                                        <small class="mt-2 fw-semibold text-secondary">Merchandise ISNU</small>
                                    </div>
                                @endif

                                <!-- Category Badge -->
                                <span class="position-absolute top-0 start-0 m-2 badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem;">
                                    {{ $product->category_label }}
                                </span>

                                <!-- Stock Badge -->
                                @if($product->stock <= 0)
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-danger rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem;">
                                        Stok Habis
                                    </span>
                                @elseif($product->stock < 5)
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem;">
                                        Sisa {{ $product->stock }}
                                    </span>
                                @endif
                            </div>

                            <!-- Body -->
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <!-- Rating & Review Count -->
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="star-rating-badge">
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <span>{{ number_format($product->average_rating, 1) }}</span>
                                        <span class="text-muted fw-normal" style="font-size: 0.72rem;">({{ $product->reviews_count }})</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-box-seam me-1"></i> Stok: {{ $product->stock }}
                                    </small>
                                </div>

                                <!-- Title -->
                                <h6 class="fw-bold text-dark mb-1 lh-sm" title="{{ $product->name }}" style="min-height: 2.4rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <a href="{{ route('store.show', $product->slug) }}" class="text-dark text-decoration-none hover-success">
                                        {{ $product->name }}
                                    </a>
                                </h6>

                                <!-- Price -->
                                <div class="mt-auto pt-2 border-top">
                                    <div class="d-flex align-items-baseline justify-content-between mb-3">
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Harga Resmi</small>
                                            <span class="fw-extrabold text-success fs-5">
                                                {{ $product->formatted_price }}
                                            </span>
                                        </div>
                                    </div>

                                    <a href="{{ route('store.show', $product->slug) }}" class="btn btn-isnu-primary w-100 rounded-pill py-2 btn-sm fw-semibold">
                                        <i class="bi bi-bag-plus me-1"></i> Pesan Barang
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-5 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</section>

<!-- Callout Banner: Khusus Anggota ISNU -->
<section class="py-5 bg-white border-top">
    <div class="container px-3 px-sm-4">
        <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #bbf7d0;">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success px-3 py-1 rounded-pill fw-bold text-white">
                            <i class="bi bi-info-circle-fill me-1"></i> Informasi Pembelian
                        </span>
                        <span class="text-muted small">Aturan Pengurus PC ISNU Surabaya</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Ingin Membeli Merchandise Resmi ISNU?</h3>
                    <p class="text-secondary mb-0">
                        Untuk menjaga keaslian identitas organisasi dan integritas atribut sarjana NU, seluruh transaksi pembelian seragam, batik, dan pin ISNU hanya dilayani untuk <strong>Anggota ISNU Kota Surabaya yang telah memiliki akun terverifikasi</strong>.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end text-center">
                    @guest
                        <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                            <a href="{{ route('register') }}" class="btn btn-isnu-primary rounded-pill px-4 py-2 fw-semibold">
                                <i class="bi bi-person-plus-fill me-1"></i> Daftar Anggota ISNU
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-success rounded-pill px-4 py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Akun
                            </a>
                        </div>
                    @else
                        @if(auth()->user()->member && auth()->user()->member->isVerified())
                            <div class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill bg-success text-white shadow-sm">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <span class="small fw-semibold">Anda terverifikasi sebagai Anggota Aktif ISNU</span>
                            </div>
                        @else
                            <a href="{{ route('member.dashboard') }}" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark">
                                <i class="bi bi-speedometer2 me-1"></i> Cek Status Akun Saya
                            </a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
