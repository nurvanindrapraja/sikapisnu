@extends('layouts.admin')

@section('title', 'Detail Transaksi ' . $transaction->transaction_code)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.finances.index') }}" class="text-decoration-none">Keuangan & Kas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $transaction->transaction_code }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Rincian Transaksi Kas</h4>
            <p class="text-muted small mb-0">Detail pencatatan arus kas dan bukti pendukung transaksi.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.finances.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            <a href="{{ route('admin.finances.edit', $transaction->id) }}" class="btn btn-light border rounded-pill px-3">
                <i class="bi bi-pencil me-1"></i> Sunting
            </a>
            <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteTransactionModal">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Detail Receipt Card -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <!-- Header Card -->
                <div class="p-4 text-white text-center position-relative"
                     style="background: {{ $transaction->isIncome() ? 'linear-gradient(135deg, #198754 0%, #157347 100%)' : 'linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%)' }};">
                    <span class="badge bg-white text-dark rounded-pill px-3 py-1 font-monospace mb-2" style="font-size: 0.82rem;">
                        {{ $transaction->transaction_code }}
                    </span>
                    <h2 class="fw-bold mb-1">
                        {{ $transaction->isIncome() ? '+' : '-' }} {{ $transaction->formatted_amount }}
                    </h2>
                    <div class="small text-white-50">
                        {{ $transaction->type_label }} &bull; {{ $transaction->category }}
                    </div>
                </div>

                <!-- Body Details -->
                <div class="card-body p-4 p-md-5">
                    <div class="mb-4">
                        <span class="text-muted small fw-semibold text-uppercase">Judul / Peruntukan</span>
                        <h5 class="fw-bold text-dark mt-1 mb-0">{{ $transaction->title }}</h5>
                    </div>

                    @if($transaction->description)
                        <div class="mb-4 p-3 rounded-3 bg-light border">
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Keterangan / Rincian</span>
                            <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $transaction->description }}</p>
                        </div>
                    @endif

                    <div class="row g-3 pt-3 border-top">
                        <div class="col-6 col-sm-4">
                            <span class="text-muted small d-block">Tanggal Transaksi</span>
                            <span class="fw-semibold text-dark">{{ $transaction->formatted_date }}</span>
                        </div>
                        <div class="col-6 col-sm-4">
                            <span class="text-muted small d-block">Metode Bayar</span>
                            <span class="fw-semibold text-dark">{{ $transaction->payment_method ?? 'Tunai' }}</span>
                        </div>
                        <div class="col-12 col-sm-4">
                            <span class="text-muted small d-block">Tingkatan Organisasi</span>
                            <span class="fw-semibold text-dark">
                                {{ $transaction->level }}
                                @if($transaction->pac)
                                    ({{ $transaction->pac->name }})
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 pt-3 mt-2 border-top">
                        <div class="col-6">
                            <span class="text-muted small d-block">Petugas Pencatat</span>
                            <span class="fw-semibold text-dark">{{ $transaction->user?->name ?? 'User Sistem' }}</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Waktu Input Sistem</span>
                            <span class="fw-semibold text-dark">{{ $transaction->created_at->format('d/m/Y H:i') }} WIB</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proof / Attachment Preview -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-paperclip me-1"></i> Bukti Transaksi
                    </h6>
                    @if($transaction->proof_path)
                        <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Penuh
                        </a>
                    @endif
                </div>
                <div class="card-body p-4 text-center">
                    @if($transaction->proof_path)
                        @php
                            $ext = strtolower(pathinfo($transaction->proof_path, PATHINFO_EXTENSION));
                        @endphp
                        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                            <div class="rounded-3 border overflow-hidden p-1 bg-light">
                                <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $transaction->proof_path) }}" alt="Bukti Transaksi" class="img-fluid rounded-2" style="max-height: 420px; object-fit: contain;">
                                </a>
                            </div>
                            <div class="small text-muted mt-2">Klik gambar untuk memperbesar resolusi asli.</div>
                        @else
                            <div class="py-5">
                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                                <div class="fw-bold text-dark mt-2">Dokumen Bukti (PDF)</div>
                                <div class="text-muted small mb-3">Berkas bukti transaksi tersimpan dalam format PDF.</div>
                                <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                                    <i class="bi bi-download me-1"></i> Unduh / Pratinjau PDF
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 text-secondary opacity-50"></i>
                            <div class="fw-semibold mt-2">Tidak Ada Lampiran Bukti</div>
                            <p class="small text-muted mb-0">Transaksi ini dicatat tanpa menyertakan nota / struk transfer.</p>
                            <a href="{{ route('admin.finances.edit', $transaction->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-3">
                                <i class="bi bi-upload me-1"></i> Unggah Bukti Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Hapus Transaksi -->
    <div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="bi bi-trash3-fill fs-2"></i>
                        </div>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-2" id="deleteTransactionModalLabel">Hapus Transaksi Keuangan</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus catatan transaksi:
                    </p>
                    <div class="fw-bold text-dark fs-6 my-2 bg-light p-2 rounded-3 border">
                        {{ $transaction->title }}
                    </div>
                    <div class="fw-bold fs-5 text-danger mb-3">{{ $transaction->formatted_amount }}</div>
                    <p class="text-muted small mb-0">
                        Tindakan ini akan mempengaruhi rekapitulasi saldo kas dan tercatat pada audit log.
                    </p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <form action="{{ route('admin.finances.destroy', $transaction->id) }}" method="POST" class="flex-fill m-0"
                          onsubmit="document.getElementById('btnSubmitDelete').disabled = true; document.getElementById('btnSubmitDelete').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\' role=\'status\' aria-hidden=\'true\'></span>Menghapus...';">
                        @csrf
                        @method('DELETE')
                        <button type="submit" id="btnSubmitDelete" class="btn btn-danger text-white rounded-pill w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-trash me-1"></i> Ya, Hapus Transaksi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
