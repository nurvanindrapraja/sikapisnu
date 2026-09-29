@extends('layouts.app')

@section('title', 'Tentang ISNU Surabaya')

@section('content')
<!-- Header Banner (Jarak dengan top menu didekatkan) -->
<div class="bg-success text-white py-3 py-md-4 border-bottom">
    <div class="container px-3 px-sm-4">
        <h2 class="fw-extrabold m-0" style="letter-spacing: -0.5px;">Tentang ISNU Kota Surabaya</h2>
        <p class="lead mb-0 text-white-80 fs-6 mt-1">Ikatan Sarjana Nahdlatul Ulama Kota Surabaya</p>
    </div>
</div>

<!-- Main Content (Jarak didekatkan) -->
<div class="container px-3 px-sm-4 py-3 py-md-4">
    <!-- Visi & Misi Section -->
    <div class="row g-4 align-items-center mb-3">
        <div class="col-lg-4 text-center">
            <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU Kota Surabaya" class="img-fluid" style="max-height: 190px; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.1));">
        </div>
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">Visi & Misi Organisasi</h3>
            <p class="text-secondary fs-6 mb-0" style="line-height: 1.6;">
                Ikatan Sarjana Nahdlatul Ulama (ISNU) adalah badan otonom Nahdlatul Ulama yang berfungsi membantu melaksanakan kebijakan NU pada kelompok sarjana, akademisi, dan profesional intelektual di Kota Surabaya.
            </p>
        </div>
    </div>

    <!-- Poin Fokus Pengembangan SIKAP ISNU (Didekatkan dengan Visi & Misi) -->
    <div class="mt-3 pt-3 border-top">
        <div class="text-center max-w-700 mx-auto mb-4">
            <h4 class="fw-bold text-dark m-0"><i class="bi bi-bullseye text-success me-2"></i> Fokus Pengembangan SIKAP ISNU</h4>
            <p class="fs-5 text-secondary fw-semibold mt-2 mb-0">3 Pilar Utama Sistem Informasi Keanggotaan dan Potensi ISNU Kota Surabaya</p>
        </div>

        <div class="row g-4">
            <!-- Card 1: Pendataan Terstruktur -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition-all">
                    <div class="bg-success-subtle text-success p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                        <i class="bi bi-database-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Pendataan Terstruktur</h5>
                    <p class="text-secondary small mb-0" style="line-height: 1.5;">
                        Pengumpulan dan pembaruan data dasar sarjana NU di Surabaya secara akurat, sistematis, dan terintegrasi.
                    </p>
                </div>
            </div>

            <!-- Card 2: Identitas Digital -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition-all">
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Identitas Digital</h5>
                    <p class="text-secondary small mb-0" style="line-height: 1.5;">
                        Penerbitan Kartu Anggota Digital ber-QR Code otentik yang terhubung ke server verifikasi publik resmi ISNU.
                    </p>
                </div>
            </div>

            <!-- Card 3: Database Potensi -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition-all">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-2 d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px;">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Database Potensi Kader</h5>
                    <p class="text-secondary small mb-0" style="line-height: 1.5;">
                        Pemetaan kepakaran (dosen, dokter, engineer, pengusaha, ASN, ulama sarjana) untuk konsolidasi dan penugasan organisasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

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
