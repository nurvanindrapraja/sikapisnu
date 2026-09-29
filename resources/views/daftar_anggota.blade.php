@extends('layouts.app')

@section('title', 'Katalog Anggota & Potensi')

@section('content')
    <div class="bg-success text-white py-4">
        <div class="container">
            <h2 class="fw-bold m-0"><i class="bi bi-people-fill me-2"></i> Katalog Anggota ISNU Surabaya</h2>
            <p class="mb-0 text-white-50">Daftar sarjana dan kader ISNU Kota Surabaya yang telah terverifikasi</p>
        </div>
    </div>

    <div class="container py-4">
        <!-- Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <form action="{{ route('daftar.anggota') }}" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small">Cari Nama / Profesi / No. Anggota</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0"
                                placeholder="Contoh: Dokter, Dosen, Jurnalis, Aktivis NGO / LSM, IT, Ahmad..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">Kecamatan / MWC NU</label>
                        <select name="mwc_id" class="form-select">
                            <option value="">-- Semua MWC Kecamatan --</option>
                            @foreach($mwcs as $mwc)
                                <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                                    {{ $mwc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-isnu-primary w-100 fw-bold py-2">
                            <i class="bi bi-funnel-fill me-1"></i> Filter Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Member Grid -->
        <div class="row g-4">
            @forelse($members as $m)
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="{{ $m->photo_url }}" alt="{{ $m->full_name }}"
                                    class="rounded-circle object-fit-cover border border-2 border-success"
                                    style="width: 58px; height: 58px;">
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold text-dark m-0 text-truncate" title="{{ $m->full_name }}">
                                        {{ $m->full_name }}</h6>
                                    <span
                                        class="badge {{ $m->membership_status === 'pengurus' ? 'bg-warning text-dark' : 'bg-success' }} small mt-1">
                                        {{ strtoupper($m->membership_status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="small text-secondary space-y-1">
                                <div><i class="bi bi-card-text me-1 text-success"></i> {{ $m->member_number ?? '-' }}</div>
                                <div><i class="bi bi-briefcase me-1 text-success"></i> {{ $m->occupation }}</div>
                                @if($m->mwc)
                                    <div><i class="bi bi-geo-alt me-1 text-success"></i> {{ $m->mwc->name }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search text-muted display-1"></i>
                    <h5 class="fw-bold text-muted mt-3">Tidak ada data anggota yang cocok.</h5>
                </div>
            @endforelse
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $members->links() }}
        </div>
    </div>
@endsection