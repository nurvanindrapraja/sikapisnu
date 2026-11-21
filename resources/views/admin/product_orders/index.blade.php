@extends('layouts.admin')

@section('title', 'Kelola Pesanan & Pengiriman Merchandise - Bendahara PCNU')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pesanan Merchandise</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Kelola Pemesanan & Pengiriman Merchandise</h4>
            <p class="text-muted small mb-0">Dikelola oleh Pengurus NU jabatan Bendahara / Wakil Bendahara (verifikasi pembayaran non-tunai & pengiriman).</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-person-badge-fill me-1"></i> Otoritas: Bendahara / Wakil Bendahara
            </span>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Loading Bar -->
    <div id="orderLoadingBar" class="progress rounded-pill mb-3" style="height: 4px; display: none;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
    </div>

    <!-- Stat Summary Cards for Bendahara -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #003e21 0%, #006837 100%); color: white;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.72rem;">Total Kas Masuk</small>
                        <i class="bi bi-cash-stack text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-extrabold text-white mb-0" id="statTotalIncome">Rp {{ number_format($stats['total_income'], 0, ',', '.') }}</h4>
                    <small class="text-white-50" style="font-size: 0.72rem;">Dari pesanan lunas/selesai</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Perlu Verifikasi Kas</small>
                        <i class="bi bi-receipt text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0" id="statPaymentUploaded">{{ $stats['payment_uploaded'] }}</h4>
                    <small class="text-warning fw-semibold" style="font-size: 0.72rem;">Bukti transfer baru diunggah</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Siap & Sedang Dikirim</small>
                        <i class="bi bi-truck text-primary fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0" id="statShipping">{{ $stats['paid'] + $stats['shipped'] }}</h4>
                    <small class="text-primary fw-semibold" style="font-size: 0.72rem;"><span id="statPaidText">{{ $stats['paid'] }}</span> siap kirim, <span id="statShippedText">{{ $stats['shipped'] }}</span> dikirim</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Pesanan Selesai</small>
                        <i class="bi bi-check2-circle text-success fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0" id="statReceived">{{ $stats['received'] }}</h4>
                    <small class="text-success fw-semibold" style="font-size: 0.72rem;">Diterima oleh pembeli</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form id="filterOrderForm" action="{{ route('admin.product_orders.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="orderSearchInput" name="search" class="form-control bg-light border-start-0" placeholder="Cari no. pesanan, nama pembeli, no resi..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-4 col-lg-4">
                    <select id="orderStatusSelect" name="status" class="form-select form-select-sm">
                        <option value="all">Semua Status Pesanan</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PENDING_PAYMENT }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PENDING_PAYMENT ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED ? 'selected' : '' }}>Bukti Bayar Diunggah (Perlu Verifikasi)</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PAID }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PAID ? 'selected' : '' }}>Lunas / Siap Kirim</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_SHIPPED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_SHIPPED ? 'selected' : '' }}>Sedang Dikirim</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_RECEIVED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_RECEIVED ? 'selected' : '' }}>Selesai Diterima Pembeli</option>
                    </select>
                </div>

                <div class="col-md-2 col-lg-3 text-md-end d-flex align-items-center justify-content-md-end gap-2">
                    <div id="orderTableSpinner" class="spinner-border spinner-border-sm text-success" style="display: none;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Filter</button>
                    <button type="button" id="btnResetOrderFilter" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
        <div id="orderTableOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 z-2" style="display: none !important;">
            <div class="spinner-border text-success mb-2" role="status"></div>
            <small class="text-muted fw-semibold">Memuat data pesanan...</small>
        </div>

        <div class="card-body p-0" id="orderTableContainer">
            @include('admin.product_orders.partials.order_table', ['orders' => $orders])
        </div>
    </div>
</div>

<!-- Modal Popup Lihat Bukti Transfer (Menggantikan link new tab) -->
<div class="modal fade" id="modalViewProof" tabindex="-1" aria-labelledby="modalViewProofLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark" id="modalViewProofLabel">
                        <i class="bi bi-image text-success me-2"></i> Bukti Transfer Pembayaran
                    </h5>
                    <small class="text-muted" id="modalProofSubTitle">No. Pesanan: -</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-2 border rounded-4 bg-light mb-3">
                    <img id="modalProofImage" src="" alt="Bukti Transfer" class="img-fluid rounded-3 shadow-sm" style="max-height: 480px; object-fit: contain;">
                </div>
                <div class="row g-2 text-start small bg-light p-3 rounded-3 border">
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Nama Pembeli:</span>
                        <strong class="text-dark" id="modalProofRecipient">-</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Total Tagihan:</span>
                        <strong class="text-success" id="modalProofTotal">-</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block">Waktu Upload:</span>
                        <span class="text-dark" id="modalProofDate">-</span>
                    </div>
                    <div class="col-sm-6 text-sm-end align-self-end">
                        <a id="modalProofDirectLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Ukuran Asli
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isSuperAdmin())
<!-- Modal Konfirmasi Hapus Pesanan Merchandise (Khusus Super Admin) -->
<div class="modal fade" id="modalConfirmDeleteOrder" tabindex="-1" aria-labelledby="modalConfirmDeleteOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger-subtle text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-trash fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modalConfirmDeleteOrderLabel">Hapus Data Pesanan?</h5>
                <p class="text-secondary small mb-3">
                    Apakah Anda yakin ingin menghapus pesanan <strong id="deleteOrderNumber" class="text-dark"></strong> atas nama <strong id="deleteOrderRecipient" class="text-dark"></strong>?
                    Data pesanan yang dihapus tidak dapat dikembalikan.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnExecuteDeleteOrder" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-trash me-1"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterOrderForm');
    const searchInput = document.getElementById('orderSearchInput');
    const statusSelect = document.getElementById('orderStatusSelect');
    const btnReset = document.getElementById('btnResetOrderFilter');
    const tableContainer = document.getElementById('orderTableContainer');
    const tableSpinner = document.getElementById('orderTableSpinner');
    const loadingOverlay = document.getElementById('orderTableOverlay');
    const loadingBar = document.getElementById('orderLoadingBar');
    const alertContainer = document.getElementById('ajaxAlertContainer');

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

    let debounceTimer = null;
    function fetchOrders(url = null) {
        setLoading(true);
        const targetUrl = url || (filterForm.action + '?' + new URLSearchParams(new FormData(filterForm)).toString());

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat data pesanan.');
            return res.json();
        })
        .then(data => {
            if (data.html !== undefined) {
                tableContainer.innerHTML = data.html;
                bindDynamicEvents();
            }
            if (data.stats) {
                const s = data.stats;
                const statIncomeEl = document.getElementById('statTotalIncome');
                if (statIncomeEl) statIncomeEl.textContent = 'Rp ' + Number(s.total_income || 0).toLocaleString('id-ID');

                const statUploadEl = document.getElementById('statPaymentUploaded');
                if (statUploadEl) statUploadEl.textContent = s.payment_uploaded || 0;

                const statShippingEl = document.getElementById('statShipping');
                if (statShippingEl) statShippingEl.textContent = (s.paid || 0) + (s.shipped || 0);

                const statPaidTextEl = document.getElementById('statPaidText');
                if (statPaidTextEl) statPaidTextEl.textContent = s.paid || 0;

                const statShippedTextEl = document.getElementById('statShippedText');
                if (statShippedTextEl) statShippedTextEl.textContent = s.shipped || 0;

                const statReceivedEl = document.getElementById('statReceived');
                if (statReceivedEl) statReceivedEl.textContent = s.received || 0;
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

    // Filter listeners
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchOrders();
        }, 400);
    });

    statusSelect.addEventListener('change', () => fetchOrders());

    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchOrders();
    });

    btnReset.addEventListener('click', function() {
        searchInput.value = '';
        statusSelect.value = 'all';
        fetchOrders();
    });

    function bindDynamicEvents() {
        // Pagination clicks
        tableContainer.querySelectorAll('.ajax-pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetchOrders(this.href);
            });
        });

        // Lihat Bukti Modal Trigger
        tableContainer.querySelectorAll('.btn-view-proof').forEach(btn => {
            btn.addEventListener('click', function() {
                const proofUrl = this.getAttribute('data-proof-url');
                const orderNumber = this.getAttribute('data-order-number');
                const recipientName = this.getAttribute('data-recipient-name');
                const uploadedAt = this.getAttribute('data-uploaded-at');
                const total = this.getAttribute('data-total');

                document.getElementById('modalProofImage').src = proofUrl;
                document.getElementById('modalProofSubTitle').textContent = `No. Pesanan: ${orderNumber}`;
                document.getElementById('modalProofRecipient').textContent = recipientName;
                document.getElementById('modalProofTotal').textContent = total;
                document.getElementById('modalProofDate').textContent = `${uploadedAt} WIB`;
                document.getElementById('modalProofDirectLink').href = proofUrl;

                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewProof'));
                modal.show();
            });
        });

        @if(auth()->user()->isSuperAdmin())
        // Hapus Pesanan Modal Trigger (Khusus Super Admin)
        tableContainer.querySelectorAll('.btn-delete-order').forEach(btn => {
            btn.addEventListener('click', function() {
                const modalEl = document.getElementById('modalConfirmDeleteOrder');
                const btnConfirm = document.getElementById('btnExecuteDeleteOrder');
                if (!modalEl || !btnConfirm) return;

                const id = this.getAttribute('data-id');
                const orderNumber = this.getAttribute('data-order-number');
                const recipientName = this.getAttribute('data-recipient-name');
                const url = this.getAttribute('data-url');

                document.getElementById('deleteOrderNumber').textContent = orderNumber;
                document.getElementById('deleteOrderRecipient').textContent = recipientName;
                btnConfirm.setAttribute('data-id', id);
                btnConfirm.setAttribute('data-url', url);

                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            });
        });
        @endif
    }

    @if(auth()->user()->isSuperAdmin())
    const btnExecuteDeleteOrder = document.getElementById('btnExecuteDeleteOrder');
    if (btnExecuteDeleteOrder) {
        btnExecuteDeleteOrder.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const id = this.getAttribute('data-id');
            const spinner = this.querySelector('.spinner-border');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            this.disabled = true;
            if (spinner) spinner.classList.remove('d-none');

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(errData => { throw errData; }).catch(() => { throw new Error('Gagal menghapus data pesanan.'); });
                }
                return res.json();
            })
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmDeleteOrder'))?.hide();
                if (data.success) {
                    showNotification(data.message, 'success');
                    const rows = document.querySelectorAll(`[data-order-row="${id}"]`);
                    if (rows.length > 0) {
                        rows.forEach(row => {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                        });
                        setTimeout(() => fetchOrders(), 350);
                    } else {
                        fetchOrders();
                    }
                } else {
                    showNotification(data.message || 'Gagal menghapus data pesanan.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification(err.message || 'Terjadi kesalahan saat menghapus data pesanan.', 'danger');
            })
            .finally(() => {
                this.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            });
        });
    }
    @endif

    bindDynamicEvents();
});
</script>
@endsection
