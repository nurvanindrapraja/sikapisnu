@extends('layouts.admin')

@section('title', 'Verifikasi Keanggotaan')
@section('header_title', 'Verifikasi Keanggotaan')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    loading: false,
    status: '{{ $status }}',
    search: '{{ request('search') }}',

    async fetchVerifikasiData(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('verifikasiContainer').innerHTML = html;
        } catch (e) {
            console.error('Error fetching verifikasi data', e);
        } finally {
            this.loading = false;
        }
    },

    changeStatus(newStatus) {
        this.status = newStatus;
        const params = new URLSearchParams();
        if (this.status) params.set('status', this.status);
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.verifikasi.index') }}?' + params.toString();
        this.fetchVerifikasiData(url);
    },

    onSearchSubmit() {
        const params = new URLSearchParams();
        if (this.status) params.set('status', this.status);
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.verifikasi.index') }}?' + params.toString();
        this.fetchVerifikasiData(url);
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchVerifikasiData(link.href);
        }
    }
}">
    <div class="card-body p-4">
        <!-- Filter Status Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" @click="changeStatus('menunggu_verifikasi')" 
                    :class="status === 'menunggu_verifikasi' ? 'btn-danger' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Menunggu Verifikasi
            </button>
            <button type="button" @click="changeStatus('perbaikan')" 
                    :class="status === 'perbaikan' ? 'btn-warning text-dark' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Perlu Perbaikan
            </button>
            <button type="button" @click="changeStatus('terverifikasi')" 
                    :class="status === 'terverifikasi' ? 'btn-success' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Terverifikasi
            </button>
            <button type="button" @click="changeStatus('ditolak')" 
                    :class="status === 'ditolak' ? 'btn-secondary text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Ditolak
            </button>
            <button type="button" @click="changeStatus('all')" 
                    :class="status === 'all' ? 'btn-dark text-white' : 'btn-light text-dark'" 
                    class="btn rounded-pill px-3 py-1.5 fw-semibold border-0">
                Semua Data
            </button>
        </div>

        <!-- Search Bar -->
        <form @submit.prevent="onSearchSubmit()" class="row g-2 mb-4">
            <div class="col-md-9">
                <input type="text" x-model="search" class="form-control" placeholder="Cari nama, NIK, email, no. HP...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100 fw-bold"><i class="bi bi-search me-1"></i> Cari Data</button>
            </div>
        </form>

        <!-- Progress Bar Indicator -->
        <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
        </div>

        <!-- Table & Mobile Cardview Container -->
        <div id="verifikasiContainer" 
             @click="handlePagination($event)" 
             :class="{ 'opacity-50 pointer-events-none': loading }" 
             style="transition: opacity 0.2s ease;">
            @include('admin.verifikasi.partials.member_list')
        </div>
    </div>
</div>
@endsection
