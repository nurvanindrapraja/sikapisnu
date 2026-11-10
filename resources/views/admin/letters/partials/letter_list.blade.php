<!-- Desktop Table View (>= 768px) -->
<div class="d-none d-md-block table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light text-muted small text-uppercase">
            <tr>
                <th class="ps-4" style="width: 5%;">No</th>
                <th style="width: 25%;">Nomor & Perihal Surat</th>
                <th style="width: 20%;">Tujuan & Tanggal</th>
                <th style="width: 22%;">Status & E-TTD</th>
                <th style="width: 13%;">Pengaju</th>
                <th class="text-end pe-4" style="width: 15%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($letters as $index => $item)
                <tr>
                    <td class="ps-4 text-muted small">{{ $letters->firstItem() + $index }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <span class="{{ $item->letter_type_badge_class }} rounded-pill" style="font-size: 0.72rem; padding: 0.2rem 0.55rem;">
                                {{ $item->letter_type_label }}
                            </span>
                            @if($item->isLevelPac())
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill" style="font-size: 0.7rem;">
                                    PAC {{ $item->pac?->name }}
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.7rem;">
                                    PC ISNU
                                </span>
                            @endif
                        </div>
                        <div class="fw-bold text-dark fs-6">{{ $item->letter_number }}</div>
                        <div class="text-primary small fw-semibold text-truncate" style="max-width: 280px;" title="{{ $item->perihal }}">
                            {{ $item->perihal }}
                        </div>
                        <div class="text-muted font-monospace" style="font-size: 0.75rem;">
                            ID: {{ $item->letter_code }}
                        </div>
                    </td>
                    <td>
                        @if($item->isMandat())
                            <div class="fw-semibold text-dark"><i class="bi bi-award me-1 text-primary"></i>Kegiatan: {{ $item->kegiatan ?: '-' }}</div>
                            @if($item->lokasi_pelaksanaan)
                                <div class="text-muted small">
                                    <i class="bi bi-geo-alt me-1"></i>{{ $item->lokasi_pelaksanaan }}
                                </div>
                            @endif
                        @elseif($item->isRekomendasi())
                            <div class="fw-semibold text-dark"><i class="bi bi-patch-check me-1 text-warning"></i>Rekomendasi Resmi PC ISNU</div>
                        @else
                            <div class="fw-semibold text-dark">{{ $item->tujuan_surat }}</div>
                            <div class="text-muted small">
                                <i class="bi bi-geo-alt me-1"></i>Tujuan: {{ $item->kota_tujuan ?? 'Surabaya' }}
                            </div>
                        @endif
                        <div class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i>{{ $item->kota_asal ?? 'Surabaya' }}, {{ $item->formatted_date_letter }}
                        </div>
                    </td>
                    <td>
                        <div class="mb-2">
                            <span class="{{ $item->status_badge_class }} px-2 py-1 rounded-pill small">
                                {{ $item->status_label }}
                            </span>
                        </div>
                        <!-- Progres Tanda Tangan Bertahap -->
                        <div class="d-flex align-items-center gap-2 small">
                            <span class="badge {{ $item->secretary_signed_at ? 'bg-success' : 'bg-light text-muted border' }}" title="{{ $item->secretary_signed_at ? 'Sudah ditandatangani Sekretaris' : 'Belum ditandatangani Sekretaris' }}">
                                <i class="bi {{ $item->secretary_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i> Sekretaris
                            </span>
                            <i class="bi bi-arrow-right text-muted small"></i>
                            <span class="badge {{ $item->chairman_signed_at ? 'bg-success' : 'bg-light text-muted border' }}" title="{{ $item->chairman_signed_at ? 'Sudah ditandatangani Ketua' : 'Belum ditandatangani Ketua' }}">
                                <i class="bi {{ $item->chairman_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i> Ketua
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold small text-dark">{{ $item->user?->name ?? 'User Sistem' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.letters.show', $item->id) }}" class="btn btn-outline-primary" title="Lihat Detail & E-TTD">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if(auth()->user()->canEditLetter($item))
                                <a href="{{ route('admin.letters.edit', $item->id) }}" class="btn btn-outline-secondary" title="Sunting Konsep">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endif
                            @if($item->status === 'selesai')
                                <button type="button" class="btn btn-outline-success" title="Unduh PDF Resmi"
                                        :disabled="downloadingId === {{ $item->id }}"
                                        @click="downloadPdfWithSpinner('{{ route('admin.letters.download-pdf', $item->id) }}', 'Surat_{{ \Illuminate\Support\Str::slug($item->letter_number, '_') }}.pdf', (val) => downloadingId = val ? {{ $item->id }} : null)">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="downloadingId === {{ $item->id }}" x-cloak></span>
                                    <i class="bi bi-file-earmark-pdf" x-show="downloadingId !== {{ $item->id }}"></i>
                                </button>
                            @else
                                <button type="button" class="btn btn-outline-secondary disabled" disabled title="File PDF baru dapat diunduh setelah surat selesai ditandatangani secara lengkap">
                                    <i class="bi bi-lock-fill"></i>
                                </button>
                            @endif
                            @if(($item->status === 'draft' || $item->status === 'ditolak') && (auth()->user()->canEditLetter($item) || auth()->user()->isSuperAdmin()))
                                <button type="button" class="btn btn-outline-danger" title="Hapus Konsep"
                                        :disabled="deletingId !== null"
                                        @click="openDeleteModal({{ $item->id }}, '{{ addslashes($item->letter_number) }}')">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="deletingId === {{ $item->id }}" x-cloak></span>
                                    <i class="bi bi-trash" x-show="deletingId !== {{ $item->id }}"></i>
                                </button>
                            @endif
                        </div>
                        @if(($item->status === 'draft' || $item->status === 'ditolak') && (auth()->user()->canEditLetter($item) || auth()->user()->isSuperAdmin()))
                            <form id="delete-letter-{{ $item->id }}" action="{{ route('admin.letters.destroy', $item->id) }}" method="POST" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-text text-secondary opacity-50" style="font-size: 3.5rem;"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Tidak Ada Konsep Surat Ditemukan</h6>
                        <p class="small text-muted mb-3">Tidak ada data surat yang sesuai dengan kriteria pencarian atau status terpilih.</p>
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#chooseLetterTypeModal">
                            <i class="bi bi-plus-circle me-1"></i> Buat Konsep Surat Baru
                        </button>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (< 768px) -->
<div class="d-md-none p-3 d-flex flex-column gap-3">
    @forelse($letters as $item)
        <div class="card border rounded-4 shadow-sm bg-white overflow-hidden">
            <!-- Card Header: Badges & Status -->
            <div class="card-header bg-light border-0 py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1">
                    <span class="{{ $item->letter_type_badge_class }} rounded-pill" style="font-size: 0.72rem; padding: 0.2rem 0.55rem;">
                        {{ $item->letter_type_label }}
                    </span>
                    @if($item->isLevelPac())
                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill" style="font-size: 0.7rem;">
                            PAC {{ $item->pac?->name }}
                        </span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.7rem;">
                            PC ISNU
                        </span>
                    @endif
                </div>
                <span class="{{ $item->status_badge_class }} px-2.5 py-1 rounded-pill small fw-semibold">
                    {{ $item->status_label }}
                </span>
            </div>

            <!-- Card Body -->
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-1 fs-6">{{ $item->letter_number }}</h6>
                <div class="text-primary small fw-semibold mb-1">
                    {{ $item->perihal }}
                </div>
                <div class="text-muted font-monospace mb-3" style="font-size: 0.75rem;">
                    ID: {{ $item->letter_code }}
                </div>

                <!-- Info Box -->
                <div class="bg-light p-2.5 rounded-3 mb-3 small">
                    <!-- Tujuan / Kegiatan -->
                    <div class="mb-2 d-flex align-items-start gap-2">
                        <i class="bi bi-geo-alt text-danger mt-0.5"></i>
                        <div class="flex-grow-1">
                            @if($item->isMandat())
                                <div class="fw-semibold text-dark">Kegiatan: {{ $item->kegiatan ?: '-' }}</div>
                                @if($item->lokasi_pelaksanaan)
                                    <div class="text-muted small">{{ $item->lokasi_pelaksanaan }}</div>
                                @endif
                            @elseif($item->isRekomendasi())
                                <div class="fw-semibold text-dark">Rekomendasi Resmi PC ISNU</div>
                            @else
                                <div class="fw-semibold text-dark">{{ $item->tujuan_surat }}</div>
                                <div class="text-muted small">Tujuan: {{ $item->kota_tujuan ?? 'Surabaya' }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-primary"></i>
                        <span class="text-secondary">{{ $item->kota_asal ?? 'Surabaya' }}, {{ $item->formatted_date_letter }}</span>
                    </div>

                    <!-- Pengaju -->
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle text-secondary"></i>
                        <span class="text-muted">{{ $item->user?->name ?? 'User Sistem' }} &bull; {{ $item->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                <!-- Progress E-TTD -->
                <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border bg-white mb-3">
                    <span class="small text-muted fw-semibold">Alur E-TTD:</span>
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="badge {{ $item->secretary_signed_at ? 'bg-success' : 'bg-light text-muted border' }}" title="{{ $item->secretary_signed_at ? 'Sudah ditandatangani Sekretaris' : 'Belum ditandatangani' }}">
                            <i class="bi {{ $item->secretary_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i> Sekr
                        </span>
                        <i class="bi bi-arrow-right text-muted small"></i>
                        <span class="badge {{ $item->chairman_signed_at ? 'bg-success' : 'bg-light text-muted border' }}" title="{{ $item->chairman_signed_at ? 'Sudah ditandatangani Ketua' : 'Belum ditandatangani' }}">
                            <i class="bi {{ $item->chairman_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i> Ketua
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 pt-2 border-top">
                    <a href="{{ route('admin.letters.show', $item->id) }}" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 d-flex align-items-center justify-content-center gap-1 py-1.5 fw-semibold">
                        <i class="bi bi-eye"></i>
                        <span>Detail & E-TTD</span>
                    </a>
                    @if(auth()->user()->canEditLetter($item))
                        <a href="{{ route('admin.letters.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center justify-content-center" title="Sunting Konsep">
                            <i class="bi bi-pencil"></i>
                        </a>
                    @endif
                    @if($item->status === 'selesai')
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 d-flex align-items-center justify-content-center" title="Unduh PDF Resmi"
                                :disabled="downloadingId === {{ $item->id }}"
                                @click="downloadPdfWithSpinner('{{ route('admin.letters.download-pdf', $item->id) }}', 'Surat_{{ \Illuminate\Support\Str::slug($item->letter_number, '_') }}.pdf', (val) => downloadingId = val ? {{ $item->id }} : null)">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="downloadingId === {{ $item->id }}" x-cloak></span>
                            <i class="bi bi-file-earmark-pdf" x-show="downloadingId !== {{ $item->id }}"></i>
                        </button>
                    @else
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 disabled" disabled title="File PDF baru dapat diunduh setelah surat selesai ditandatangani secara lengkap">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    @endif
                    @if(($item->status === 'draft' || $item->status === 'ditolak') && (auth()->user()->canEditLetter($item) || auth()->user()->isSuperAdmin()))
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-flex align-items-center justify-content-center" title="Hapus Konsep"
                                :disabled="deletingId !== null"
                                @click="openDeleteModal({{ $item->id }}, '{{ addslashes($item->letter_number) }}')">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" x-show="deletingId === {{ $item->id }}" x-cloak></span>
                            <i class="bi bi-trash" x-show="deletingId !== {{ $item->id }}"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 px-3 bg-white rounded-4 border">
            <div class="mb-3">
                <i class="bi bi-file-earmark-text text-secondary opacity-50" style="font-size: 3.5rem;"></i>
            </div>
            <h6 class="fw-bold mb-1">Tidak Ada Konsep Surat Ditemukan</h6>
            <p class="small text-muted mb-3">Tidak ada data surat yang sesuai dengan kriteria pencarian atau status terpilih.</p>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#chooseLetterTypeModal">
                <i class="bi bi-plus-circle me-1"></i> Buat Konsep Surat Baru
            </button>
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
@if($letters->hasPages())
    <div class="card-footer bg-white border-0 py-3" @click="handlePagination($event)">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                Menampilkan {{ $letters->firstItem() ?? 0 }} - {{ $letters->lastItem() ?? 0 }} dari {{ $letters->total() }} konsep surat
            </div>
            <div>
                {{ $letters->links() }}
            </div>
        </div>
    </div>
@endif
