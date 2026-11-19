@extends('layouts.admin')

@section('title', 'Manajemen Produk Toko Merchandise - Admin PCNU')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manajemen Produk Toko</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Manajemen Produk & Merchandise ISNU</h4>
            <p class="text-muted small mb-0">Dikelola oleh Admin PCNU untuk penyediaan seragam, batik, kaos, pin, buku, dan cinderamata resmi.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('store.index') }}" target="_blank" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Toko Publik
            </a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-isnu-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Produk Baru
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.products.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari nama barang..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">Semua Kategori</option>
                        @foreach($categories as $key => $name)
                            @if($key !== 'all')
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 col-lg-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Filter</button>
                    @if(request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            @if($products->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-box-seam display-4 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Data Produk</h5>
                    <p class="text-secondary small mb-3">Tambahkan produk baru ke dalam katalog merchandise ISNU.</p>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th style="width: 70px;" class="text-center">Foto</th>
                                <th>Nama Produk & Kategori</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Rating</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $prod)
                                <tr>
                                    <td class="text-center">
                                        <div class="rounded-3 bg-light border p-1 mx-auto" style="width: 52px; height: 52px; overflow: hidden;">
                                            @if($prod->image_url)
                                                <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="w-100 h-100 object-fit-cover rounded-2">
                                            @else
                                                <i class="bi bi-box-seam text-success fs-3 lh-lg"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('store.show', $prod->slug) }}" target="_blank" class="fw-bold text-dark text-decoration-none hover-success d-block">
                                            {{ $prod->name }}
                                        </a>
                                        <div class="d-flex gap-2 mt-1">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                                                {{ $prod->category_label }}
                                            </span>
                                            <small class="text-muted" style="font-size: 0.72rem;">{{ $prod->weight_grams }} gram</small>
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        {{ $prod->formatted_price }}
                                    </td>
                                    <td class="text-center">
                                        @if($prod->stock <= 0)
                                            <span class="badge bg-danger rounded-pill px-2">Habis</span>
                                        @elseif($prod->stock < 5)
                                            <span class="badge bg-warning text-dark rounded-pill px-2">{{ $prod->stock }} unit</span>
                                        @else
                                            <span class="badge bg-success rounded-pill px-2">{{ $prod->stock }} unit</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-star-fill text-warning me-1"></i>
                                            {{ number_format($prod->average_rating, 1) }}
                                            <small class="text-muted">({{ $prod->reviews_count }})</small>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.products.toggle_status', $prod->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm rounded-pill px-2 py-0 fw-semibold {{ $prod->is_active ? 'btn-success' : 'btn-secondary' }}" style="font-size: 0.75rem;" title="Klik untuk ubah status aktif/nonaktif">
                                                {{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('admin.products.edit', $prod->id) }}" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1" title="Edit Produk">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('admin.products.destroy', $prod->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini dari katalog?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3 px-2 py-1" title="Hapus Produk">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
