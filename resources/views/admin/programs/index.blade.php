@extends('layouts.admin')

@section('title', 'Master Program Kerja Seksi - SIKAP ISNU')
@section('header_title', 'Master Program Kerja')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0">
            <i class="bi bi-card-checklist text-success me-2"></i> Master Program Kerja Seksi
        </h4>
        <p class="text-secondary small m-0 mt-1">
            Kelola program kerja masing-masing Seksi / Bidang PC & PAC ISNU lengkap dengan indikator dan target capaian
        </p>
    </div>
    @if(auth()->user()->canManageAnyProgramOrEvent())
    <button type="button" class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddProgram">
        <i class="bi bi-plus-lg me-1"></i> Tambah Program Baru
    </button>
    @endif
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
                    <i class="bi bi-journal-bookmark-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Program</span>
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
                    <span class="text-muted small d-block">Program PC ISNU</span>
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
                    <span class="text-muted small d-block">Program PAC ISNU</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pac'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-calendar-check-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Kegiatan Terhubung</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['events_count'] }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter & Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    search: '{{ request('search') }}',
    section_id: '{{ request('section_id') }}',
    level: '{{ request('level') }}',
    status: '{{ request('status') }}',
    loading: false,
    fetchPrograms() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.section_id) params.append('section_id', this.section_id);
        if (this.level) params.append('level', this.level);
        if (this.status) params.append('status', this.status);

        fetch(`{{ route('admin.programs.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('programListContainer').innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    }
}" @refresh-programs.window="fetchPrograms()">
    <div class="card-header bg-white border-0 p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchPrograms()" class="form-control bg-light border-start-0" placeholder="Cari nama program / indikator / target...">
                </div>
            </div>
            <div class="col-md-3">
                <select x-model="section_id" @change="fetchPrograms()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Seksi / Bidang --</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}">
                            [{{ $sec->level }}] {{ $sec->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select x-model="level" @change="fetchPrograms()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="PC ISNU">PC ISNU</option>
                    <option value="PAC ISNU">PAC ISNU</option>
                </select>
            </div>
            <div class="col-md-2">
                <select x-model="status" @change="fetchPrograms()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Status --</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-Aktif</option>
                </select>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" @click="search=''; section_id=''; level=''; status=''; fetchPrograms()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <div class="card-body p-0" id="programListContainer">
        @include('admin.programs.partials.program_list')
    </div>
</div>

<!-- Modal Tambah Program Kerja Baru -->
<div class="modal fade" id="modalAddProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.programs.store') }}" method="POST" x-data="{
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
                            const modalEl = document.getElementById('modalAddProgram');
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            this.$el.reset();
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                            window.dispatchEvent(new CustomEvent('refresh-programs'));
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
                    <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Master Program Kerja Seksi</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                </div>
                <div x-show="loading" class="progress rounded-0" style="height: 3px;" x-cloak>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Pilih Seksi / Bidang Pengampu <span class="text-danger">*</span></label>
                            <select name="section_id" class="form-select" required :disabled="loading">
                                @if(auth()->user()->isWakilPengurus() && !auth()->user()->canManageAllProgramsAndEvents())
                                    @php $mySection = auth()->user()->getOfficerSection(); @endphp
                                    @if($mySection)
                                        <option value="{{ $mySection->id }}" selected>[{{ $mySection->level }}] {{ $mySection->name }} @if($mySection->pac) ({{ $mySection->pac->name }}) @endif</option>
                                    @else
                                        <option value="">-- Anda belum memiliki Seksi assigned --</option>
                                    @endif
                                @else
                                    <option value="">-- Pilih Seksi / Bidang --</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec->id }}">
                                            [{{ $sec->level }}] {{ $sec->name }} @if($sec->pac) ({{ $sec->pac->name }}) @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tahun / Periode <span class="text-secondary small">(Opsional)</span></label>
                            <input type="text" name="year" class="form-control" value="{{ date('Y') }}" placeholder="Contoh: 2026 atau 2026-2030" :disabled="loading">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Program Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Program Penguatan Ideologi & Pelatihan Kader Intelektual" required :disabled="loading">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Program <span class="text-secondary small">(Opsional)</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Contoh: PRG-KDR-01" :disabled="loading">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Indikator Keberhasilan / Capaian <span class="text-danger">*</span></label>
                            <textarea name="indicator" class="form-control" rows="3" placeholder="Contoh: Terlaksananya pembinaan ideologi kader sarjana dan peningkatan pemahaman ASWAJA..." required :disabled="loading"></textarea>
                            <div class="form-text small text-muted">Kriteria atau parameter untuk mengukur ketercapaian program.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Capaian <span class="text-danger">*</span></label>
                            <textarea name="target" class="form-control" rows="3" placeholder="Contoh: 100 Kader sarjana tersertifikasi dalam 2 angkatan pelatihan..." required :disabled="loading"></textarea>
                            <div class="form-text small text-muted">Ukuran hasil kuantitatif maupun kualitatif yang diharapkan.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Program Kerja</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Tuliskan latar belakang, tujuan, atau mekanisme program..." :disabled="loading"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="addProgramIsActive" value="1" checked :disabled="loading">
                                <label class="form-check-label fw-semibold" for="addProgramIsActive">Status Program Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                    <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-check-lg me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan Program'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
