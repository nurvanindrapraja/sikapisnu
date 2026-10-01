@extends('layouts.admin')

@section('title', 'Manajemen Kegiatan ISNU & Presensi - SIKAP ISNU')
@section('header_title', 'Kegiatan & Presensi ISNU')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark m-0"><i class="bi bi-calendar-event-fill text-success me-2"></i> Manajemen Kegiatan & Presensi Kehadiran</h4>
        <p class="text-secondary small m-0 mt-1">Rencanakan kegiatan ISNU, kelola link presensi berdurasi, LPJ, dan dokumentasi foto</p>
    </div>
    <button type="button" class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddEvent">
        <i class="bi bi-plus-lg me-1"></i> Rencanakan Kegiatan Baru
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
    method: '{{ request('method') }}',
    status: '{{ request('status') }}',
    loading: false,
    fetchEvents() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.method) params.append('method', this.method);
        if (this.status) params.append('status', this.status);

        fetch(`{{ route('admin.events.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('eventListContainer').innerHTML = html;
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
                    <input type="text" x-model="search" @input.debounce.400ms="fetchEvents()" class="form-control bg-light border-start-0" placeholder="Cari nama kegiatan / lokasi...">
                </div>
            </div>
            <div class="col-md-3">
                <select x-model="method" @change="fetchEvents()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Metode --</option>
                    <option value="luring">Luring (Offline)</option>
                    <option value="daring">Daring (Online)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select x-model="status" @change="fetchEvents()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Status --</option>
                    <option value="planned">Direncana (Mendatang)</option>
                    <option value="completed">Terlaksana (Selesai)</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" @click="search=''; method=''; status=''; fetchEvents()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <div class="card-body p-0" id="eventListContainer">
        @include('admin.events.partials.event_list')
    </div>
</div>

<!-- Modal Tambah Kegiatan Baru -->
<div class="modal fade" id="modalAddEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-isnu text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-calendar-plus-fill me-1"></i> Rencanakan Kegiatan ISNU Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.events.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4" x-data="{
                    method: 'luring',
                    eventDate: '{{ date('Y-m-d') }}',
                    startTime: '08:00',
                    endTime: '12:00',
                    updatePresenceTimes() {
                        if (this.eventDate) {
                            this.$refs.presenceStart.value = `${this.eventDate}T${this.startTime}`;
                            this.$refs.presenceEnd.value = `${this.eventDate}T${this.endTime}`;
                        }
                    }
                }" x-init="updatePresenceTimes()">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Kegiatan / Acara <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: Majelis Ta'lim & Halqah Kebangsaan ISNU Kota Surabaya" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Singkat Kegiatan</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Tuliskan tujuan / tema / ringkasan agenda kegiatan..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode Pelaksanaan <span class="text-danger">*</span></label>
                            <select name="method" class="form-select" x-model="method" required>
                                <option value="luring">Luring (Tatap Muka / Offline)</option>
                                <option value="daring">Daring (Virtual / Online)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lokasi Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" placeholder="Contoh: Gedung PCNU Surabaya / Zoom Meeting" required>
                        </div>

                        <div class="col-12" x-show="method === 'daring'" x-cloak>
                            <label class="form-label fw-semibold">Link Meeting Daring (Zoom / Google Meet) <span class="text-danger">*</span></label>
                            <input type="url" name="meeting_link" class="form-control" placeholder="https://us02web.zoom.us/j/... atau https://meet.google.com/..." :required="method === 'daring'">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control" x-model="eventDate" @change="updatePresenceTimes()" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" x-model="startTime" @change="updatePresenceTimes()" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" x-model="endTime" @change="updatePresenceTimes()" required>
                        </div>

                        <div class="col-12"><hr class="my-2 text-secondary"></div>
                        <div class="col-12">
                            <h6 class="fw-bold text-success m-0"><i class="bi bi-clock-history me-1"></i> Pengaturan Masa Aktif Link Presensi Kehadiran</h6>
                            <p class="text-muted small mb-0">Tentukan rentang waktu link presensi dapat dibuka oleh anggota/pengurus. Di luar masa ini, form presensi otomatis tidak dapat diakses.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Mulai Presensi Aktif <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="presence_start_at" x-ref="presenceStart" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Berakhir Presensi <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="presence_end_at" x-ref="presenceEnd" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Kegiatan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="planned">Direncana (Mendatang)</option>
                                <option value="completed">Terlaksana (Selesai)</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4">Simpan Kegiatan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
