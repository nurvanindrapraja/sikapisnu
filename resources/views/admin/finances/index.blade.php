@extends('layouts.admin')

@section('title', 'Pengelolaan Keuangan Kas ISNU')

@section('content')
<div class="container-fluid px-0" x-data="financesManager()">
    <!-- Header Page -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Keuangan & Kas</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Pengelolaan Keuangan Kas ISNU</h4>
            <p class="text-muted small mb-0">Kelola arus kas uang masuk dan uang keluar organisasi secara transparan dan akuntabel.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.finances.export') }}" class="btn btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i>
                <span>Export Laporan (CSV)</span>
            </a>
            <a href="{{ route('admin.finances.create', ['type' => 'in']) }}" class="btn btn-success rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Catat Uang Masuk</span>
            </a>
            <a href="{{ route('admin.finances.create', ['type' => 'out']) }}" class="btn btn-danger rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-dash-circle-fill"></i>
                <span>Catat Uang Keluar</span>
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

    <!-- Financial Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- Saldo Kas Bersih -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-white-50 small fw-semibold text-uppercase tracking-wider">Saldo Kas Saat Ini</span>
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-wallet2 text-white fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1">
                        Rp {{ number_format($currentBalance, 0, ',', '.') }}
                    </h3>
                    <div class="small text-white-50">
                        {{ $currentBalance >= 0 ? 'Surplus Kas Organisasi' : 'Defisit Kas Organisasi' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Uang Masuk -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Uang Masuk</span>
                        <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-arrow-down-left-circle-fill fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-success mb-1">
                        Rp {{ number_format($totalIncomeAll, 0, ',', '.') }}
                    </h4>
                    <div class="small text-muted">
                        Pemasukan kas keseluruhan
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Uang Keluar -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Uang Keluar</span>
                        <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-danger mb-1">
                        Rp {{ number_format($totalExpenseAll, 0, ',', '.') }}
                    </h4>
                    <div class="small text-muted">
                        Pengeluaran kas keseluruhan
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Transaksi</span>
                        <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-receipt-cutoff fs-5"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">
                        {{ number_format($totalTransactionsCount) }}
                    </h4>
                    <div class="small text-muted">
                        Catatan transaksi tercatat
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card & Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <!-- Filter Header -->
        <div class="card-header bg-white p-4 border-bottom">
            <div class="row g-3 align-items-center">
                <!-- Search Input with Debounce & Spinner -->
                <div class="col-12 col-lg-4">
                    <div class="position-relative">
                        <template x-if="!isLoading">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        </template>
                        <template x-if="isLoading">
                            <span class="spinner-border spinner-border-sm position-absolute top-50 start-0 translate-middle-y ms-3 text-primary" role="status" aria-hidden="true"></span>
                        </template>
                        <input type="text"
                               class="form-control rounded-pill ps-5 bg-light border-0"
                               placeholder="Cari judul, keterangan, kode transaksi..."
                               x-model="searchQuery"
                               @input.debounce.300ms="applyFilter()">
                    </div>
                </div>

                <!-- Filter Tipe Transaksi -->
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select rounded-pill bg-light border-0" x-model="transactionType" @change="applyFilter()" aria-label="Filter Tipe Transaksi">
                        <option value="all">Semua Tipe</option>
                        <option value="in">Uang Masuk (+)</option>
                        <option value="out">Uang Keluar (-)</option>
                    </select>
                </div>

                <!-- Filter Kategori -->
                <div class="col-6 col-md-3 col-lg-2">
                    <select class="form-select rounded-pill bg-light border-0" x-model="category" @change="applyFilter()" aria-label="Filter Kategori">
                        <option value="all">Semua Kategori</option>
                        <optgroup label="Uang Masuk">
                            @foreach(\App\Models\FinancialTransaction::CATEGORIES_IN as $catIn)
                                <option value="{{ $catIn }}">{{ $catIn }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Uang Keluar">
                            @foreach(\App\Models\FinancialTransaction::CATEGORIES_OUT as $catOut)
                                <option value="{{ $catOut }}">{{ $catOut }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <!-- Filter Tanggal Mulai -->
                <div class="col-6 col-md-3 col-lg-2">
                    <input type="date" class="form-control rounded-pill bg-light border-0" x-model="startDate" @change="applyFilter()" title="Tanggal Mulai">
                </div>

                <!-- Filter Tanggal Selesai -->
                <div class="col-6 col-md-3 col-lg-2">
                    <input type="date" class="form-control rounded-pill bg-light border-0" x-model="endDate" @change="applyFilter()" title="Tanggal Selesai">
                </div>
            </div>

            <!-- Active Filter Badge & Reset -->
            <template x-if="isFiltered()">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top small">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Filter Aktif:</span>
                        <span class="badge bg-light text-dark border rounded-pill" x-show="transactionType !== 'all'" x-text="'Tipe: ' + (transactionType === 'in' ? 'Uang Masuk' : 'Uang Keluar')"></span>
                        <span class="badge bg-light text-dark border rounded-pill" x-show="category !== 'all'" x-text="'Kategori: ' + category"></span>
                        <span class="badge bg-light text-dark border rounded-pill" x-show="startDate" x-text="'Dari: ' + startDate"></span>
                        <span class="badge bg-light text-dark border rounded-pill" x-show="endDate" x-text="'Sampai: ' + endDate"></span>
                        <span class="badge bg-light text-dark border rounded-pill" x-show="searchQuery" x-text="'Pencarian: ' + searchQuery"></span>
                    </div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-danger p-0 fw-semibold" @click="resetFilter()">
                        <i class="bi bi-x-circle me-1"></i> Reset Semua Filter
                    </button>
                </div>
            </template>
        </div>

        <!-- Table / Cards Container -->
        <div id="transactionsContainer" class="position-relative">
            <!-- Loading Overlay -->
            <template x-if="isLoading">
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center"
                     style="z-index: 10;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="small text-muted mt-2 fw-semibold">Memuat transaksi keuangan...</div>
                    </div>
                </div>
            </template>

            <!-- Initial Server-Rendered Content -->
            <div id="transactionsContent">
                @include('admin.finances.partials.transaction_list')
            </div>
        </div>
    </div>

    <!-- Modal Hapus Transaksi -->
    <div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="bi bi-trash3-fill fs-2"></i>
                        </div>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-2" id="deleteTransactionModalLabel">Hapus Transaksi Keuangan</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus catatan transaksi:
                    </p>
                    <div class="fw-bold text-dark fs-6 my-2 bg-light p-2 rounded-3 border">
                        <span x-text="deleteTitle"></span>
                    </div>
                    <div class="fw-bold fs-5 text-danger mb-3" x-text="deleteAmount"></div>
                    <p class="text-muted small mb-0">
                        Tindakan ini akan mempengaruhi rekapitulasi saldo kas dan tercatat pada audit log.
                    </p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <form :action="'{{ url('admin/finances') }}/' + deleteId" method="POST" class="flex-fill m-0"
                          @submit="isDeleting = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger text-white rounded-pill w-100 py-2 fw-semibold shadow-sm" :disabled="isDeleting">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isDeleting" x-cloak></span>
                            <i class="bi bi-trash me-1" x-show="!isDeleting"></i>
                            <span x-text="isDeleting ? 'Menghapus...' : 'Ya, Hapus Transaksi'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Transaction Modal -->
    <div class="modal fade" id="previewTransactionModal" tabindex="-1" aria-labelledby="previewTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden" id="previewTransactionModalContent">
                <!-- Content will be loaded dynamically via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function financesManager() {
    return {
        searchQuery: '{{ request('search', '') }}',
        transactionType: '{{ request('type', 'all') }}',
        category: '{{ request('category', 'all') }}',
        startDate: '{{ request('start_date', '') }}',
        endDate: '{{ request('end_date', '') }}',
        isLoading: false,
        deleteId: null,
        deleteTitle: '',
        deleteAmount: '',
        isDeleting: false,
        previewId: null,

        isFiltered() {
            return this.searchQuery !== '' ||
                   this.transactionType !== 'all' ||
                   this.category !== 'all' ||
                   this.startDate !== '' ||
                   this.endDate !== '';
        },

        resetFilter() {
            this.searchQuery = '';
            this.transactionType = 'all';
            this.category = 'all';
            this.startDate = '';
            this.endDate = '';
            this.applyFilter();
        },

        applyFilter() {
            this.isLoading = true;
            const params = new URLSearchParams();

            if (this.searchQuery) params.append('search', this.searchQuery);
            if (this.transactionType && this.transactionType !== 'all') params.append('type', this.transactionType);
            if (this.category && this.category !== 'all') params.append('category', this.category);
            if (this.startDate) params.append('start_date', this.startDate);
            if (this.endDate) params.append('end_date', this.endDate);

            const url = `{{ route('admin.finances.index') }}?${params.toString()}`;

            // Update browser URL query string without reloading
            window.history.replaceState({}, '', url);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('transactionsContent').innerHTML = html;
                this.isLoading = false;
            })
            .catch(err => {
                console.error('Error fetching filtered transactions:', err);
                this.isLoading = false;
            });
        },

        openPreviewModal(id) {
            this.previewId = id;
            const modalEl = document.getElementById('previewTransactionModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }

            const contentEl = document.getElementById('previewTransactionModalContent');
            contentEl.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Memuat pratinjau...</span>
                    </div>
                    <div class="text-muted small mt-3">Sedang memuat pratinjau transaksi kas...</div>
                </div>
            `;

            fetch('{{ url('/admin/finances') }}/' + id + '/preview', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.text();
            })
            .then(html => {
                contentEl.innerHTML = html;
            })
            .catch(err => {
                console.error('Error loading transaction preview:', err);
                contentEl.innerHTML = `
                    <div class="modal-header border-bottom px-4 py-3 bg-light">
                        <h5 class="modal-title fw-bold text-danger mb-0">Gagal Memuat Pratinjau</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Terjadi kesalahan saat memuat pratinjau transaksi. Silakan coba kembali atau buka langsung halaman rincian.
                        </div>
                    </div>
                    <div class="modal-footer border-top px-4 py-2 bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                        <a href="{{ url('/admin/finances') }}/${id}" class="btn btn-primary rounded-pill px-3">Buka Rincian</a>
                    </div>
                `;
            });
        },

        openDeleteModal(id, title, amount) {
            this.deleteId = id;
            this.deleteTitle = title;
            this.deleteAmount = amount;
            this.isDeleting = false;
            const modalEl = document.getElementById('deleteTransactionModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }
    }
}
</script>
@endsection
