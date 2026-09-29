@extends('layouts.admin')

@section('title', 'Data Sampah')
@section('header_title', 'Data Sampah')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    loading: false,
    search: '{{ request('search') }}',

    forceDeleteModal: null,
    forceDeleteUrl: '',
    forceDeleteName: '',
    confirmForceDelete(url, name) {
        this.forceDeleteUrl = url;
        this.forceDeleteName = name;
        if (!this.forceDeleteModal) {
            this.forceDeleteModal = new bootstrap.Modal(document.getElementById('modalConfirmForceDelete'));
        }
        this.forceDeleteModal.show();
    },

    restoreModal: null,
    restoreUrl: '',
    restoreName: '',
    confirmRestore(url, name) {
        this.restoreUrl = url;
        this.restoreName = name;
        if (!this.restoreModal) {
            this.restoreModal = new bootstrap.Modal(document.getElementById('modalConfirmRestore'));
        }
        this.restoreModal.show();
    },

    async fetchTrashedData(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('trashedContainer').innerHTML = html;
        } catch (e) {
            console.error('Error fetching trashed data', e);
        } finally {
            this.loading = false;
        }
    },

    onSearchSubmit() {
        const params = new URLSearchParams();
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.sampah.index') }}?' + params.toString();
        this.fetchTrashedData(url);
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchTrashedData(link.href);
        }
    }
}">
    <div class="card-body p-4">
        <!-- Header Info without back button -->
        <div class="mb-4">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-trash3-fill text-danger me-2"></i> Data Sampah Anggota</h5>
            <small class="text-muted">Daftar anggota yang telah dihapus sementara. Anda dapat memulihkan data atau menghapusnya secara permanen.</small>
        </div>

        <!-- Search Bar -->
        <form @submit.prevent="onSearchSubmit()" class="row g-2 mb-4">
            <div class="col-md-9">
                <input type="text" x-model="search" class="form-control" placeholder="Cari nama, NIK, nomor anggota, email...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-danger w-100 fw-bold"><i class="bi bi-search me-1"></i> Cari Data Sampah</button>
            </div>
        </form>

        <!-- Progress Bar Indicator -->
        <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger w-100"></div>
        </div>

        <!-- AJAX Container for Desktop Table & Mobile Card View -->
        <div id="trashedContainer" 
             @click="handlePagination($event)" 
             :class="{ 'opacity-50 pointer-events-none': loading }" 
             style="transition: opacity 0.2s ease;">
            @include('admin.sampah.partials.trashed_list')
        </div>
    </div>

    <!-- Modal Konfirmasi Pulihkan Data -->
    <div class="modal fade" id="modalConfirmRestore" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Konfirmasi Pulihkan Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-success mb-3">
                        <i class="bi bi-arrow-counterclockwise display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Pulihkan Data Anggota Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda yakin ingin memulihkan data anggota <strong x-text="restoreName" class="text-dark"></strong>? 
                        Sistem akan mengecek ketersediaan email sebelum memulihkan data.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <form :action="restoreUrl" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-arrow-counterclockwise me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Memulihkan...' : 'Ya, Pulihkan Data'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Permanen -->
    <div class="modal fade" id="modalConfirmForceDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> Hapus Permanen Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-x-circle-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hapus Permanen Anggota Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda benar-benar ingin menghapus permanen data anggota <strong x-text="forceDeleteName" class="text-dark"></strong>? 
                        Data yang dihapus permanen <span class="text-danger fw-bold">TIDAK DAPAT DIPULIHKAN KEMBALI</span>.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <form :action="forceDeleteUrl" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-trash-fill me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menghapus...' : 'Ya, Hapus Permanen'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
