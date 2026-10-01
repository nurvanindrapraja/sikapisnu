@extends('layouts.admin')

@section('title', 'Master Seksi Kepengurusan - SIKAP ISNU')
@section('header_title', 'Master Seksi Kepengurusan')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-diagram-2-fill text-success me-2"></i> Master Seksi Kepengurusan</h4>
        <p class="text-secondary small m-0 mt-1">Kelola data Seksi / Bidang pada PC ISNU Kota Surabaya & PAC ISNU Kecamatan</p>
    </div>
    <button type="button" class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSection">
        <i class="bi bi-plus-lg me-1"></i> Tambah Seksi Baru
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Card Filter & Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    search: '{{ request('search') }}',
    level: '{{ request('level') }}',
    pac_id: '{{ request('pac_id') }}',
    loading: false,
    fetchSections() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.level) params.append('level', this.level);
        if (this.pac_id) params.append('pac_id', this.pac_id);

        fetch(`{{ route('admin.sections.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('sectionListContainer').innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    }
}">
    <div class="card-header bg-white border-0 p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchSections()" class="form-control bg-light border-start-0" placeholder="Cari nama seksi / keterangan...">
                </div>
            </div>
            <div class="col-md-3">
                <select x-model="level" @change="fetchSections()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Tingkat Organisasi --</option>
                    <option value="PC ISNU">PC ISNU Kota Surabaya</option>
                    <option value="PAC ISNU">PAC ISNU (Kecamatan)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select x-model="pac_id" @change="fetchSections()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua PAC ISNU --</option>
                    @foreach($pacs as $pac)
                        <option value="{{ $pac->id }}">{{ $pac->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" @click="search=''; level=''; pac_id=''; fetchSections()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <div class="card-body p-0" id="sectionListContainer">
        @include('admin.sections.partials.section_list')
    </div>
</div>

<!-- Modal Tambah Seksi Baru -->
<div class="modal fade" id="modalAddSection" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-isnu text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Master Seksi Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.sections.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4" x-data="{ level: 'PC ISNU' }">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tingkat Organisasi <span class="text-danger">*</span></label>
                        <select name="level" class="form-select" x-model="level" required>
                            <option value="PC ISNU">PC ISNU Kota Surabaya (Tingkat Kota)</option>
                            <option value="PAC ISNU">PAC ISNU (Tingkat Kecamatan)</option>
                        </select>
                    </div>

                    <div class="mb-3" x-show="level === 'PAC ISNU'" x-cloak>
                        <label class="form-label fw-semibold">Pilih PAC ISNU <span class="text-danger">*</span></label>
                        <select name="pac_id" class="form-select" :required="level === 'PAC ISNU'">
                            <option value="">-- Pilih PAC ISNU --</option>
                            @foreach($pacs as $pac)
                                <option value="{{ $pac->id }}">{{ $pac->name }} ({{ $pac->kecamatan }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Seksi / Bidang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Seksi Sains dan Teknologi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Seksi <span class="text-secondary small">(Opsional)</span></label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: SKS-SAINTEK">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi / Keterangan</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan deskripsi atau tugas seksi ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4">Simpan Seksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
