<!-- Modal Pratinjau Berkas Lampiran (PDF) -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-labelledby="attachmentPreviewModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 960px;">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                    <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="modal-title fw-bold text-dark mb-0 text-truncate" id="attachmentPreviewModalLabel">
                            Pratinjau Berkas Lampiran
                        </h6>
                        <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">
                            <span id="attachmentPreviewFileName" class="text-truncate fw-medium text-dark">-</span>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5" id="attachmentPreviewFileSize">-</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-0 position-relative bg-dark-subtle" style="min-height: 520px; height: 75vh;">
                <!-- Loading State Spinner -->
                <div id="attachmentPreviewLoading" class="position-absolute top-50 start-50 translate-middle text-center" style="z-index: 2;">
                    <div class="spinner-border text-primary mb-2" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <div class="small text-muted fw-semibold">Memuat pratinjau berkas lampiran...</div>
                </div>

                <!-- PDF Preview Iframe -->
                <iframe id="attachmentPreviewIframe" 
                        src="about:blank" 
                        class="w-100 h-100 border-0" 
                        style="position: relative; z-index: 3;" 
                        onload="const l = document.getElementById('attachmentPreviewLoading'); if(l) l.style.display='none';">
                </iframe>
            </div>

            <div class="modal-footer border-top px-4 py-3 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-3 border fw-semibold" data-bs-dismiss="modal">
                        Tutup
                    </button>
                    <a id="attachmentPreviewNewTabBtn" 
                       href="#" 
                       target="_blank" 
                       class="btn btn-outline-secondary rounded-pill px-3 small fw-semibold d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Buka Tab Baru</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="attachmentPreviewDownloadBtn" 
                       href="#" 
                       class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Unduh Berkas Lampiran</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.openAttachmentPreviewModal = function(previewUrl, downloadUrl, fileName, fileSize) {
        let modalEl = document.getElementById('attachmentPreviewModal');
        if (!modalEl) {
            console.error('attachmentPreviewModal tidak ditemukan di halaman');
            window.location.href = downloadUrl;
            return;
        }

        // Pastikan modal berada langsung di bawah body agar tidak terhalang backdrop atau overflow parent
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        const nameEl = document.getElementById('attachmentPreviewFileName');
        if (nameEl) nameEl.textContent = fileName || 'Berkas Lampiran (PDF)';

        const sizeEl = document.getElementById('attachmentPreviewFileSize');
        if (sizeEl) sizeEl.textContent = fileSize || 'PDF';

        const loadingEl = document.getElementById('attachmentPreviewLoading');
        if (loadingEl) loadingEl.style.display = 'block';

        const iframeEl = document.getElementById('attachmentPreviewIframe');
        if (iframeEl) {
            iframeEl.src = previewUrl;
        }

        const newTabBtn = document.getElementById('attachmentPreviewNewTabBtn');
        if (newTabBtn) {
            newTabBtn.href = previewUrl;
        }

        const downloadBtn = document.getElementById('attachmentPreviewDownloadBtn');
        if (downloadBtn) {
            downloadBtn.href = downloadUrl;
            if (fileName) {
                downloadBtn.setAttribute('download', fileName);
            }
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else if (window.jQuery && typeof window.jQuery(modalEl).modal === 'function') {
            window.jQuery(modalEl).modal('show');
        } else {
            console.warn('Bootstrap Modal tidak tersedia, mengarahkan ke tautan berkas');
            window.open(downloadUrl || previewUrl, '_blank');
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('attachmentPreviewModal');
        if (modalEl) {
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
            modalEl.addEventListener('hidden.bs.modal', function () {
                const iframeEl = document.getElementById('attachmentPreviewIframe');
                if (iframeEl) iframeEl.src = 'about:blank';
                const loadingEl = document.getElementById('attachmentPreviewLoading');
                if (loadingEl) loadingEl.style.display = 'block';
            });
        }
    });
</script>
