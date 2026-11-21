<!-- Modal Pilihan Unduh PDF Surat (Surat Saja vs Surat + Lampiran) -->
<div class="modal fade" id="letterPdfDownloadModal" tabindex="-1" aria-labelledby="letterPdfDownloadModalLabel" aria-hidden="true" style="z-index: 1075;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="modal-title fw-bold text-dark mb-0" id="letterPdfDownloadModalLabel">
                            Pilih Format Unduhan PDF
                        </h6>
                        <div class="small text-muted text-truncate" id="letterPdfDownloadSubtitle">
                            Pilih apakah ingin menyertakan berkas lampiran.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="letterPdfDownloadCloseBtn"></button>
            </div>

            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Surat ini memiliki <strong class="text-dark"><span id="letterPdfModalAttachmentBadge">1</span> berkas lampiran PDF</strong>. Silakan pilih format dokumen yang ingin diunduh:
                </p>

                <div id="letterPdfDownloadError" class="alert alert-danger small rounded-3 py-2 px-3 d-none mb-3" role="alert"></div>

                <div class="d-grid gap-3">
                    <!-- Pilihan 1: File Surat Saja -->
                    <button type="button"
                            id="btnDownloadLetterOnly"
                            class="btn btn-outline-secondary rounded-4 p-3 text-start d-flex align-items-center justify-content-between gap-3 shadow-sm border bg-white text-dark">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-light border text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-file-earmark-pdf fs-4" data-icon></i>
                                <span class="spinner-border spinner-border-sm text-success d-none" role="status" aria-hidden="true" data-spinner></span>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" data-title>Unduh File Surat Saja</div>
                                <div class="small text-muted" data-desc>Hanya dokumen surat resmi berkop &amp; E-TTD (tanpa berkas lampiran).</div>
                            </div>
                        </div>
                        <i class="bi bi-download text-secondary fs-5 flex-shrink-0"></i>
                    </button>

                    <!-- Pilihan 2: File Surat + Lampiran (1 File PDF) -->
                    <button type="button"
                            id="btnDownloadLetterWithAttachments"
                            class="btn btn-outline-success rounded-4 p-3 text-start d-flex align-items-center justify-content-between gap-3 shadow-sm bg-success-subtle bg-opacity-10">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-3 bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-layers-fill fs-4" data-icon></i>
                                <span class="spinner-border spinner-border-sm text-white d-none" role="status" aria-hidden="true" data-spinner></span>
                            </div>
                            <div>
                                <div class="fw-bold text-dark d-flex align-items-center flex-wrap gap-2">
                                    <span data-title>Unduh Surat + Lampiran</span>
                                    <span class="badge bg-success rounded-pill fw-semibold" style="font-size: 0.7rem;">1 File PDF</span>
                                </div>
                                <div class="small text-muted" data-desc>
                                    Menggabungkan dokumen surat resmi beserta seluruh lampiran (<span id="letterPdfModalAttachmentCount">1</span> berkas) ke dalam satu file PDF.
                                </div>
                            </div>
                        </div>
                        <i class="bi bi-download text-success fs-5 flex-shrink-0"></i>
                    </button>
                </div>
            </div>

            <div class="modal-footer border-top px-4 py-3 bg-light">
                <button type="button" class="btn btn-light border rounded-pill px-4 btn-sm fw-semibold" data-bs-dismiss="modal" id="letterPdfDownloadCancelBtn">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        let activeLetterPdfOptions = null;
        let isLetterPdfChoiceBusy = false;

        function setTriggerButtonLoading(btn, isLoading) {
            if (!btn || !(btn instanceof HTMLElement)) {
                return;
            }
            btn.disabled = isLoading;
            if (isLoading) {
                if (!btn.dataset.originalHtml) {
                    btn.dataset.originalHtml = btn.innerHTML;
                }
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengunduh...';
            } else if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }

        function setOptionLoading(btnId, isLoading, loadingText, defaultText) {
            const btnOnly = document.getElementById('btnDownloadLetterOnly');
            const btnMerged = document.getElementById('btnDownloadLetterWithAttachments');
            const cancelBtn = document.getElementById('letterPdfDownloadCancelBtn');
            const closeBtn = document.getElementById('letterPdfDownloadCloseBtn');

            [btnOnly, btnMerged, cancelBtn, closeBtn].forEach(function (el) {
                if (el) el.disabled = isLoading;
            });

            const targetBtn = document.getElementById(btnId);
            if (!targetBtn) return;

            const iconEl = targetBtn.querySelector('[data-icon]');
            const spinnerEl = targetBtn.querySelector('[data-spinner]');
            const titleEl = targetBtn.querySelector('[data-title]');

            if (iconEl) iconEl.classList.toggle('d-none', isLoading);
            if (spinnerEl) spinnerEl.classList.toggle('d-none', !isLoading);
            if (titleEl) titleEl.textContent = isLoading ? loadingText : defaultText;
        }

        async function executeChoiceDownload(mode) {
            if (!activeLetterPdfOptions || isLetterPdfChoiceBusy) {
                return;
            }

            isLetterPdfChoiceBusy = true;
            const errorEl = document.getElementById('letterPdfDownloadError');
            if (errorEl) {
                errorEl.classList.add('d-none');
                errorEl.textContent = '';
            }

            const isMerged = mode === 'merged';
            const btnId = isMerged ? 'btnDownloadLetterWithAttachments' : 'btnDownloadLetterOnly';
            const loadingText = isMerged ? 'Menggabungkan & Mengunduh...' : 'Mengunduh File Surat...';
            const defaultText = isMerged ? 'Unduh Surat + Lampiran' : 'Unduh File Surat Saja';
            const url = isMerged ? activeLetterPdfOptions.mergedUrl : activeLetterPdfOptions.baseUrl;
            const filename = isMerged ? activeLetterPdfOptions.mergedFilename : activeLetterPdfOptions.baseFilename;

            setOptionLoading(btnId, true, loadingText, defaultText);

            try {
                if (typeof downloadPdfWithSpinner === 'function') {
                    await downloadPdfWithSpinner(url, filename, null);
                } else {
                    window.location.href = url;
                }

                const modalEl = document.getElementById('letterPdfDownloadModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            } catch (err) {
                console.error('Error downloading letter PDF:', err);
                if (errorEl) {
                    errorEl.textContent = 'Terjadi kesalahan saat menyiapkan file PDF. Silakan coba kembali.';
                    errorEl.classList.remove('d-none');
                }
            } finally {
                setOptionLoading(btnId, false, loadingText, defaultText);
                isLetterPdfChoiceBusy = false;
            }
        }

        window.handleLetterPdfDownload = function (options, externalLoadingHandler) {
            if (!options || !options.baseUrl) {
                return;
            }

            const attachmentsCount = Number(options.attachmentsCount || 0);
            if (attachmentsCount <= 0) {
                const handler = typeof externalLoadingHandler === 'function'
                    ? externalLoadingHandler
                    : function (val) { setTriggerButtonLoading(externalLoadingHandler, val); };

                if (typeof downloadPdfWithSpinner === 'function') {
                    downloadPdfWithSpinner(options.baseUrl, options.baseFilename, handler);
                } else {
                    window.location.href = options.baseUrl;
                }
                return;
            }

            const modalEl = document.getElementById('letterPdfDownloadModal');
            if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                window.location.href = options.baseUrl;
                return;
            }

            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            activeLetterPdfOptions = options;

            const subtitleEl = document.getElementById('letterPdfDownloadSubtitle');
            if (subtitleEl) {
                subtitleEl.textContent = (options.letterNumber ? options.letterNumber + ' • ' : '') + attachmentsCount + ' lampiran PDF';
            }

            const badgeEl = document.getElementById('letterPdfModalAttachmentBadge');
            if (badgeEl) badgeEl.textContent = String(attachmentsCount);

            const countEl = document.getElementById('letterPdfModalAttachmentCount');
            if (countEl) countEl.textContent = String(attachmentsCount);

            const errorEl = document.getElementById('letterPdfDownloadError');
            if (errorEl) {
                errorEl.classList.add('d-none');
                errorEl.textContent = '';
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        };

        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('letterPdfDownloadModal');
            if (!modalEl) return;

            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            const btnOnly = document.getElementById('btnDownloadLetterOnly');
            if (btnOnly) {
                btnOnly.addEventListener('click', function () {
                    executeChoiceDownload('only');
                });
            }

            const btnMerged = document.getElementById('btnDownloadLetterWithAttachments');
            if (btnMerged) {
                btnMerged.addEventListener('click', function () {
                    executeChoiceDownload('merged');
                });
            }

            modalEl.addEventListener('shown.bs.modal', function () {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '1070';
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                if (document.querySelector('.modal.show')) {
                    document.body.classList.add('modal-open');
                }
            });
        });
    })();
</script>
