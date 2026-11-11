<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr class="text-secondary small fw-semibold">
                <th style="width: 140px;">KODE & TGL</th>
                <th style="width: 120px;">TIPE</th>
                <th>JUDUL & KATEGORI</th>
                <th style="width: 160px;">METODE</th>
                <th class="text-end" style="width: 180px;">NOMINAL</th>
                <th class="text-end pe-4" style="width: 130px;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $item)
                <tr>
                    <td>
                        <span class="badge bg-light text-dark border font-monospace px-2 py-1" style="font-size: 0.78rem;">
                            {{ $item->transaction_code }}
                        </span>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $item->formatted_date }}
                        </div>
                    </td>
                    <td>
                        @if($item->isIncome())
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                                <i class="bi bi-arrow-down-left-circle-fill"></i> Masuk
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                                <i class="bi bi-arrow-up-right-circle-fill"></i> Keluar
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">{{ $item->title }}</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-light text-secondary border rounded-pill small" style="font-size: 0.72rem;">
                                {{ $item->category }}
                            </span>
                            @if($item->proof_path)
                                <button type="button" class="badge bg-info-subtle text-info border-0 text-decoration-none small py-1 px-2"
                                        title="Pratinjau Bukti Transaksi"
                                        @click="openPreviewModal({{ $item->id }})">
                                    <i class="bi bi-paperclip me-1"></i>Bukti
                                </button>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="small text-dark fw-medium">{{ $item->payment_method ?? 'Tunai' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            Oleh: {{ $item->user?->name ?? 'Sistem' }}
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="fw-bold fs-6 {{ $item->isIncome() ? 'text-success' : 'text-danger' }}">
                            {{ $item->isIncome() ? '+' : '-' }} {{ $item->formatted_amount }}
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" title="Pratinjau Transaksi (Popup Modal)"
                                    @click="openPreviewModal({{ $item->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="{{ route('admin.finances.edit', $item->id) }}" class="btn btn-outline-secondary" title="Sunting">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Hapus"
                                    @click="openDeleteModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ $item->formatted_amount }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="mb-3">
                            <i class="bi bi-wallet2 text-secondary opacity-50" style="font-size: 3.5rem;"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Tidak Ada Data Transaksi Keuangan</h6>
                        <p class="small text-muted mb-3">Belum ada catatan arus kas yang sesuai dengan kriteria filter.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('admin.finances.create', ['type' => 'in']) }}" class="btn btn-success btn-sm rounded-pill px-3">
                                <i class="bi bi-plus-circle me-1"></i> Catat Uang Masuk
                            </a>
                            <a href="{{ route('admin.finances.create', ['type' => 'out']) }}" class="btn btn-danger btn-sm rounded-pill px-3">
                                <i class="bi bi-dash-circle me-1"></i> Catat Uang Keluar
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="d-md-none p-3 d-flex flex-column gap-3">
    @forelse($transactions as $item)
        <div class="card border rounded-4 shadow-sm overflow-hidden border-start {{ $item->isIncome() ? 'border-success' : 'border-danger' }} border-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-light text-dark border font-monospace px-2 py-0.5" style="font-size: 0.72rem;">
                            {{ $item->transaction_code }}
                        </span>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $item->formatted_date }}
                        </div>
                    </div>
                    @if($item->isIncome())
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold small">
                            <i class="bi bi-arrow-down-left-circle-fill me-1"></i>Masuk
                        </span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-semibold small">
                            <i class="bi bi-arrow-up-right-circle-fill me-1"></i>Keluar
                        </span>
                    @endif
                </div>

                <h6 class="fw-bold text-dark mb-1">{{ $item->title }}</h6>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-light text-secondary border rounded-pill small">
                        {{ $item->category }}
                    </span>
                    @if($item->proof_path)
                        <button type="button" class="badge bg-info-subtle text-info border-0 small py-0.5 px-2"
                                title="Pratinjau Bukti Transaksi"
                                @click="openPreviewModal({{ $item->id }})">
                            <i class="bi bi-paperclip me-1"></i>Bukti
                        </button>
                    @endif
                    <span class="text-muted small">
                        &bull; {{ $item->payment_method ?? 'Tunai' }}
                    </span>
                </div>

                <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light mb-3">
                    <span class="text-muted small">Nominal:</span>
                    <span class="fw-bold fs-6 {{ $item->isIncome() ? 'text-success' : 'text-danger' }}">
                        {{ $item->isIncome() ? '+' : '-' }} {{ $item->formatted_amount }}
                    </span>
                </div>

                <div class="d-flex gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 d-flex align-items-center justify-content-center gap-1 py-1.5 fw-semibold"
                            @click="openPreviewModal({{ $item->id }})">
                        <i class="bi bi-eye"></i>
                        <span>Preview</span>
                    </button>
                    <a href="{{ route('admin.finances.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center justify-content-center" title="Sunting">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-flex align-items-center justify-content-center" title="Hapus"
                            @click="openDeleteModal({{ $item->id }}, '{{ addslashes($item->title) }}', '{{ $item->formatted_amount }}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 px-3 bg-white rounded-4 border">
            <div class="mb-3">
                <i class="bi bi-wallet2 text-secondary opacity-50" style="font-size: 3.5rem;"></i>
            </div>
            <h6 class="fw-bold mb-1">Tidak Ada Data Transaksi Keuangan</h6>
            <p class="small text-muted mb-3">Belum ada catatan arus kas yang sesuai dengan kriteria filter.</p>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('admin.finances.create', ['type' => 'in']) }}" class="btn btn-success btn-sm rounded-pill py-2">
                    <i class="bi bi-plus-circle me-1"></i> Catat Uang Masuk
                </a>
                <a href="{{ route('admin.finances.create', ['type' => 'out']) }}" class="btn btn-danger btn-sm rounded-pill py-2">
                    <i class="bi bi-dash-circle me-1"></i> Catat Uang Keluar
                </a>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="small text-muted">
        Menampilkan <strong>{{ $transactions->firstItem() ?? 0 }}</strong> - <strong>{{ $transactions->lastItem() ?? 0 }}</strong> dari total <strong>{{ $transactions->total() }}</strong> transaksi
    </div>
    <div>
        {{ $transactions->links() }}
    </div>
</div>
