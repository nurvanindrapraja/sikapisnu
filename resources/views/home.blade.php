@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container px-3 px-sm-4 position-relative z-1">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3 text-uppercase"
                        style="font-size: 0.75rem; letter-spacing: 1px;">
                        <i class="bi bi-star-fill me-1"></i> Database Potensi Kader NU
                    </span>
                    <h1 class="display-4 fw-extrabold text-white mb-3" style="letter-spacing: -1px; line-height: 1.15;">
                        Sistem Informasi Keanggotaan & Potensi <span style="color: var(--isnu-gold-light);">ISNU
                            Surabaya</span>
                    </h1>
                    <p class="lead text-white-80 mb-4 fs-5" style="color: rgba(255,255,255,0.9);">
                        Wadah konsolidasi sarjana, akademisi, dan profesional Nahdlatul Ulama di Kota Surabaya. Dilengkapi
                        Kartu Anggota Digital ber-QR Code untuk verifikasi otentik.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('register') }}"
                            class="btn btn-warning btn-lg fw-bold rounded-pill px-4 shadow-lg text-dark">
                            <i class="bi bi-person-plus-fill me-2"></i> Daftar Sekarang
                        </a>
                        <a href="{{ route('daftar.anggota') }}"
                            class="btn btn-outline-light btn-lg fw-semibold rounded-pill px-4">
                            <i class="bi bi-search me-2"></i> Cari Potensi Kader
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center">
                    <!-- Preview Digital Card Showcase -->
                    @if($recentMembers->count() > 0)
                        @php $sampleMember = $recentMembers->first(); @endphp
                        <div class="card-showcase p-2">
                            <x-digital_card :member="$sampleMember" :card="$sampleMember->activeCard" />
                        </div>
                    @else
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="ISNU Logo" class="img-fluid p-4"
                            style="max-height: 320px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));">
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Metrics Section (Card View Display) -->
    <section class="py-5 bg-light border-bottom">
        <div class="container px-3 px-sm-4">
            <div class="row g-4">
                <!-- Card 1: Anggota Terverifikasi -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white hover-shadow transition-all">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h2 class="fw-extrabold text-dark m-0" style="letter-spacing: -0.5px;">
                                    {{ number_format($totalMembers) }}
                                </h2>
                                <span class="text-secondary fw-semibold small">Anggota Terverifikasi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Pengurus Aktif -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white hover-shadow transition-all">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-award-fill"></i>
                            </div>
                            <div>
                                <h2 class="fw-extrabold text-dark m-0" style="letter-spacing: -0.5px;">
                                    {{ number_format($totalPengurus) }}
                                </h2>
                                <span class="text-secondary fw-semibold small">Pengurus Aktif</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: MWC NU Kecamatan -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white hover-shadow transition-all">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 64px; height: 64px;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <h2 class="fw-extrabold text-dark m-0" style="letter-spacing: -0.5px;">
                                    {{ number_format($totalMwc) }}
                                </h2>
                                <span class="text-secondary fw-semibold small">MWC NU Kecamatan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container px-3 px-sm-4 py-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <h2 class="fw-bold text-dark">Layanan Utama SIKAP ISNU</h2>
                <p class="text-secondary fs-5">Fasilitas pendataan dan integrasi potensi kader terpadu</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="text-success fs-1 mb-3">
                            <i class="bi bi-card-heading"></i>
                        </div>
                        <h5 class="fw-bold">Kartu Anggota Digital</h5>
                        <p class="text-secondary small mb-0">
                            Kartu identitas resmi dengan QR Code dinamis yang terhubung langsung dengan server verifikasi
                            publik ISNU Kota Surabaya.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="text-warning fs-1 mb-3">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <h5 class="fw-bold">Pemetaan Potensi Kader</h5>
                        <p class="text-secondary small mb-0">
                            Pendataan komprehensif meliputi latar belakang pendidikan (S1-S3), keahlian profesional, riwayat
                            kerja, dan kaderisasi NU.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="text-primary fs-1 mb-3">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="fw-bold">Verifikasi Berjenjang</h5>
                        <p class="text-secondary small mb-0">
                            Proses validasi bertahap oleh Pengurus Cabang untuk menjamin keshahihan data anggota dan
                            pengurus di tingkat PAC.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .hover-shadow {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-shadow:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08) !important;
        }
    </style>
@endsection