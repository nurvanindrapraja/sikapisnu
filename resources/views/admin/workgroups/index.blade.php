@extends('layouts.admin')

@section('title', 'Master Kelompok Kerja (Pokja) - SIKAP ISNU')
@section('header_title', 'Master Kelompok Kerja')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0">
            <i class="bi bi-person-workspace text-success me-2"></i> Master Kelompok Kerja (Pokja)
        </h4>
        <p class="text-secondary small m-0 mt-1">
            Kelola struktur khusus gugus tugas yang beranggotakan pengurus dari Seksi-Seksi PC & PAC ISNU
        </p>
    </div>
    <button type="button" class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddWorkgroup">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pokja Baru
    </button>
</div>

<!-- Notice Edukatif Mengenai Ketentuan Pokja -->
<div class="alert alert-success bg-success-subtle border-success-subtle rounded-4 p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-start gap-3">
        <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
            <i class="bi bi-info-circle-fill fs-5"></i>
        </div>
        <div class="small">
            <strong class="text-success-emphasis d-block mb-1">Ketentuan Keanggotaan Kelompok Kerja:</strong>
            <span class="text-secondary">
                Kelompok Kerja (Pokja) merupakan struktur gugus tugas khusus yang anggotanya direkrut <strong>khusus dari pengurus yang sudah bernaung di dalam Seksi</strong> (bukan di luar Seksi). Satu pengurus dapat bertugas pada Seksi strukturalnya sekaligus merangkap tugas di Kelompok Kerja.
            </span>
        </div>
    </div>
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

<!-- Metrik Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-diagram-3-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Pokja</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Pokja PC ISNU</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pc'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-geo-alt-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Pokja PAC ISNU</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pac'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Pengurus di Pokja</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['members'] }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter & Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    search: '{{ request('search') }}',
    level: '{{ request('level') }}',
    pac_id: '{{ request('pac_id') }}',
    status: '{{ request('status') }}',
    loading: false,
    fetchWorkgroups() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.level) params.append('level', this.level);
        if (this.pac_id) params.append('pac_id', this.pac_id);
        if (this.status) params.append('status', this.status);

        fetch(`{{ route('admin.workgroups.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('workgroupListContainer').innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    }
}" @refresh-workgroups.window="fetchWorkgroups()">
    <div class="card-header bg-white border-0 p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchWorkgroups()" class="form-control bg-light border-start-0" placeholder="Cari nama pokja / kode / deskripsi...">
                </div>
            </div>
            <div class="col-md-3">
                <select x-model="level" @change="fetchWorkgroups()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Tingkat Organisasi --</option>
                    <option value="PC ISNU">PC ISNU Kota Surabaya</option>
                    <option value="PAC ISNU">PAC ISNU (Kecamatan)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select x-model="pac_id" @change="fetchWorkgroups()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua PAC ISNU --</option>
                    @foreach($pacs as $pac)
                        <option value="{{ $pac->id }}">{{ $pac->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select x-model="status" @change="fetchWorkgroups()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Status --</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-Aktif</option>
                </select>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" @click="search=''; level=''; pac_id=''; status=''; fetchWorkgroups()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <div class="card-body p-0" id="workgroupListContainer">
        @include('admin.workgroups.partials.workgroup_list')
    </div>
</div>

<!-- Modal Tambah Kelompok Kerja Baru -->
<div class="modal fade" id="modalAddWorkgroup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.workgroups.store') }}" method="POST" x-data="{
                level: 'PC ISNU',
                loading: false,
                async submitForm() {
                    this.loading = true;
                    const formData = new FormData(this.$el);
                    try {
                        const res = await fetch(this.$el.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (res.ok && data.success) {
                            const modalEl = document.getElementById('modalAddWorkgroup');
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            this.$el.reset();
                            this.level = 'PC ISNU';
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                            window.dispatchEvent(new CustomEvent('refresh-workgroups'));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: data.message || 'Terjadi kesalahan.', type: 'error' } }));
                        }
                    } catch (err) {
                        this.loading = false;
                        console.error(err);
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: 'Terjadi kesalahan server.', type: 'error' } }));
                    }
                }
            }" @submit.prevent="submitForm()">
                @csrf
                <div class="modal-header bg-isnu text-white border-0 p-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Master Kelompok Kerja</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                </div>
                <div x-show="loading" class="progress rounded-0" style="height: 3px;" x-cloak>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tingkat Organisasi <span class="text-danger">*</span></label>
                        <select name="level" class="form-select" x-model="level" required :disabled="loading">
                            <option value="PC ISNU">PC ISNU Kota Surabaya (Tingkat Kota)</option>
                            <option value="PAC ISNU">PAC ISNU (Tingkat Kecamatan)</option>
                        </select>
                    </div>

                    <div class="mb-3" x-show="level === 'PAC ISNU'" x-cloak>
                        <label class="form-label fw-semibold">Pilih PAC ISNU <span class="text-danger">*</span></label>
                        <select name="pac_id" class="form-select" :required="level === 'PAC ISNU'" :disabled="loading">
                            <option value="">-- Pilih PAC ISNU --</option>
                            @foreach($pacs as $pac)
                                <option value="{{ $pac->id }}">{{ $pac->name }} ({{ $pac->kecamatan }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kelompok Kerja (Pokja) <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Kelompok Kerja Transformasi Digital & Media" required :disabled="loading">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Pokja <span class="text-secondary small">(Opsional)</span></label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: PKJ-DIGITAL" :disabled="loading">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi / Ruang Lingkup Tugas</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan tujuan, tugas pokok, atau ruang lingkup kelompok kerja ini..." :disabled="loading"></textarea>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="addIsActive" value="1" checked :disabled="loading">
                        <label class="form-check-label fw-semibold" for="addIsActive">Status Pokja Aktif</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                    <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-check-lg me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan Kelompok Kerja'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
