@php
    $initialHtmlAttachments = old('html_attachments');
    if (! is_array($initialHtmlAttachments)) {
        $initialHtmlAttachments = isset($letter) && $letter->relationLoaded('htmlAttachments')
            ? $letter->htmlAttachments->map(fn ($att) => [
                'title' => (string) ($att->title ?? ''),
                'content' => (string) ($att->content ?? ''),
            ])->values()->all()
            : [];
    } else {
        $initialHtmlAttachments = array_values(array_map(fn ($att) => [
            'title' => (string) ($att['title'] ?? ''),
            'content' => (string) ($att['content'] ?? ''),
        ], $initialHtmlAttachments));
    }
@endphp

<!-- Lampiran Teks HTML (Opsional & Multi-Lampiran) -->
<div class="mt-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-2 mb-3">
        <h5 class="fw-bold text-success mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-richtext"></i>
            <span>Lampiran Teks HTML</span>
            <span class="badge bg-secondary-subtle text-secondary fw-semibold" style="font-size: 0.72rem;">Opsional</span>
        </h5>
        <button type="button"
                class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1"
                onclick="addHtmlAttachmentCard()">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Lampiran Teks</span>
        </button>
    </div>

    <p class="small text-muted mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Gunakan lampiran teks HTML untuk mengakomodir <strong>daftar tujuan surat yang banyak</strong>, daftar peserta, susunan acara, atau lampiran lain tanpa perlu unggah PDF. Anda dapat menambahkan lebih dari satu lampiran; setiap lampiran otomatis dicetak pada halaman baru berkop resmi ISNU di file PDF surat.
    </p>

    <div id="htmlAttachmentsEmpty" class="p-3 bg-light rounded-3 border border-dashed text-center text-muted small {{ count($initialHtmlAttachments) > 0 ? 'd-none' : '' }}">
        <i class="bi bi-file-earmark-text fs-5 d-block mb-1 text-secondary"></i>
        Belum ada lampiran teks HTML. Klik tombol <strong>+ Tambah Lampiran Teks</strong> di atas jika diperlukan.
    </div>

    <div id="htmlAttachmentsContainer" class="d-flex flex-column gap-3" x-ignore></div>
</div>

@push('scripts')
<script>
    (function () {
        const initialHtmlAttachments = @json($initialHtmlAttachments);
        const htmlAttachmentEditors = new Map();
        let htmlAttachmentCounter = 0;

        function cleanHtmlAttachmentContent(quill) {
            const text = quill.getText().trim();
            const html = quill.root.innerHTML;
            const hasTableOrImg = quill.root.querySelector('table, img') !== null;
            if (!text && !hasTableOrImg && (!html || html === '<p><br></p>')) {
                return '';
            }
            return html;
        }

        function ensureAttachmentTableClasses(rootEl) {
            if (!rootEl) return;
            rootEl.querySelectorAll('table').forEach(table => {
                if (!table.classList.contains('table-bordered') && !table.classList.contains('table-borderless')) {
                    const borderAttr = table.getAttribute('border');
                    if (borderAttr === '0') {
                        table.classList.add('table-borderless');
                    } else {
                        table.classList.add('table-bordered');
                        table.setAttribute('border', '1');
                    }
                }
            });
        }

        function updateAttachmentNumbersAndEmptyState() {
            const container = document.getElementById('htmlAttachmentsContainer');
            const emptyState = document.getElementById('htmlAttachmentsEmpty');
            if (!container) return;

            const cards = container.querySelectorAll('.html-attachment-card');
            cards.forEach((card, index) => {
                const label = card.querySelector('.html-attachment-number');
                if (label) {
                    label.textContent = `Lampiran Teks #${index + 1}`;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', cards.length > 0);
            }
        }

        function getAttachmentTemplateHtml(templateType) {
            if (templateType === 'tujuan') {
                return `
                    <p><strong>Daftar Penerima / Tujuan Surat:</strong></p>
                    <ol>
                        <li>Ketua PAC ISNU Kecamatan ...</li>
                        <li>Ketua PAC ISNU Kecamatan ...</li>
                        <li>Ketua PAC ISNU Kecamatan ...</li>
                    </ol>
                `;
            }
            if (templateType === 'tabel_tujuan') {
                return `
                    <table class="table table-bordered" border="1" style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <tr>
                                <td style="width: 8%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>No</strong></td>
                                <td style="width: 47%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>Nama / Jabatan / Instansi Tujuan</strong></td>
                                <td style="width: 45%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>Alamat / Wilayah</strong></td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">1</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Ketua PAC ISNU ...</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Surabaya</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">2</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Ketua PAC ISNU ...</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Surabaya</td>
                            </tr>
                        </tbody>
                    </table>
                `;
            }
            if (templateType === 'agenda') {
                return `
                    <table class="table table-bordered" border="1" style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <tr>
                                <td style="width: 8%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>No</strong></td>
                                <td style="width: 24%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>Waktu</strong></td>
                                <td style="width: 43%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>Kegiatan / Agenda</strong></td>
                                <td style="width: 25%; border: 1px solid #333333; padding: 6px 10px; background-color: #f1f5f9;"><strong>Penanggung Jawab</strong></td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">1</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">08.00 - 08.30 WIB</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Registrasi Peserta</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Kesekretariatan</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">2</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">08.30 - 09.30 WIB</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Pembukaan &amp; Sambutan</td>
                                <td style="border: 1px solid #333333; padding: 6px 10px;">Panitia</td>
                            </tr>
                        </tbody>
                    </table>
                `;
            }
            return `
                <table class="table table-borderless" border="0" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="width: 28%; border: none; padding: 4px 8px;">Keterangan 1</td>
                            <td style="width: 4%; border: none; padding: 4px 8px;">:</td>
                            <td style="width: 68%; border: none; padding: 4px 8px;">...</td>
                        </tr>
                        <tr>
                            <td style="width: 28%; border: none; padding: 4px 8px;">Keterangan 2</td>
                            <td style="width: 4%; border: none; padding: 4px 8px;">:</td>
                            <td style="width: 68%; border: none; padding: 4px 8px;">...</td>
                        </tr>
                    </tbody>
                </table>
            `;
        }

        function escapeAttr(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        window.addHtmlAttachmentCard = function (initialData = null) {
            if (typeof Quill === 'undefined') {
                setTimeout(() => window.addHtmlAttachmentCard(initialData), 100);
                return;
            }

            const container = document.getElementById('htmlAttachmentsContainer');
            if (!container) return;

            const key = htmlAttachmentCounter++;
            const titleVal = initialData && initialData.title ? initialData.title : '';
            const contentVal = initialData && initialData.content ? initialData.content : '';

            const card = document.createElement('div');
            card.className = 'card border rounded-3 shadow-sm bg-white html-attachment-card';
            card.dataset.attachmentKey = String(key);

            card.innerHTML = `
                <div class="card-header bg-light border-bottom py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold html-attachment-number">
                        Lampiran Teks
                    </span>
                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div class="dropdown">
                            <button type="button" class="btn btn-xs btn-outline-success dropdown-toggle rounded-pill px-2.5 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-table me-1"></i>Sisipkan Format
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2 small" style="min-width: 240px;">
                                <li>
                                    <button type="button" class="dropdown-item rounded-2 py-1.5" onclick="insertHtmlAttachmentTemplate(${key}, 'tujuan')">
                                        <i class="bi bi-list-ol text-success me-2"></i>Daftar Tujuan Surat (Bernomor)
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item rounded-2 py-1.5" onclick="insertHtmlAttachmentTemplate(${key}, 'tabel_tujuan')">
                                        <i class="bi bi-table text-primary me-2"></i>Tabel Daftar Tujuan / Peserta
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item rounded-2 py-1.5" onclick="insertHtmlAttachmentTemplate(${key}, 'agenda')">
                                        <i class="bi bi-calendar-week text-warning me-2"></i>Tabel Susunan Acara
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item rounded-2 py-1.5" onclick="insertHtmlAttachmentTemplate(${key}, 'borderless')">
                                        <i class="bi bi-border-none text-secondary me-2"></i>Tabel Rincian Tanpa Border
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" onclick="htmlAttachmentAddTableRow(${key})" title="Tambah baris pada tabel terakhir di lampiran ini">
                            <i class="bi bi-plus-lg"></i> Baris Tabel
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1" onclick="removeHtmlAttachmentCard(${key})" title="Hapus lampiran teks ini">
                            <i class="bi bi-trash me-1"></i>Hapus
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1">
                            Judul Lampiran <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <input type="text"
                               name="html_attachments[${key}][title]"
                               class="form-control form-control-sm"
                               value="${escapeAttr(titleVal)}"
                               placeholder="Contoh: Daftar Tujuan Undangan / Susunan Acara / Daftar Peserta">
                    </div>
                    <div>
                        <label class="form-label fw-semibold small mb-1">Isi Lampiran (Editor HTML)</label>
                        <div id="html-att-editor-${key}" class="bg-white html-att-quill-editor"></div>
                        <textarea name="html_attachments[${key}][content]" id="html-att-input-${key}" class="d-none"></textarea>
                    </div>
                </div>
            `;

            container.appendChild(card);

            const editorEl = document.getElementById(`html-att-editor-${key}`);
            const textareaEl = document.getElementById(`html-att-input-${key}`);

            if (contentVal) {
                const normalized = contentVal
                    .replace(/<th\b/gi, '<td')
                    .replace(/<\/th>/gi, '</td>')
                    .replace(/<\/?thead\b[^>]*>/gi, '');
                editorEl.innerHTML = normalized;
            }

            const quill = new Quill(`#html-att-editor-${key}`, {
                theme: 'snow',
                placeholder: 'Tuliskan daftar tujuan surat, susunan acara, atau teks lampiran di sini...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['clean']
                    ]
                }
            });

            quill.root.style.minHeight = '160px';
            if (quill.container) {
                quill.container.style.minHeight = '160px';
            }

            const syncThis = () => {
                ensureAttachmentTableClasses(quill.root);
                textareaEl.value = cleanHtmlAttachmentContent(quill);
            };

            quill.on('text-change', syncThis);
            syncThis();

            htmlAttachmentEditors.set(key, { quill, textareaEl, card });
            updateAttachmentNumbersAndEmptyState();
        };

        window.removeHtmlAttachmentCard = function (key) {
            const entry = htmlAttachmentEditors.get(key);
            if (entry) {
                entry.card.remove();
                htmlAttachmentEditors.delete(key);
            }
            updateAttachmentNumbersAndEmptyState();
        };

        window.insertHtmlAttachmentTemplate = function (key, templateType) {
            const entry = htmlAttachmentEditors.get(key);
            if (!entry) return;

            const templateHtml = getAttachmentTemplateHtml(templateType);
            const currentHtml = cleanHtmlAttachmentContent(entry.quill);
            if (!currentHtml) {
                entry.quill.root.innerHTML = templateHtml;
            } else {
                entry.quill.root.insertAdjacentHTML('beforeend', templateHtml);
            }
            ensureAttachmentTableClasses(entry.quill.root);
            entry.textareaEl.value = cleanHtmlAttachmentContent(entry.quill);
        };

        window.htmlAttachmentAddTableRow = function (key) {
            const entry = htmlAttachmentEditors.get(key);
            if (!entry) return;

            const tables = entry.quill.root.querySelectorAll('table');
            if (!tables.length) {
                window.insertHtmlAttachmentTemplate(key, 'tabel_tujuan');
                return;
            }

            const table = tables[tables.length - 1];
            const tbody = table.querySelector('tbody') || table;
            const rows = tbody.querySelectorAll('tr');
            if (!rows.length) return;

            const lastRow = rows[rows.length - 1];
            const cols = lastRow.querySelectorAll('td, th').length;
            const isBordered = table.classList.contains('table-bordered') || table.getAttribute('border') === '1';
            const cellStyle = isBordered
                ? 'border: 1px solid #333333; padding: 6px 10px;'
                : 'border: none; padding: 4px 8px;';

            const tr = document.createElement('tr');
            for (let i = 0; i < cols; i++) {
                const td = document.createElement('td');
                td.setAttribute('style', cellStyle);
                td.innerHTML = i === 0 && isBordered ? String(rows.length) : '<br>';
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
            entry.textareaEl.value = cleanHtmlAttachmentContent(entry.quill);
        };

        window.syncHtmlAttachments = function () {
            htmlAttachmentEditors.forEach(({ quill, textareaEl }) => {
                ensureAttachmentTableClasses(quill.root);
                textareaEl.value = cleanHtmlAttachmentContent(quill);
            });
        };

        function initInitialHtmlAttachments() {
            if (typeof Quill === 'undefined') {
                setTimeout(initInitialHtmlAttachments, 100);
                return;
            }
            if (Array.isArray(initialHtmlAttachments) && initialHtmlAttachments.length > 0) {
                initialHtmlAttachments.forEach(item => window.addHtmlAttachmentCard(item));
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initInitialHtmlAttachments);
        } else {
            initInitialHtmlAttachments();
        }
    })();
</script>
@endpush
