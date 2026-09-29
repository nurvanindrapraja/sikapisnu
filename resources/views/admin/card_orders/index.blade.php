@extends('layouts.admin')

@section('title', 'Pemesanan Kartu Fisik')
@section('header_title', 'Daftar Pemesanan Kartu Anggota ISNU Fisik')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    loading: false,
    status: '{{ request('status') }}',
    search: '{{ request('search') }}',
    proofUrl: '',
    proofName: '',
    isPdf: false,
    deleteUrl: '',
    deleteName: '',

    openProofModal(url, name) {
        this.proofUrl = url;
        this.proofName = name;
        this.isPdf = url.toLowerCase().includes('.pdf');
        const modalEl = document.getElementById('modalAdminViewProof');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    },

    async fetchOrders(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('cardOrdersContainer').innerHTML = html;
        } catch (e) {
            console.error('Error fetching card orders', e);
        } finally {
            this.loading = false;
        }
    },

    changeStatus(newStatus) {
        this.status = newStatus;
        const params = new URLSearchParams();
        if (this.status) params.set('status', this.status);
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.card_orders.index') }}?' + params.toString();
        this.fetchOrders(url);
    },

    onSearchSubmit() {
        const params = new URLSearchParams();
        if (this.status) params.set('status', this.status);
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.card_orders.index') }}?' + params.toString();
        this.fetchOrders(url);
    },

    async updateStatusAjax(e, targetUrl) {
        this.loading = true;
        const form = e.target;
        const formData = new FormData(form);
        try {
            const res = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                // Refresh current view
                const currentUrl = window.location.href;
                await this.fetchOrders(currentUrl);
            }
        } catch (err) {
            console.error('Failed to update status', err);
        } finally {
            this.loading = false;
        }
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchOrders(link.href);
        }
    },

    confirmDelete(url, name) {
        this.deleteUrl = url;
        this.deleteName = name;
        const modalEl = document.getElementById('modalAdminDeleteOrder');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    },

    async deleteOrderAjax(e) {
        this.loading = true;
        try {
            const res = await fetch(this.deleteUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: new FormData(e.target)
            });
            const data = await res.json();
            if (data.success) {
                const modalEl = document.getElementById('modalAdminDeleteOrder');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                await this.fetchOrders(window.location.href);
            }
        } catch (err) {
            console.error('Failed to delete order', err);
        } finally {
            this.loading = false;
        }
    }
}">
    <div class="card-body p-4">
        <!-- Top Title & Filter Tabs -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-card-heading text-success me-2"></i> Pemesanan Kartu Anggota ISNU Fisik</h5>
                <small class="text-muted">Kelola status pemesanan kartu anggota fisik (Dalam Pemesanan, Sudah Jadi, Dikirim, Diterima).</small>
            </div>
        </div>

        <!-- Filter Status Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" @click="changeStatus('')" 
                    :class="status === '' ? 'btn-dark text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Semua Pemesanan
            </button>
            <button type="button" @click="changeStatus('pending')" 
                    :class="status === 'pending' ? 'btn-warning text-dark' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                <i class="bi bi-hourglass-split me-1"></i> Dalam Pemesanan
            </button>
            <button type="button" @click="changeStatus('printed')" 
                    :class="status === 'printed' ? 'btn-info text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                <i class="bi bi-printer-fill me-1"></i> Sudah Jadi
            </button>
            <button type="button" @click="changeStatus('shipped')" 
                    :class="status === 'shipped' || status === 'delivered' ? 'btn-primary text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                <i class="bi bi-truck me-1"></i> Kartu Dikirim
            </button>
            <button type="button" @click="changeStatus('received')" 
                    :class="status === 'received' ? 'btn-success text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                <i class="bi bi-box-seam-fill me-1"></i> Kartu Diterima
            </button>
        </div>

        <!-- Search Bar -->
        <form @submit.prevent="onSearchSubmit()" class="row g-2 mb-4">
            <div class="col-md-9">
                <input type="text" x-model="search" class="form-control" placeholder="Cari nama pemesan, no. anggota, no. HP...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100 fw-bold"><i class="bi bi-search me-1"></i> Cari Pemesanan</button>
            </div>
        </form>

        <!-- Progress Bar Indicator -->
        <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
        </div>

        <!-- AJAX Container for Desktop Table & Mobile Card View -->
        <div id="cardOrdersContainer" 
             @click="handlePagination($event)" 
             :class="{ 'opacity-50 pointer-events-none': loading }" 
             style="transition: opacity 0.2s ease;">
            @include('admin.card_orders.partials.order_list')
        </div>

        <!-- Modal Popup Bukti Transfer Admin -->
        <div class="modal fade" id="modalAdminViewProof" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 text-center">
                    <div class="modal-header bg-success text-white border-0 rounded-top-4">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-receipt me-2"></i> Bukti Transfer: <span x-text="proofName"></span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <template x-if="isPdf">
                            <iframe :src="proofUrl" class="w-100 rounded-3 border" style="height: 500px;"></iframe>
                        </template>
                        <template x-if="!isPdf">
                            <div class="bg-light p-2 rounded-3 border">
                                <img :src="proofUrl" class="img-fluid rounded-3 shadow-sm" style="max-height: 500px; object-fit: contain;" alt="Bukti Transfer">
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-between">
                        <a :href="proofUrl" target="_blank" download class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-download me-1"></i> Unduh File
                        </a>
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Pemesanan -->
        <div class="modal fade" id="modalAdminDeleteOrder" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 text-center">
                    <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Data
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <i class="bi bi-trash text-danger display-3 d-block mb-3"></i>
                        <p class="fs-6 text-dark mb-1">Apakah Anda yakin ingin menghapus data pemesanan kartu fisik untuk:</p>
                        <h6 class="fw-bold text-danger mb-3" x-text="deleteName"></h6>
                        <p class="small text-muted mb-0">Tindakan ini tidak dapat dibatalkan dan berkas bukti transfer terkait akan dihapus.</p>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                        <form :action="deleteUrl" method="POST" class="d-inline" @submit.prevent="deleteOrderAjax($event)">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                                <i class="bi bi-trash me-1"></i> Ya, Hapus Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
