<div class="modal-header border-0 text-white p-4 position-relative"
     style="background: {{ $transaction->isIncome() ? 'linear-gradient(135deg, #198754 0%, #157347 100%)' : 'linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%)' }};">
    <div class="d-flex flex-column gap-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.8rem;">
                {{ $transaction->transaction_code }}
            </span>
            <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-2.5 py-1 small">
                {{ $transaction->type_label }} &bull; {{ $transaction->category }}
            </span>
        </div>
        <div class="fs-2 fw-bold mt-1">
            {{ $transaction->isIncome() ? '+' : '-' }} {{ $transaction->formatted_amount }}
        </div>
    </div>
    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-4 bg-light" style="max-height: 75vh; overflow-y: auto;">
    <!-- Card Detail Info -->
    <div class="card border-0 rounded-4 shadow-sm mb-3">
        <div class="card-body p-4">
            <div class="mb-3">
                <span class="text-muted small fw-semibold text-uppercase">Judul / Peruntukan</span>
                <h5 class="fw-bold text-dark mt-1 mb-0">{{ $transaction->title }}</h5>
            </div>

            @if($transaction->description)
                <div class="mb-3 p-3 rounded-3 bg-light border">
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Keterangan / Rincian</span>
                    <p class="mb-0 text-dark small" style="white-space: pre-line;">{{ $transaction->description }}</p>
                </div>
            @endif

            <div class="row g-3 pt-3 border-top">
                <div class="col-6 col-md-4">
                    <span class="text-muted small d-block">Tanggal Transaksi</span>
                    <span class="fw-semibold text-dark">{{ $transaction->formatted_date }}</span>
                </div>
                <div class="col-6 col-md-4">
                    <span class="text-muted small d-block">Metode Bayar</span>
                    <span class="fw-semibold text-dark">{{ $transaction->payment_method ?? 'Tunai' }}</span>
                </div>
                <div class="col-12 col-md-4">
                    <span class="text-muted small d-block">Tingkatan Organisasi</span>
                    <span class="fw-semibold text-dark">
                        {{ $transaction->level }}
                        @if($transaction->pac)
                            ({{ $transaction->pac->name }})
                        @endif
                    </span>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Petugas Pencatat</span>
                    <span class="fw-semibold text-dark">{{ $transaction->user?->name ?? 'User Sistem' }}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Waktu Input</span>
                    <span class="fw-semibold text-dark">{{ $transaction->created_at->format('d/m/Y H:i') }} WIB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Bukti Transaksi -->
    <div class="card border-0 rounded-4 shadow-sm">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark small mb-0">
                <i class="bi bi-paperclip me-1"></i> Bukti Lampiran Transaksi
            </span>
            @if($transaction->proof_path)
                <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Layar Penuh
                </a>
            @endif
        </div>
        <div class="card-body p-4 text-center">
            @if($transaction->proof_path)
                @php
                    $ext = strtolower(pathinfo($transaction->proof_path, PATHINFO_EXTENSION));
                @endphp
                @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                    <div class="rounded-3 border overflow-hidden p-2 bg-light d-inline-block shadow-sm" style="max-width: 100%;">
                        <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" title="Klik untuk membuka ukuran asli">
                            <img src="{{ asset('storage/' . $transaction->proof_path) }}" alt="Bukti Transaksi" class="img-fluid rounded-2" style="max-height: 380px; object-fit: contain;">
                        </a>
                    </div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-zoom-in me-1"></i>Klik gambar untuk melihat resolusi asli di tab baru.
                    </div>
                @else
                    <div class="py-4">
                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-1"></i>
                        <div class="fw-bold text-dark mt-2">Dokumen Bukti (PDF)</div>
                        <div class="text-muted small mb-3">Berkas bukti transaksi tersimpan dalam format PDF.</div>
                        <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-download me-1"></i> Unduh / Buka Dokumen PDF
                        </a>
                    </div>
                @endif
            @else
                <div class="py-4 text-muted">
                    <i class="bi bi-file-earmark-x fs-2 text-secondary opacity-50"></i>
                    <div class="fw-semibold mt-2">Tidak Ada Lampiran Bukti</div>
                    <p class="small text-muted mb-0">Transaksi ini dicatat tanpa menyertakan nota / bukti transfer fisik.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal-footer border-top px-4 py-3 bg-white d-flex justify-content-between flex-wrap gap-2">
    <button type="button" class="btn btn-light rounded-pill px-3 fw-semibold" data-bs-dismiss="modal">
        Tutup
    </button>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.finances.edit', $transaction->id) }}" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
            <i class="bi bi-pencil me-1"></i> Sunting
        </a>
        <a href="{{ route('admin.finances.show', $transaction->id) }}" class="btn btn-primary rounded-pill px-3 fw-semibold">
            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Lengkap
        </a>
    </div>
</div>
