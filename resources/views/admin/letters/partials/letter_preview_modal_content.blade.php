<div class="modal-header border-bottom px-4 py-3 bg-light">
    <div class="d-flex flex-column gap-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="{{ $letter->letter_type_badge_class }} px-2 py-0.5 rounded-pill small">
                {{ $letter->letter_type_label }}
            </span>
            @if(!empty($isDraftPreview))
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-0.5 rounded-pill small">
                    <i class="bi bi-eye-fill me-1"></i>Pratinjau Dokumen Utuh
                </span>
            @else
                <span class="{{ $letter->status_badge_class }} px-2.5 py-0.5 rounded-pill small">
                    {{ $letter->status_label }}
                </span>
                <span class="text-muted small font-monospace">Kode: {{ $letter->letter_code }}</span>
            @endif
        </div>
        <h5 class="modal-title fw-bold text-dark mb-0">{{ $letter->formatted_letter_number }}</h5>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-3 p-md-4 bg-white" style="max-height: 75vh; overflow-y: auto;">
    <div class="border rounded-4 p-3 p-md-4 shadow-sm bg-white">
        @include('admin.letters.partials.letter_preview_body', ['letter' => $letter])
    </div>
</div>

<div class="modal-footer border-top px-4 py-3 bg-light d-flex justify-content-between flex-wrap gap-2">
    @if(!empty($isDraftPreview))
        <div class="d-flex align-items-center gap-2 small text-muted">
            <i class="bi bi-info-circle text-primary me-1"></i>
            <span>Pratinjau tampilan utuh dokumen resmi berdasarkan data formulir Anda saat ini.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold shadow-sm" data-bs-dismiss="modal">
                <i class="bi bi-pencil-square me-1"></i> Tutup & Lanjutkan Menyunting
            </button>
        </div>
    @else
        <!-- Status E-TTD Badges -->
        <div class="d-flex align-items-center gap-2 small">
            <span class="badge {{ $letter->secretary_signed_at ? 'bg-success' : 'bg-light text-muted border' }}">
                <i class="bi {{ $letter->secretary_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i>
                Sekretaris {{ $letter->secretary_signed_at ? '('.$letter->secretary_signed_at->format('d/m H:i').')' : '' }}
            </span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge {{ $letter->chairman_signed_at ? 'bg-success' : 'bg-light text-muted border' }}">
                <i class="bi {{ $letter->chairman_signed_at ? 'bi-check-circle-fill' : 'bi-circle' }} me-1"></i>
                Ketua {{ $letter->chairman_signed_at ? '('.$letter->chairman_signed_at->format('d/m H:i').')' : '' }}
            </span>
        </div>

        <!-- Actions -->
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-secondary rounded-pill px-3 btn-sm" data-bs-dismiss="modal">
                Tutup
            </button>

            @if(auth()->user()->canEditLetter($letter))
                <a href="{{ route('admin.letters.edit', $letter->id) }}" class="btn btn-outline-secondary rounded-pill px-3 btn-sm">
                    <i class="bi bi-pencil me-1"></i> Sunting
                </a>
            @endif

            @if($letter->status === 'selesai')
                @php
                    $previewAttachmentsCount = $letter->relationLoaded('attachments')
                        ? $letter->attachments->count()
                        : $letter->attachments()->count();
                    $previewPdfSlug = \Illuminate\Support\Str::slug($letter->letter_number, '_');
                @endphp
                <button type="button" class="btn btn-outline-success rounded-pill px-3 btn-sm d-inline-flex align-items-center"
                        onclick="handleLetterPdfDownload({
                            baseUrl: '{{ route('admin.letters.download-pdf', $letter->id) }}',
                            mergedUrl: '{{ route('admin.letters.download-pdf', ['id' => $letter->id, 'include_attachments' => 1]) }}',
                            letterNumber: '{{ addslashes($letter->formatted_letter_number) }}',
                            baseFilename: 'Surat_{{ $previewPdfSlug }}.pdf',
                            mergedFilename: 'Surat_{{ $previewPdfSlug }}_dan_Lampiran.pdf',
                            attachmentsCount: {{ $previewAttachmentsCount }}
                        }, this)">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Unduh PDF
                </button>
            @endif

            <a href="{{ route('admin.letters.show', $letter->id) }}" class="btn btn-primary rounded-pill px-3 btn-sm fw-semibold">
                <i class="bi bi-box-arrow-up-right me-1"></i> Halaman E-TTD Lengkap
            </a>
        </div>
    @endif
</div>
