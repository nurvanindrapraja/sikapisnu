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
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
            <a href="{{ route('store.index') }}" target="_blank" class="btn btn-outline-success rounded-pill px-3 shadow-sm d-inline-flex align-items-center justify-content-center btn-mobile-icon" title="Buka Toko Publik" aria-label="Buka Toko Publik">
                <i class="bi bi-box-arrow-up-right"></i>
                <span class="d-none d-md-inline ms-1">Buka Toko Publik</span>
            </a>
            <button type="button"
                    class="btn btn-isnu-primary rounded-pill px-3 shadow-sm d-inline-flex align-items-center justify-content-center btn-mobile-icon"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCreateProduct"
                    title="Tambah Produk Baru"
                    aria-label="Tambah Produk Baru">
                <i class="bi bi-plus-circle-fill"></i>
                <span class="d-none d-md-inline ms-1">Tambah Produk Baru</span>
            </button>
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

    <!-- Top Loading Bar -->
    <div id="productLoadingBar" class="progress rounded-pill mb-3" style="height: 4px; display: none;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
    </div>

    <!-- Filters & Search Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="filterProductForm" action="{{ route('admin.products.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" name="search" class="form-control bg-light border-start-0" placeholder="Ketik nama barang untuk mencari..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-3">
                    <select id="categorySelect" name="category" class="form-select form-select-sm">
                        <option value="all">Semua Kategori</option>
                        @foreach($categories as $key => $name)
                            @if($key !== 'all')
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <select id="statusSelect" name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 col-lg-3 text-md-end d-flex align-items-center justify-content-md-end gap-2">
                    <div id="tableSpinner" class="spinner-border spinner-border-sm text-success" style="display: none;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Filter</button>
                    <button type="button" id="btnResetFilter" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
        <div id="tableLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 z-2" style="display: none !important;">
            <div class="spinner-border text-success mb-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <small class="text-muted fw-semibold">Memuat data produk...</small>
        </div>

        <div class="card-body p-0" id="productTableContainer">
            @include('admin.products.partials.table', ['products' => $products])
        </div>
    </div>
</div>

<!-- Modal Tambah Produk Baru -->
<div class="modal fade" id="modalCreateProduct" tabindex="-1" aria-labelledby="modalCreateProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalCreateProductLabel">
                    <i class="bi bi-plus-circle-fill text-success me-2"></i> Tambah Produk Merchandise Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCreateProduct" action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div id="createProductErrors" class="alert alert-danger rounded-3 d-none mb-3 py-2 small"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="create_name" class="form-label fw-bold small text-dark">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" id="create_name" name="name" class="form-control rounded-3" placeholder="Contoh: Jas Resmi ISNU Standar Nasional" required>
                        </div>

                        <div class="col-md-4">
                            <label for="create_category" class="form-label fw-bold small text-dark">Kategori <span class="text-danger">*</span></label>
                            <select id="create_category" name="category" class="form-select rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $key => $name)
                                    @if($key !== 'all')
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="create_price" class="form-label fw-bold small text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" id="create_price" name="price" class="form-control" min="0" step="1000" placeholder="150000" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="create_stock" class="form-label fw-bold small text-dark">Jumlah Stok <span class="text-danger">*</span></label>
                            <input type="number" id="create_stock" name="stock" class="form-control rounded-3" value="10" min="0" required>
                        </div>

                        <div class="col-md-4">
                            <label for="create_weight" class="form-label fw-bold small text-dark">Perkiraan Berat</label>
                            <div class="input-group">
                                <input type="number" id="create_weight" name="weight_grams" class="form-control" value="250" min="0">
                                <span class="input-group-text bg-light">gram</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="create_image" class="form-label fw-bold small text-dark">Foto Produk Merchandise</label>
                            <input type="file" id="create_image" name="image" class="form-control rounded-3" accept="image/*">
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Format: JPG, PNG, WEBP (maksimal 5MB).</small>

                            <!-- Live Image Preview -->
                            <div id="createImagePreviewContainer" class="mt-2 d-none">
                                <small class="text-muted d-block mb-1">Preview Foto:</small>
                                <div class="position-relative d-inline-block border rounded-3 p-1 bg-light">
                                    <img id="createImagePreview" src="" alt="Preview" class="rounded-2" style="max-height: 120px; object-fit: contain;">
                                    <button type="button" id="btnRemoveCreateImage" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle p-1" style="line-height: 1; width: 24px; height: 24px;" title="Hapus foto">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="create_description" class="form-label fw-bold small text-dark">Deskripsi & Rincian Produk</label>
                            <textarea id="create_description" name="description" class="form-control rounded-3" rows="3" placeholder="Tuliskan spesifikasi bahan, ukuran yang tersedia, makna logo, dll..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-bold small text-dark" for="create_is_active">Aktifkan produk di katalog toko publik</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitCreateProduct" class="btn btn-isnu-primary rounded-pill px-4 fw-bold">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-save me-1"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="modalEditProduct" tabindex="-1" aria-labelledby="modalEditProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="modalEditProductLabel">
                    <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Produk Merchandise
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditProduct" action="" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body p-4 position-relative">
                    <div id="editLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 z-2" style="display: none !important;">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <small class="text-muted fw-semibold">Memuat data produk...</small>
                    </div>

                    <div id="editProductErrors" class="alert alert-danger rounded-3 d-none mb-3 py-2 small"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="edit_name" class="form-label fw-bold small text-dark">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" id="edit_name" name="name" class="form-control rounded-3" required>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_category" class="form-label fw-bold small text-dark">Kategori <span class="text-danger">*</span></label>
                            <select id="edit_category" name="category" class="form-select rounded-3" required>
                                @foreach($categories as $key => $name)
                                    @if($key !== 'all')
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_price" class="form-label fw-bold small text-dark">Harga (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" id="edit_price" name="price" class="form-control" min="0" step="1000" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_stock" class="form-label fw-bold small text-dark">Jumlah Stok <span class="text-danger">*</span></label>
                            <input type="number" id="edit_stock" name="stock" class="form-control rounded-3" min="0" required>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_weight" class="form-label fw-bold small text-dark">Perkiraan Berat</label>
                            <div class="input-group">
                                <input type="number" id="edit_weight" name="weight_grams" class="form-control" min="0">
                                <span class="input-group-text bg-light">gram</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="edit_image" class="form-label fw-bold small text-dark">Ganti Foto Produk (Opsional)</label>
                            <input type="file" id="edit_image" name="image" class="form-control rounded-3" accept="image/*">
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Format: JPG, PNG, WEBP (maksimal 5MB). Biarkan kosong jika tidak ingin mengubah foto.</small>

                            <!-- Live Image Preview for Edit -->
                            <div id="editImagePreviewContainer" class="mt-2 d-none">
                                <small class="text-muted d-block mb-1" id="editPreviewLabel">Foto Saat Ini / Preview:</small>
                                <div class="position-relative d-inline-block border rounded-3 p-1 bg-light">
                                    <img id="editImagePreview" src="" alt="Preview" class="rounded-2" style="max-height: 120px; object-fit: contain;">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="edit_description" class="form-label fw-bold small text-dark">Deskripsi & Rincian Produk</label>
                            <textarea id="edit_description" name="description" class="form-control rounded-3" rows="3"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                                <label class="form-check-label fw-bold small text-dark" for="edit_is_active">Aktifkan produk di katalog toko publik</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitEditProduct" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-save me-1"></i> Perbarui Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Produk (Menggantikan confirm()) -->
<div class="modal fade" id="modalConfirmDeleteProduct" tabindex="-1" aria-labelledby="modalConfirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger-subtle text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-trash fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Produk?</h5>
                <p class="text-secondary small mb-3">
                    Apakah Anda yakin ingin menghapus produk <strong id="deleteProductName" class="text-dark"></strong> dari katalog? Tindakan ini dapat dibatalkan melalui admin.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnExecuteDeleteProduct" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterProductForm');
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const statusSelect = document.getElementById('statusSelect');
    const btnReset = document.getElementById('btnResetFilter');
    const tableContainer = document.getElementById('productTableContainer');
    const tableSpinner = document.getElementById('tableSpinner');
    const loadingOverlay = document.getElementById('tableLoadingOverlay');
    const loadingBar = document.getElementById('productLoadingBar');
    const alertContainer = document.getElementById('ajaxAlertContainer');

    // Helper: Tampilkan Notifikasi Toast
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
            if (el) {
                bootstrap.Alert.getOrCreateInstance(el).close();
            }
        }, 5000);
    }

    // Helper: Show/Hide Loading
    function setLoading(isLoading) {
        if (isLoading) {
            loadingBar.style.display = 'block';
            tableSpinner.style.display = 'inline-block';
            if (loadingOverlay) loadingOverlay.style.setProperty('display', 'flex', 'important');
        } else {
            loadingBar.style.display = 'none';
            tableSpinner.style.display = 'none';
            if (loadingOverlay) loadingOverlay.style.setProperty('display', 'none', 'important');
        }
    }

    // AJAX: Fetch Data Produk & Render Table
    let debounceTimer = null;
    function fetchProducts(url = null) {
        setLoading(true);
        const targetUrl = url || (filterForm.action + '?' + new URLSearchParams(new FormData(filterForm)).toString());

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Gagal memuat data produk.');
            return response.json();
        })
        .then(data => {
            if (data.html !== undefined) {
                tableContainer.innerHTML = data.html;
                bindDynamicEvents();
            }
        })
        .catch(err => {
            console.error(err);
            showNotification(err.message || 'Terjadi kesalahan jaringan.', 'danger');
        })
        .finally(() => {
            setLoading(false);
        });
    }

    // Filter Listeners
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchProducts();
        }, 400);
    });

    categorySelect.addEventListener('change', () => fetchProducts());
    statusSelect.addEventListener('change', () => fetchProducts());

    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchProducts();
    });

    btnReset.addEventListener('click', function() {
        searchInput.value = '';
        categorySelect.value = 'all';
        statusSelect.value = '';
        fetchProducts();
    });

    // Delegasi pagination link via AJAX
    function bindDynamicEvents() {
        // Pagination link click
        tableContainer.querySelectorAll('.ajax-pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchProducts(this.href);
            });
        });

        // Toggle Status Button
        tableContainer.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const btnText = this.querySelector('.btn-text');
                const originalText = btnText.textContent;

                this.disabled = true;
                btnText.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btnText.textContent = data.label;
                        this.className = `btn btn-sm rounded-pill px-2 py-0 fw-semibold btn-toggle-status ${data.badge_class}`;
                        showNotification(data.message, 'success');
                    } else {
                        btnText.textContent = originalText;
                        showNotification(data.message || 'Gagal mengubah status.', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnText.textContent = originalText;
                    showNotification('Terjadi kesalahan saat mengubah status produk.', 'danger');
                })
                .finally(() => {
                    this.disabled = false;
                });
            });
        });

        // Edit Button Click (Open Edit Modal & Fetch Data)
        tableContainer.querySelectorAll('.btn-edit-product').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const updateUrl = this.getAttribute('data-update-url');
                const modalEl = document.getElementById('modalEditProduct');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                const editOverlay = document.getElementById('editLoadingOverlay');
                const editForm = document.getElementById('formEditProduct');
                const editErrors = document.getElementById('editProductErrors');

                editErrors.classList.add('d-none');
                editErrors.innerHTML = '';
                editForm.action = updateUrl;
                editOverlay.style.setProperty('display', 'flex', 'important');
                modal.show();

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.product) {
                        const p = data.product;
                        document.getElementById('edit_name').value = p.name || '';
                        document.getElementById('edit_category').value = p.category || '';
                        document.getElementById('edit_price').value = p.price || 0;
                        document.getElementById('edit_stock').value = p.stock || 0;
                        document.getElementById('edit_weight').value = p.weight_grams || 250;
                        document.getElementById('edit_description').value = p.description || '';
                        document.getElementById('edit_is_active').checked = !!p.is_active;

                        // Preview Foto
                        const previewContainer = document.getElementById('editImagePreviewContainer');
                        const previewImg = document.getElementById('editImagePreview');
                        if (p.image_url) {
                            previewImg.src = p.image_url;
                            previewContainer.classList.remove('d-none');
                        } else {
                            previewContainer.classList.add('d-none');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('Gagal memuat rincian data produk.', 'danger');
                })
                .finally(() => {
                    editOverlay.style.setProperty('display', 'none', 'important');
                });
            });
        });

        // Delete Button Click (Open Confirm Modal)
        tableContainer.querySelectorAll('.btn-delete-product').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const url = this.getAttribute('data-url');

                document.getElementById('deleteProductName').textContent = `"${name}"`;
                const btnConfirm = document.getElementById('btnExecuteDeleteProduct');
                btnConfirm.setAttribute('data-url', url);
                btnConfirm.setAttribute('data-id', id);

                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmDeleteProduct'));
                modal.show();
            });
        });
    }

    // Modal Create Image Preview
    const createImageInput = document.getElementById('create_image');
    const createImagePreviewContainer = document.getElementById('createImagePreviewContainer');
    const createImagePreview = document.getElementById('createImagePreview');
    const btnRemoveCreateImage = document.getElementById('btnRemoveCreateImage');

    createImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                createImagePreview.src = e.target.result;
                createImagePreviewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            createImagePreviewContainer.classList.add('d-none');
        }
    });

    btnRemoveCreateImage.addEventListener('click', function() {
        createImageInput.value = '';
        createImagePreview.src = '';
        createImagePreviewContainer.classList.add('d-none');
    });

    // Modal Edit Image Preview
    const editImageInput = document.getElementById('edit_image');
    const editImagePreviewContainer = document.getElementById('editImagePreviewContainer');
    const editImagePreview = document.getElementById('editImagePreview');

    editImageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                editImagePreview.src = e.target.result;
                editImagePreviewContainer.classList.remove('d-none');
                document.getElementById('editPreviewLabel').textContent = 'Preview Foto Baru:';
            };
            reader.readAsDataURL(file);
        }
    });

    // Form Create Submit via AJAX
    const formCreate = document.getElementById('formCreateProduct');
    const btnSubmitCreate = document.getElementById('btnSubmitCreateProduct');
    const createErrors = document.getElementById('createProductErrors');

    formCreate.addEventListener('submit', function(e) {
        e.preventDefault();
        btnSubmitCreate.disabled = true;
        const spinner = btnSubmitCreate.querySelector('.spinner-border');
        spinner.classList.remove('d-none');
        createErrors.classList.add('d-none');
        createErrors.innerHTML = '';

        const formData = new FormData(formCreate);

        fetch(formCreate.action, {
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
                bootstrap.Modal.getInstance(document.getElementById('modalCreateProduct')).hide();
                formCreate.reset();
                createImagePreviewContainer.classList.add('d-none');
                showNotification(data.message, 'success');
                fetchProducts();
            }
        })
        .catch(err => {
            console.error(err);
            let msg = 'Gagal menyimpan produk.';
            if (err.errors) {
                msg = '<ul class="mb-0 ps-3">' + Object.values(err.errors).map(e => `<li>${e[0]}</li>`).join('') + '</ul>';
            } else if (err.message) {
                msg = err.message;
            }
            createErrors.innerHTML = msg;
            createErrors.classList.remove('d-none');
        })
        .finally(() => {
            btnSubmitCreate.disabled = false;
            spinner.classList.add('d-none');
        });
    });

    // Form Edit Submit via AJAX
    const formEdit = document.getElementById('formEditProduct');
    const btnSubmitEdit = document.getElementById('btnSubmitEditProduct');
    const editErrors = document.getElementById('editProductErrors');

    formEdit.addEventListener('submit', function(e) {
        e.preventDefault();
        btnSubmitEdit.disabled = true;
        const spinner = btnSubmitEdit.querySelector('.spinner-border');
        spinner.classList.remove('d-none');
        editErrors.classList.add('d-none');
        editErrors.innerHTML = '';

        const formData = new FormData(formEdit);

        fetch(formEdit.action, {
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
                bootstrap.Modal.getInstance(document.getElementById('modalEditProduct')).hide();
                showNotification(data.message, 'success');
                fetchProducts();
            }
        })
        .catch(err => {
            console.error(err);
            let msg = 'Gagal memperbarui produk.';
            if (err.errors) {
                msg = '<ul class="mb-0 ps-3">' + Object.values(err.errors).map(e => `<li>${e[0]}</li>`).join('') + '</ul>';
            } else if (err.message) {
                msg = err.message;
            }
            editErrors.innerHTML = msg;
            editErrors.classList.remove('d-none');
        })
        .finally(() => {
            btnSubmitEdit.disabled = false;
            spinner.classList.add('d-none');
        });
    });

    // Execute Delete via AJAX (from Modal)
    const btnExecuteDelete = document.getElementById('btnExecuteDeleteProduct');
    btnExecuteDelete.addEventListener('click', function() {
        const url = this.getAttribute('data-url');
        const id = this.getAttribute('data-id');
        const spinner = this.querySelector('.spinner-border');

        this.disabled = true;
        spinner.classList.remove('d-none');

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmDeleteProduct')).hide();
            if (data.success) {
                showNotification(data.message, 'success');
                // Smooth remove row
                const row = document.getElementById(`product-row-${id}`);
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        fetchProducts();
                    }, 350);
                } else {
                    fetchProducts();
                }
            } else {
                showNotification(data.message || 'Gagal menghapus produk.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Terjadi kesalahan saat menghapus produk.', 'danger');
        })
        .finally(() => {
            this.disabled = false;
            spinner.classList.add('d-none');
        });
    });

    // Initial binding
    bindDynamicEvents();
});
</script>
@endsection
