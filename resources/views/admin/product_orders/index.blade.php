@extends('layouts.admin')

@section('title', 'Kelola Pesanan & Pengiriman Merchandise - Bendahara PCNU')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pesanan Merchandise</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Kelola Pemesanan & Pengiriman Merchandise</h4>
            <p class="text-muted small mb-0">Dikelola oleh Pengurus NU jabatan Bendahara / Wakil Bendahara (verifikasi pembayaran non-tunai & pengiriman).</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm">
                <i class="bi bi-person-badge-fill me-1"></i> Otoritas: Bendahara / Wakil Bendahara
            </span>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stat Summary Cards for Bendahara -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #003e21 0%, #006837 100%); color: white;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.72rem;">Total Kas Masuk</small>
                        <i class="bi bi-cash-stack text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-extrabold text-white mb-0">Rp {{ number_format($stats['total_income'], 0, ',', '.') }}</h4>
                    <small class="text-white-50" style="font-size: 0.72rem;">Dari pesanan lunas/selesai</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Perlu Verifikasi Kas</small>
                        <i class="bi bi-receipt text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">{{ $stats['payment_uploaded'] }}</h4>
                    <small class="text-warning fw-semibold" style="font-size: 0.72rem;">Bukti transfer baru diunggah</small>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Siap & Sedang Dikirim</small>
                        <i class="bi bi-truck text-primary fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">{{ $stats['paid'] + $stats['shipped'] }}</h4>
                    <small class="text-primary fw-semibold" style="font-size: 0.72rem;">{{ $stats['paid'] }} siap kirim, {{ $stats['shipped'] }} dikirim</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Pesanan Selesai</small>
                        <i class="bi bi-check2-circle text-success fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">{{ $stats['received'] }}</h4>
                    <small class="text-success fw-semibold" style="font-size: 0.72rem;">Diterima oleh pembeli</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.product_orders.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari no. pesanan, nama pembeli, no resi..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-4 col-lg-4">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">Semua Status Pesanan</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PENDING_PAYMENT }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PENDING_PAYMENT ? 'selected' : '' }}>Menunggu Pembayaran</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PAYMENT_UPLOADED ? 'selected' : '' }}>Bukti Bayar Diunggah (Perlu Verifikasi)</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_PAID }}" {{ request('status') === \App\Models\ProductOrder::STATUS_PAID ? 'selected' : '' }}>Lunas / Siap Kirim</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_SHIPPED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_SHIPPED ? 'selected' : '' }}>Sedang Dikirim</option>
                        <option value="{{ \App\Models\ProductOrder::STATUS_RECEIVED }}" {{ request('status') === \App\Models\ProductOrder::STATUS_RECEIVED ? 'selected' : '' }}>Selesai Diterima Pembeli</option>
                    </select>
                </div>

                <div class="col-md-2 col-lg-3 text-md-end">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Filter</button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.product_orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            @if($orders->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted opacity-50 mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">Belum Ada Pesanan Masuk</h5>
                    <p class="text-secondary small mb-0">Pesanan dari anggota akan muncul di sini untuk dikelola oleh Bendahara.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th>No. Pesanan & Tanggal</th>
                                <th>Pembeli / Anggota ISNU</th>
                                <th>Barang Dipesan</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-center">Bukti Transfer</th>
                                <th class="text-center">Status Pesanan</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $ord)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="fw-bold text-dark text-decoration-none hover-success d-block">
                                            {{ $ord->order_number }}
                                        </a>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            {{ $ord->created_at->translatedFormat('d M Y, H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block">{{ $ord->user->name ?? $ord->recipient_name }}</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-telephone me-1"></i> {{ $ord->recipient_phone }}
                                        </small>
                                    </td>
                                    <td>
                                        @foreach($ord->items as $it)
                                            <div class="small text-dark text-truncate" style="max-width: 200px;">
                                                • {{ $it->product_name }} ({{ $it->quantity }}x)
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        {{ $ord->formatted_grand_total }}
                                    </td>
                                    <td class="text-center">
                                        @if($ord->payment_proof_url)
                                            <a href="{{ $ord->payment_proof_url }}" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none">
                                                <i class="bi bi-image me-1"></i> Lihat Bukti
                                            </a>
                                        @else
                                            <span class="badge bg-light text-muted border">Belum Upload</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $ord->status_badge_class }} rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $ord->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="btn btn-sm btn-isnu-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                            Kelola <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
