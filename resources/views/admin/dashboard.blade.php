@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('header_title', 'Dashboard')

@section('content')
<!-- Metric Cards (Clickable to Verifikasi Keanggotaan) -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'all']) }}" class="text-decoration-none">
            <div class="card stat-card bg-white p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-muted small d-block font-semibold">TOTAL DAFTAR</span>
                <h3 class="fw-extrabold text-dark m-0 mt-1">{{ number_format($stats['total']) }}</h3>
                <small class="text-success small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'terverifikasi']) }}" class="text-decoration-none">
            <div class="card stat-card bg-success text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-white-50 small d-block font-semibold">ANGGOTA AKTIF</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['terverifikasi']) }}</h3>
                <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'pengurus']) }}" class="text-decoration-none">
            <div class="card stat-card bg-warning text-dark p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-dark-50 small d-block font-semibold">PENGURUS</span>
                <h3 class="fw-extrabold text-dark m-0 mt-1">{{ number_format($stats['pengurus']) }}</h3>
                <small class="text-dark-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'menunggu_verifikasi']) }}" class="text-decoration-none">
            <div class="card stat-card bg-danger text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-white-50 small d-block font-semibold">PENDING VERIFIKASI</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['pending']) }}</h3>
                <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'perbaikan']) }}" class="text-decoration-none">
            <div class="card stat-card bg-info text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-white-50 small d-block font-semibold">PERLU PERBAIKAN</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['perbaikan']) }}</h3>
                <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <a href="{{ route('admin.verifikasi.index', ['status' => 'ditolak']) }}" class="text-decoration-none">
            <div class="card stat-card bg-secondary text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale cursor-pointer">
                <span class="text-white-50 small d-block font-semibold">DITOLAK</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['ditolak']) }}</h3>
                <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
            </div>
        </a>
    </div>
</div>

<!-- Pending Registration Queue Section -->
@if($pendingMembers->count() > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-warning-subtle border-0 p-3 d-flex justify-content-between align-items-center">
            <strong class="text-dark"><i class="bi bi-clock-history me-1"></i> Antrean Verifikasi Pendaftar Baru ({{ $stats['pending'] }})</strong>
            <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-sm btn-dark rounded-pill px-3">Lihat Semua Antrean</a>
        </div>
        
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pendaftar</th>
                        <th>NIK</th>
                        <th>Pekerjaan</th>
                        <th>Kecamatan</th>
                        <th>Tanggal Daftar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingMembers as $m)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover" style="width: 38px; height: 38px;">
                                    <div>
                                        <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                                        <small class="text-muted">{{ $m->email }} | {{ $m->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><code>{{ $m->nik }}</code></td>
                            <td>{{ $m->occupation }}</td>
                            <td>{{ $m->kecamatan }}</td>
                            <td class="text-secondary small">{{ $m->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.verifikasi.show', $m->id) }}" class="btn btn-sm btn-success rounded-pill px-3">
                                    <i class="bi bi-search me-1"></i> Review & Verifikasi
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="d-block d-md-none p-3 space-y-3">
            @foreach($pendingMembers as $m)
                <div class="card border border-warning-subtle shadow-sm rounded-4 p-3 bg-white mb-3">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-warning" style="width: 44px; height: 44px;">
                        <div>
                            <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                            <small class="text-muted">{{ $m->email }}</small>
                        </div>
                    </div>
                    <div class="bg-light p-2.5 rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">NIK:</span>
                            <code>{{ $m->nik }}</code>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Pekerjaan:</span>
                            <span class="fw-semibold text-dark">{{ $m->occupation }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Kecamatan:</span>
                            <span class="fw-semibold text-success">{{ $m->kecamatan }}</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.verifikasi.show', $m->id) }}" class="btn btn-sm btn-success w-100 rounded-pill fw-bold">
                        <i class="bi bi-search me-1"></i> Review & Verifikasi
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Analytics Breakdown Grids -->
<div class="row g-4">
    <!-- Breakdown Pekerjaan / Profesi -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase-fill text-success me-2"></i> Persebaran Profesi & Pekerjaan Kader</h6>
            <div class="list-group list-group-flush">
                @foreach($occupationStats as $occ)
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                        <span><i class="bi bi-dot text-success me-1"></i> {{ $occ->occupation }}</span>
                        <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill">{{ number_format($occ->total) }} Kader</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Breakdown Pendidikan & Kaderisasi NU -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-success me-2"></i> Tingkat Pendidikan & Kaderisasi NU</h6>
            
            <label class="fw-semibold text-muted small mb-2">Jenjang Pendidikan Terdaftar:</label>
            <div class="d-flex flex-wrap gap-2 mb-4">
                @foreach($educationStats as $edu)
                    <span class="badge bg-light text-dark border p-2 px-3 rounded-3 fs-6">
                        <strong>{{ $edu->level }}:</strong> {{ $edu->total }} Orang
                    </span>
                @endforeach
            </div>

            <label class="fw-semibold text-muted small mb-2">Pendidikan Kaderisasi NU:</label>
            <div class="d-flex flex-wrap gap-2">
                @foreach($nuTrainingStats as $nu)
                    <span class="badge bg-success-subtle text-success border border-success-subtle p-2 px-3 rounded-3 fs-6">
                        <strong>{{ $nu->training_type }}:</strong> {{ $nu->total }} Kader
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
