@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('header_title', 'Dashboard')

@section('content')
@php
    $canAccessVerifikasi = !auth()->user()->isRestrictedOfficer()
        && !auth()->user()->isKetuaOrSekretarisPac()
        && !auth()->user()->isBendaharaOfficer();
@endphp

<!-- Metric Cards Keanggotaan -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'all']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-white p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-muted small d-block font-semibold">TOTAL DAFTAR</span>
                <h3 class="fw-extrabold text-dark m-0 mt-1">{{ number_format($stats['total']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-success small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'terverifikasi']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-success text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-white-50 small d-block font-semibold">ANGGOTA AKTIF</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['terverifikasi']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'pengurus']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-warning text-dark p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-dark-50 small d-block font-semibold">PENGURUS</span>
                <h3 class="fw-extrabold text-dark m-0 mt-1">{{ number_format($stats['pengurus']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-dark-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'menunggu_verifikasi']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-danger text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-white-50 small d-block font-semibold">PENDING VERIFIKASI</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['pending']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'perbaikan']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-info text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-white-50 small d-block font-semibold">PERLU PERBAIKAN</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['perbaikan']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        @if($canAccessVerifikasi)
            <a href="{{ route('admin.verifikasi.index', ['status' => 'ditolak']) }}" class="text-decoration-none">
        @endif
            <div class="card stat-card bg-secondary text-white p-3 h-100 border-0 shadow-sm transition-all hover-scale {{ $canAccessVerifikasi ? 'cursor-pointer' : '' }}">
                <span class="text-white-50 small d-block font-semibold">DITOLAK</span>
                <h3 class="fw-extrabold text-white m-0 mt-1">{{ number_format($stats['ditolak']) }}</h3>
                @if($canAccessVerifikasi)
                    <small class="text-white-50 small mt-2 d-block"><i class="bi bi-arrow-right-circle me-1"></i> Lihat Data</small>
                @endif
            </div>
        @if($canAccessVerifikasi)
            </a>
        @endif
    </div>
</div>

<!-- ====================================================================== -->
<!-- RINGKASAN REPORTING EKSEKUTIF: KEGIATAN, KEUANGAN, PERSURATAN, MERCH   -->
<!-- ====================================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 class="fw-bold text-dark m-0">
        <i class="bi bi-bar-chart-line-fill text-success me-2"></i>Reporting Organisasi (Kegiatan, Keuangan, Persuratan &amp; Merchandise)
    </h5>
    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5">
        <i class="bi bi-calendar-range me-1"></i> Tren 6 Bulan Terakhir
    </span>
</div>

<div class="row g-3 mb-4">
    <!-- KPI Kegiatan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Kegiatan &amp; Presensi</span>
                        <h3 class="fw-extrabold text-dark mb-0 mt-1">{{ number_format($eventReport['total_events']) }} <small class="fs-6 fw-normal text-muted">Kegiatan</small></h3>
                    </div>
                    <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-calendar-event-fill fs-5"></i>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 small text-muted mb-2">
                    <span><i class="bi bi-people-fill text-success me-1"></i><strong>{{ number_format($eventReport['total_presences']) }}</strong> presensi</span>
                    <span>&bull;</span>
                    <span><strong>{{ number_format($eventReport['upcoming_events']) }}</strong> terjadwal</span>
                </div>
                <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Luring {{ $eventReport['luring'] }} &bull; Daring {{ $eventReport['daring'] }}</span>
                    <a href="{{ route('admin.events.index') }}" class="text-primary fw-semibold text-decoration-none">Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Keuangan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="min-w-0">
                        <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Saldo Kas Organisasi</span>
                        <h4 class="fw-extrabold {{ $financeReport['balance'] >= 0 ? 'text-success' : 'text-danger' }} mb-0 mt-1 text-truncate" title="Rp {{ number_format($financeReport['balance'], 0, ',', '.') }}">
                            Rp {{ number_format($financeReport['balance'], 0, ',', '.') }}
                        </h4>
                    </div>
                    <div class="rounded-3 bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="bi bi-cash-coin fs-5"></i>
                    </div>
                </div>
                <div class="small mb-2">
                    <div class="text-success text-truncate"><i class="bi bi-arrow-down-left-circle me-1"></i>Masuk: <strong>Rp {{ number_format($financeReport['total_in'], 0, ',', '.') }}</strong></div>
                    <div class="text-danger text-truncate"><i class="bi bi-arrow-up-right-circle me-1"></i>Keluar: <strong>Rp {{ number_format($financeReport['total_out'], 0, ',', '.') }}</strong></div>
                </div>
                <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                    <span class="text-muted">{{ number_format($financeReport['total_transactions']) }} transaksi</span>
                    @if(auth()->user()->canManageFinances())
                        <a href="{{ route('admin.finances.index') }}" class="text-success fw-semibold text-decoration-none">Kelola <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Persuratan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-info bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Persuratan &amp; E-TTD</span>
                        <h3 class="fw-extrabold text-dark mb-0 mt-1">{{ number_format($letterReport['total']) }} <small class="fs-6 fw-normal text-muted">Surat</small></h3>
                    </div>
                    <div class="rounded-3 bg-info-subtle text-info p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-file-earmark-text-fill fs-5"></i>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 small text-muted mb-2">
                    <span class="text-success"><strong>{{ number_format($letterReport['selesai']) }}</strong> selesai</span>
                    <span>&bull;</span>
                    <span class="text-warning-emphasis"><strong>{{ number_format($letterReport['menunggu_sekretaris'] + $letterReport['menunggu_ketua']) }}</strong> antre TTD</span>
                </div>
                <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Draft {{ $letterReport['draft'] }} &bull; Revisi {{ $letterReport['ditolak'] }}</span>
                    @if(auth()->user()->canAccessLetterSubmission())
                        <a href="{{ route('admin.letters.index') }}" class="text-info fw-semibold text-decoration-none">Detail <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Merchandise -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="min-w-0">
                        <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Omzet Merchandise</span>
                        <h4 class="fw-extrabold text-dark mb-0 mt-1 text-truncate" title="Rp {{ number_format($merchandiseReport['revenue'], 0, ',', '.') }}">
                            Rp {{ number_format($merchandiseReport['revenue'], 0, ',', '.') }}
                        </h4>
                    </div>
                    <div class="rounded-3 bg-warning-subtle text-warning-emphasis p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="bi bi-bag-check-fill fs-5"></i>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 small text-muted mb-2">
                    <span><strong>{{ number_format($merchandiseReport['total_orders']) }}</strong> pesanan</span>
                    <span>&bull;</span>
                    <span><strong>{{ number_format($merchandiseReport['active_products']) }}</strong>/{{ number_format($merchandiseReport['total_products']) }} produk</span>
                </div>
                <div class="pt-2 border-top d-flex justify-content-between align-items-center small">
                    <span class="text-warning-emphasis fw-semibold">{{ $merchandiseReport['payment_uploaded'] }} perlu verifikasi</span>
                    @if(auth()->user()->canManageProductOrders())
                        <a href="{{ route('admin.product_orders.index') }}" class="text-warning-emphasis fw-semibold text-decoration-none">Kelola <i class="bi bi-arrow-right"></i></a>
                    @elseif(auth()->user()->canManageProducts())
                        <a href="{{ route('admin.products.index') }}" class="text-warning-emphasis fw-semibold text-decoration-none">Produk <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- GRAFIK REPORTING (2x2 GRID INTERAKTIF CHART.JS)                        -->
<!-- ====================================================================== -->
<div class="row g-4 mb-4">
    <!-- Grafik 1: Kegiatan & Presensi -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event-fill text-primary me-2"></i>Grafik Kegiatan &amp; Presensi (6 Bulan)</h6>
                    <small class="text-muted">Perbandingan jumlah kegiatan dan total kehadiran kader</small>
                </div>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1">Anggaran: Rp {{ number_format($eventReport['total_budget'], 0, ',', '.') }}</span>
            </div>
            <div style="position: relative; height: 250px;">
                <canvas id="chartEvents"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik 2: Keuangan (Kas Masuk vs Keluar) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-cash-stack text-success me-2"></i>Grafik Arus Kas Masuk vs Keluar (6 Bulan)</h6>
                    <small class="text-muted">Rekapitulasi pemasukan dan pengeluaran organisasi per bulan</small>
                </div>
                <span class="badge {{ $financeReport['balance'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-2.5 py-1">
                    Saldo: Rp {{ number_format($financeReport['balance'], 0, ',', '.') }}
                </span>
            </div>
            <div style="position: relative; height: 250px;">
                <canvas id="chartFinances"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik 3: Persuratan & Status E-TTD -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-check-fill text-info me-2"></i>Grafik Persuratan &amp; Status E-TTD</h6>
                    <small class="text-muted">Tren surat 6 bulan &amp; proporsi status penandatanganan</small>
                </div>
                <div class="d-flex flex-wrap gap-1 small">
                    <span class="badge bg-success-subtle text-success">Dinas: {{ $letterReport['by_type']['biasa'] }}</span>
                    <span class="badge bg-primary-subtle text-primary">Mandat: {{ $letterReport['by_type']['mandat'] }}</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Rekom: {{ $letterReport['by_type']['rekomendasi'] }}</span>
                </div>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div style="position: relative; height: 230px;">
                        <canvas id="chartLettersTrend"></canvas>
                    </div>
                </div>
                <div class="col-md-5">
                    <div style="position: relative; height: 230px;">
                        <canvas id="chartLettersStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik 4: Penjualan & Pesanan Merchandise -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shop text-warning me-2"></i>Grafik Penjualan &amp; Pesanan Merchandise</h6>
                    <small class="text-muted">Omzet penjualan (Rp) &amp; jumlah pesanan masuk 6 bulan terakhir</small>
                </div>
                @if($merchandiseReport['low_stock_products'] > 0)
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $merchandiseReport['low_stock_products'] }} stok menipis
                    </span>
                @else
                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Stok Aman</span>
                @endif
            </div>
            <div style="position: relative; height: 230px;">
                <canvas id="chartMerchandise"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- DATA RINGKASAN TERBARU (DESKTOP TABEL + MOBILE CARD VIEW)              -->
<!-- ====================================================================== -->
<div class="row g-4 mb-4">
    <!-- Data Kegiatan Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar2-check text-primary me-2"></i>Kegiatan Terbaru</h6>
                <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @if($eventReport['recent_events']->isEmpty())
                    <div class="p-4 text-center text-muted small">Belum ada data kegiatan tercatat.</div>
                @else
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Kegiatan</th>
                                    <th>Tanggal</th>
                                    <th class="text-center">Metode</th>
                                    <th class="text-end pe-3">Presensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($eventReport['recent_events'] as $ev)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('admin.events.show', $ev->id) }}" class="fw-semibold text-dark text-decoration-none">
                                                {{ \Illuminate\Support\Str::limit($ev->title, 38) }}
                                            </a>
                                            @if($ev->section)
                                                <div class="text-muted" style="font-size: 0.72rem;">{{ $ev->section->name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap text-muted">{{ $ev->event_date ? $ev->event_date->translatedFormat('d M Y') : '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $ev->method === 'luring' ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }} rounded-pill text-capitalize">
                                                {{ $ev->method ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-success">{{ number_format($ev->presences_count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none p-3 d-flex flex-column gap-2">
                        @foreach($eventReport['recent_events'] as $ev)
                            <div class="border rounded-3 p-2.5 bg-light bg-opacity-50 small">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <a href="{{ route('admin.events.show', $ev->id) }}" class="fw-bold text-dark text-decoration-none lh-sm">{{ $ev->title }}</a>
                                    <span class="badge bg-success-subtle text-success rounded-pill flex-shrink-0">{{ number_format($ev->presences_count) }} hadir</span>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                    {{ $ev->event_date ? $ev->event_date->translatedFormat('d M Y') : '-' }} &bull; {{ ucfirst($ev->method ?? '-') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Transaksi Keuangan Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-wallet2 text-success me-2"></i>Transaksi Kas Terbaru</h6>
                @if(auth()->user()->canManageFinances())
                    <a href="{{ route('admin.finances.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3">Lihat Semua</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($financeReport['recent_transactions']->isEmpty())
                    <div class="p-4 text-center text-muted small">Belum ada transaksi kas tercatat.</div>
                @else
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Transaksi</th>
                                    <th>Tanggal</th>
                                    <th class="text-end pe-3">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($financeReport['recent_transactions'] as $trx)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($trx->title, 40) }}</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $trx->category }}</div>
                                        </td>
                                        <td class="text-nowrap text-muted">{{ $trx->formatted_date }}</td>
                                        <td class="text-end pe-3 fw-bold text-nowrap {{ $trx->isIncome() ? 'text-success' : 'text-danger' }}">
                                            {{ $trx->isIncome() ? '+' : '-' }}{{ $trx->formatted_amount }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none p-3 d-flex flex-column gap-2">
                        @foreach($financeReport['recent_transactions'] as $trx)
                            <div class="border rounded-3 p-2.5 bg-light bg-opacity-50 small">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="fw-bold text-dark lh-sm">{{ $trx->title }}</span>
                                    <span class="fw-bold text-nowrap {{ $trx->isIncome() ? 'text-success' : 'text-danger' }}">
                                        {{ $trx->isIncome() ? '+' : '-' }}{{ $trx->formatted_amount }}
                                    </span>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                    {{ $trx->formatted_date }} &bull; {{ $trx->category }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Persuratan Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-envelope-paper-fill text-info me-2"></i>Persuratan Terbaru</h6>
                @if(auth()->user()->canAccessLetterSubmission())
                    <a href="{{ route('admin.letters.index') }}" class="btn btn-sm btn-outline-info rounded-pill px-3">Lihat Semua</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($letterReport['recent_letters']->isEmpty())
                    <div class="p-4 text-center text-muted small">Belum ada data surat tercatat.</div>
                @else
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Nomor &amp; Perihal</th>
                                    <th>Tanggal</th>
                                    <th class="text-end pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($letterReport['recent_letters'] as $ltr)
                                    <tr>
                                        <td class="ps-3">
                                            @if(auth()->user()->canAccessLetterSubmission())
                                                <a href="{{ route('admin.letters.show', $ltr->id) }}" class="fw-semibold text-dark text-decoration-none d-block">{{ $ltr->formatted_letter_number }}</a>
                                            @else
                                                <div class="fw-semibold text-dark">{{ $ltr->formatted_letter_number }}</div>
                                            @endif
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ \Illuminate\Support\Str::limit($ltr->perihal, 42) }}</div>
                                        </td>
                                        <td class="text-nowrap text-muted">{{ $ltr->formatted_date_letter ?: '-' }}</td>
                                        <td class="text-end pe-3">
                                            <span class="{{ $ltr->status_badge_class }} rounded-pill">{{ $ltr->status_label }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none p-3 d-flex flex-column gap-2">
                        @foreach($letterReport['recent_letters'] as $ltr)
                            <div class="border rounded-3 p-2.5 bg-light bg-opacity-50 small">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <strong class="text-dark lh-sm">{{ $ltr->formatted_letter_number }}</strong>
                                    <span class="{{ $ltr->status_badge_class }} rounded-pill flex-shrink-0" style="font-size: 0.68rem;">{{ $ltr->status_label }}</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ \Illuminate\Support\Str::limit($ltr->perihal, 60) }} &bull; {{ $ltr->formatted_date_letter ?: '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Merchandise Terlaris & Status Pesanan -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Merchandise Terlaris &amp; Ringkasan Pesanan</h6>
                @if(auth()->user()->canManageProductOrders())
                    <a href="{{ route('admin.product_orders.index') }}" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3">Pesanan</a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-2 small">
                    <span class="badge bg-secondary rounded-pill">Menunggu Bayar: {{ $merchandiseReport['pending_payment'] }}</span>
                    <span class="badge bg-warning text-dark rounded-pill">Verifikasi: {{ $merchandiseReport['payment_uploaded'] }}</span>
                    <span class="badge bg-info text-dark rounded-pill">Lunas: {{ $merchandiseReport['paid'] }}</span>
                    <span class="badge bg-primary rounded-pill">Dikirim: {{ $merchandiseReport['shipped'] }}</span>
                    <span class="badge bg-success rounded-pill">Selesai: {{ $merchandiseReport['received'] }}</span>
                </div>

                @if($merchandiseReport['top_products']->isEmpty())
                    <div class="p-4 text-center text-muted small">Belum ada data penjualan merchandise.</div>
                @else
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Nama Produk</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-end pe-3">Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($merchandiseReport['top_products'] as $tp)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">{{ $tp->product_name }}</td>
                                        <td class="text-center"><span class="badge bg-success-subtle text-success rounded-pill">{{ number_format((int) $tp->total_qty) }} pcs</span></td>
                                        <td class="text-end pe-3 fw-bold text-success">Rp {{ number_format((float) $tp->total_sales, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-md-none p-3 d-flex flex-column gap-2">
                        @foreach($merchandiseReport['top_products'] as $tp)
                            <div class="border rounded-3 p-2.5 bg-light bg-opacity-50 small d-flex justify-content-between align-items-center gap-2">
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate">{{ $tp->product_name }}</div>
                                    <span class="badge bg-success-subtle text-success rounded-pill">{{ number_format((int) $tp->total_qty) }} pcs terjual</span>
                                </div>
                                <strong class="text-success text-nowrap">Rp {{ number_format((float) $tp->total_sales, 0, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Pending Registration Queue Section -->
@if($canAccessVerifikasi && $pendingMembers->count() > 0)
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
                                    <i class="bi bi-search me-1"></i> Review &amp; Verifikasi
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
                        <i class="bi bi-search me-1"></i> Review &amp; Verifikasi
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Analytics Breakdown Grids Keanggotaan -->
<div class="row g-4">
    <!-- Breakdown Pekerjaan / Profesi -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase-fill text-success me-2"></i> Persebaran Profesi &amp; Pekerjaan Kader</h6>
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
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-success me-2"></i> Tingkat Pendidikan &amp; Kaderisasi NU</h6>
            
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const formatRupiah = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');
    const formatRupiahCompact = (val) => {
        const num = Number(val || 0);
        if (Math.abs(num) >= 1000000) return 'Rp ' + (num / 1000000).toFixed(1) + ' jt';
        if (Math.abs(num) >= 1000) return 'Rp ' + (num / 1000).toFixed(0) + ' rb';
        return 'Rp ' + num;
    };

    const eventChartData = @json($eventReport['chart']);
    const financeChartData = @json($financeReport['chart']);
    const letterChartData = @json($letterReport['chart']);
    const merchChartData = @json($merchandiseReport['chart']);

    // 1. Chart Kegiatan & Presensi (Combo Bar + Line)
    const ctxEvents = document.getElementById('chartEvents');
    if (ctxEvents) {
        new Chart(ctxEvents, {
            data: {
                labels: eventChartData.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Jumlah Kegiatan',
                        data: eventChartData.events,
                        backgroundColor: 'rgba(13, 110, 253, 0.75)',
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Total Presensi',
                        data: eventChartData.presences,
                        borderColor: '#006837',
                        backgroundColor: 'rgba(0, 104, 55, 0.15)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Kegiatan' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 }, title: { display: true, text: 'Presensi' } }
                }
            }
        });
    }

    // 2. Chart Keuangan (Grouped Bar: Masuk vs Keluar)
    const ctxFinances = document.getElementById('chartFinances');
    if (ctxFinances) {
        new Chart(ctxFinances, {
            type: 'bar',
            data: {
                labels: financeChartData.labels,
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: financeChartData.income,
                        backgroundColor: 'rgba(0, 104, 55, 0.85)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Kas Keluar',
                        data: financeChartData.expense,
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${formatRupiah(ctx.parsed.y)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (val) => formatRupiahCompact(val) }
                    }
                }
            }
        });
    }

    // 3a. Chart Persuratan Tren Bulanan
    const ctxLettersTrend = document.getElementById('chartLettersTrend');
    if (ctxLettersTrend) {
        new Chart(ctxLettersTrend, {
            type: 'bar',
            data: {
                labels: letterChartData.labels,
                datasets: [{
                    label: 'Jumlah Surat',
                    data: letterChartData.monthly_counts,
                    backgroundColor: 'rgba(13, 202, 240, 0.8)',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 3b. Chart Persuratan Status Doughnut
    const ctxLettersStatus = document.getElementById('chartLettersStatus');
    if (ctxLettersStatus) {
        const totalLetterStatus = letterChartData.status_counts.reduce((a, b) => a + b, 0);
        new Chart(ctxLettersStatus, {
            type: 'doughnut',
            data: {
                labels: totalLetterStatus > 0 ? letterChartData.status_labels : ['Belum Ada Data'],
                datasets: [{
                    data: totalLetterStatus > 0 ? letterChartData.status_counts : [1],
                    backgroundColor: totalLetterStatus > 0
                        ? ['#6c757d', '#ffc107', '#0dcaf0', '#198754', '#dc3545']
                        : ['#e2e8f0'],
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    // 4. Chart Merchandise (Omzet Bar + Jumlah Pesanan Line)
    const ctxMerch = document.getElementById('chartMerchandise');
    if (ctxMerch) {
        new Chart(ctxMerch, {
            data: {
                labels: merchChartData.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Omzet (Rp)',
                        data: merchChartData.revenue,
                        backgroundColor: 'rgba(212, 175, 55, 0.85)',
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Jumlah Pesanan',
                        data: merchChartData.orders,
                        borderColor: '#006837',
                        backgroundColor: 'rgba(0, 104, 55, 0.12)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.dataset.yAxisID === 'y'
                                ? `${ctx.dataset.label}: ${formatRupiah(ctx.parsed.y)}`
                                : `${ctx.dataset.label}: ${ctx.parsed.y} pesanan`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (val) => formatRupiahCompact(val) } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 }, title: { display: true, text: 'Pesanan' } }
                }
            }
        });
    }
});
</script>
@endsection
