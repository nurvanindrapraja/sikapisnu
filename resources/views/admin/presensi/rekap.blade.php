@extends('layouts.admin')

@section('title', 'Rekap Tingkat Kehadiran Kader - SIKAP ISNU')
@section('header_title', 'Tingkat Kehadiran Kegiatan Kader')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-graph-up-arrow text-success me-2"></i> Rekap Tingkat Kehadiran Kegiatan</h4>
        <p class="text-secondary small m-0 mt-1">Pantau keaktifan dan kehadiran semua anggota, pengurus, serta calon anggota ISNU Surabaya</p>
    </div>
    <div>
        <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Kelola Kegiatan
        </a>
    </div>
</div>

<!-- Card Stat Summary -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center p-3" style="width: 54px; height: 54px;">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small fw-medium d-block">Total Kader Terdata</span>
                    <h4 class="fw-bold text-dark m-0">{{ number_format($totalKaderCount) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center p-3" style="width: 54px; height: 54px;">
                    <i class="bi bi-patch-check-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small fw-medium d-block">Total Presensi Masuk</span>
                    <h4 class="fw-bold text-dark m-0">{{ number_format($totalPresensiCount) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center p-3" style="width: 54px; height: 54px;">
                    <i class="bi bi-trophy-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small fw-medium d-block">Kader Paling Aktif</span>
                    <h6 class="fw-bold text-dark m-0 text-truncate" style="max-width: 180px;">{{ $topParticipant ? ($topParticipant->full_name ?? $topParticipant->name) : '-' }}</h6>
                    <small class="text-success fw-semibold fs-xs">
                        @if($topParticipant)
                            <i class="bi bi-check2-all me-1"></i>{{ $topParticipant->presences_count }} Kegiatan Hadir
                        @else
                            Belum Ada Data
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card with Alpine.js -->
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    search: '{{ request('search') }}',
    status: '{{ request('status') }}',
    pac_id: '{{ request('pac_id') }}',
    sort: '{{ request('sort', 'most_active') }}',
    loading: false,
    selectedMember: null,
    loadingDetail: false,

    fetchRekap() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.status) params.append('status', this.status);
        if (this.pac_id) params.append('pac_id', this.pac_id);
        if (this.sort) params.append('sort', this.sort);

        fetch(`{{ route('admin.presensi.rekap') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('rekapListContainer').innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    },

    openDetail(memberId) {
        this.loadingDetail = true;
        this.selectedMember = null;
        
        const modalEl = document.getElementById('modalDetailPresensi');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        fetch(`/admin/presensi/detail-kader/${memberId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            this.selectedMember = data;
            this.loadingDetail = false;
        })
        .catch(err => {
            console.error(err);
            this.loadingDetail = false;
        });
    }
}">
    <!-- Header Filter Bar -->
    <div class="card-header bg-white border-0 p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchRekap()" class="form-control bg-light border-start-0" placeholder="Cari nama / NIK / No. Anggota...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select x-model="status" @change="fetchRekap()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Status --</option>
                    <option value="pengurus">Pengurus</option>
                    <option value="anggota">Anggota</option>
                    <option value="calon anggota">Calon Anggota</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select x-model="pac_id" @change="fetchRekap()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua PAC ISNU --</option>
                    @foreach($pacs as $pac)
                        <option value="{{ $pac->id }}">{{ $pac->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select x-model="sort" @change="fetchRekap()" class="form-select form-select-sm bg-light">
                    <option value="most_active">Kehadiran Terbanyak</option>
                    <option value="least_active">Kehadiran Tersedikit</option>
                    <option value="name_asc">Nama (A-Z)</option>
                    <option value="latest">Paling Baru</option>
                </select>
            </div>
            <div class="col-6 col-md-1 text-end">
                <button type="button" @click="search=''; status=''; pac_id=''; sort='most_active'; fetchRekap()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="card-body p-0" id="rekapListContainer">
        @include('admin.presensi.partials.rekap_list')
    </div>

    <!-- Popup Window (Modal Detail Kehadiran Kader) -->
    <div class="modal fade" id="modalDetailPresensi" tabindex="-1" aria-labelledby="modalDetailPresensiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-success text-white p-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill fs-4"></i>
                        <h5 class="modal-title fw-bold" id="modalDetailPresensiLabel">Detail Riwayat Kehadiran Kegiatan</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <!-- Loading Spinner -->
                    <div x-show="loadingDetail" class="text-center py-5">
                        <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 fw-medium">Mengambil riwayat kegiatan kader...</p>
                    </div>

                    <!-- Detail Content -->
                    <template x-if="!loadingDetail && selectedMember">
                        <div>
                            <!-- Header Info Kader -->
                            <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
                                <div class="row align-items-center g-3">
                                    <div class="col-auto">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 60px; height: 60px;" x-text="selectedMember.member.name.charAt(0).toUpperCase()">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <h5 class="fw-bold text-dark mb-1" x-text="selectedMember.member.name"></h5>
                                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
                                            <span x-show="selectedMember.member.nik"><i class="bi bi-card-heading me-1"></i>NIK: <strong x-text="selectedMember.member.nik"></strong></span>
                                            <span class="mx-1">•</span>
                                            <span><i class="bi bi-building me-1"></i>PAC: <strong x-text="selectedMember.member.pac_name"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-md-auto text-md-end">
                                        <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6 mb-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Total <span x-text="selectedMember.member.total_presences"></span> Kegiatan
                                        </div>
                                        <div class="small text-muted" x-text="'Status: ' + selectedMember.member.membership_status"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- List Kegiatan yang Sudah Dihadiri -->
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calendar2-check text-success me-2"></i>Daftar Kegiatan Yang Dihadiri:</h6>
                            
                            <template x-if="selectedMember.presences && selectedMember.presences.length > 0">
                                <div class="table-responsive bg-white rounded-3 shadow-sm">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Nama Kegiatan</th>
                                                <th>Waktu Hadir</th>
                                                <th>Metode</th>
                                                <th>Lokasi</th>
                                                <th>Catatan / Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(pres, idx) in selectedMember.presences" :key="pres.id">
                                                <tr>
                                                    <td class="fw-medium text-muted" x-text="idx + 1"></td>
                                                    <td>
                                                        <div class="fw-bold text-dark" x-text="pres.event_title"></div>
                                                    </td>
                                                    <td>
                                                        <div class="small text-dark fw-medium" x-text="pres.presence_date"></div>
                                                        <div class="fs-xs text-muted" x-text="pres.presence_time"></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge" 
                                                              :class="pres.method && pres.method.toLowerCase() === 'luring' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-info-subtle text-info border border-info-subtle'"
                                                              x-text="pres.method">
                                                        </span>
                                                    </td>
                                                    <td class="small text-muted" x-text="pres.location"></td>
                                                    <td class="small text-muted" x-text="pres.notes || '-'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <template x-if="!selectedMember.presences || selectedMember.presences.length === 0">
                                <div class="text-center py-4 bg-white rounded-3 border">
                                    <i class="bi bi-calendar-x text-muted display-6 d-block mb-2"></i>
                                    <p class="text-muted mb-0">Kader ini belum pernah mengisi presensi pada kegiatan manapun.</p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="modal-footer bg-white border-top-0 p-3">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
