@extends('layouts.admin')

@section('title', 'Export Laporan')
@section('header_title', 'Modul Pelaporan & Export Data Potensi')

@section('content')
<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="text-success fs-1 mb-3"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
            <h5 class="fw-bold text-dark">Laporan Seluruh Anggota</h5>
            <p class="text-secondary small">Export seluruh daftar anggota terdaftar beserta status keanggotaan dan kecamatan.</p>
            <a href="{{ route('admin.laporan.export', ['type' => 'anggota', 'format' => 'csv']) }}" class="btn btn-success w-100 rounded-pill fw-bold mt-auto">
                <i class="bi bi-download me-1"></i> Download Laporan CSV
            </a>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="text-warning fs-1 mb-3"><i class="bi bi-award-fill"></i></div>
            <h5 class="fw-bold text-dark">Laporan Pengurus ISNU</h5>
            <p class="text-secondary small">Export daftar pengurus aktif beserta jabatan, periode kepengurusan, dan kontak.</p>
            <a href="{{ route('admin.laporan.export', ['type' => 'pengurus', 'format' => 'csv']) }}" class="btn btn-warning text-dark w-100 rounded-pill fw-bold mt-auto">
                <i class="bi bi-download me-1"></i> Download Laporan Pengurus CSV
            </a>
        </div>
    </div>

    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="text-primary fs-1 mb-3"><i class="bi bi-person-lines-fill"></i></div>
            <h5 class="fw-bold text-dark">Laporan Anggota Terverifikasi</h5>
            <p class="text-secondary small">Export khusus anggota terverifikasi yang memiliki Kartu Anggota Digital aktif.</p>
            <a href="{{ route('admin.laporan.export', ['type' => 'terverifikasi', 'format' => 'csv']) }}" class="btn btn-primary w-100 rounded-pill fw-bold mt-auto">
                <i class="bi bi-download me-1"></i> Download CSV Terverifikasi
            </a>
        </div>
    </div>
</div>
@endsection
