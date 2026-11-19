@extends('layouts.admin')

@section('title', 'Edit Produk Merchandise - Admin PCNU')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Manajemen Produk</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Produk</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark mb-0">Edit Produk: {{ $product->name }}</h4>
        <p class="text-muted small mb-0">Perbarui informasi harga, stok, foto, dan deskripsi produk.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
            <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terdapat kesalahan pengisian data:</strong>
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="name" class="form-label fw-bold small text-dark">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control rounded-3" value="{{ old('name', $product->name) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label for="category" class="form-label fw-bold small text-dark">Kategori <span class="text-danger">*</span></label>
                        <select id="category" name="category" class="form-select rounded-3" required>
                            @foreach($categories as $key => $name)
                                @if($key !== 'all')
                                    <option value="{{ $key }}" {{ old('category', $product->category) === $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="price" class="form-label fw-bold small text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" id="price" name="price" class="form-control" value="{{ old('price', (int)$product->price) }}" min="0" step="1000" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="stock" class="form-label fw-bold small text-dark">Jumlah Stok (Unit) <span class="text-danger">*</span></label>
                        <input type="number" id="stock" name="stock" class="form-control rounded-3" value="{{ old('stock', $product->stock) }}" min="0" required>
                    </div>

                    <div class="col-md-4">
                        <label for="weight_grams" class="form-label fw-bold small text-dark">Perkiraan Berat (Gram)</label>
                        <div class="input-group">
                            <input type="number" id="weight_grams" name="weight_grams" class="form-control" value="{{ old('weight_grams', $product->weight_grams) }}" min="0">
                            <span class="input-group-text bg-light">gram</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="image" class="form-label fw-bold small text-dark">Ganti Foto Produk (Opsional)</label>
                        @if($product->image_url)
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded-3 border" style="width: 80px; height: 80px; object-fit: cover;">
                                <small class="text-muted">Foto saat ini. Pilih file baru jika ingin menggantinya.</small>
                            </div>
                        @endif
                        <input type="file" id="image" name="image" class="form-control rounded-3" accept="image/*">
                        <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, PNG, WEBP. Maksimal 5MB.</small>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-bold small text-dark">Deskripsi & Rincian Produk</label>
                        <textarea id="description" name="description" class="form-control rounded-3" rows="5">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small text-dark" for="is_active">Aktifkan produk di katalog toko publik</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
                    <button type="submit" class="btn btn-isnu-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Perbarui Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
