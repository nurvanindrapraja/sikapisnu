<!-- Desktop Table View (>= 768px) -->
<div class="d-none d-md-block table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Program Kerja</th>
                <th>Seksi / Bidang Pengampu</th>
                <th>Indikator Capaian</th>
                <th>Target Capaian</th>
                <th class="text-center">Kegiatan</th>
                <th>Status</th>
                <th class="text-end" style="width: 120px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($programs as $index => $prg)
                @php
                    $canManageProgram = auth()->user()->canManageSectionProgramOrEvent($prg->section_id);
                    $hasEvents = $prg->events_count > 0;
                @endphp
                <tr>
                    <td>{{ $programs->firstItem() + $index }}</td>
                    <td>
                        <strong class="d-block text-dark">{{ $prg->name }}</strong>
                        @if($prg->description)
                            @php $plainDesc = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($prg->description), ENT_QUOTES, 'UTF-8'))); @endphp
                            @if($plainDesc !== '')
                                <small class="text-muted d-block mt-0.5">{{ Str::limit($plainDesc, 95) }}</small>
                            @endif
                        @endif
                        <div class="d-flex align-items-center gap-1.5 mt-1">
                            @if($prg->code)
                                <code class="small text-muted">{{ $prg->code }}</code>
                            @endif
                            @if($prg->year)
                                <span class="badge bg-light text-secondary border small px-2 py-0.5 rounded-pill">{{ $prg->year }}</span>
                            @endif
                            @if($prg->description)
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 text-success text-decoration-none small fw-semibold"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetailProgram{{ $prg->id }}">
                                    <i class="bi bi-file-earmark-richtext me-0.5"></i> Lihat Deskripsi
                                </button>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($prg->section)
                            <strong class="d-block text-dark small">{{ $prg->section->name }}</strong>
                            @if($prg->section->level === 'PC ISNU')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill small">
                                    <i class="bi bi-building me-1"></i> PC ISNU Kota
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill small">
                                    <i class="bi bi-geo-alt me-1"></i> PAC {{ $prg->section->pac->name ?? '' }}
                                </span>
                            @endif
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                    <td class="small text-secondary" style="max-width: 260px;">
                        @if($prg->indicator)
                            <div class="d-flex align-items-start gap-1.5">
                                <i class="bi bi-check2-circle text-success mt-0.5 flex-shrink-0"></i>
                                <span>{{ $prg->indicator }}</span>
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="small" style="max-width: 240px;">
                        @if($prg->target)
                            <div class="d-flex align-items-start gap-1.5">
                                <i class="bi bi-bullseye text-danger mt-0.5 flex-shrink-0"></i>
                                <span class="fw-semibold text-dark">{{ $prg->target }}</span>
                            </div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-dark rounded-pill px-2.5 py-1" title="Kegiatan terhubung">
                            <i class="bi bi-calendar-event me-1"></i> {{ $prg->events_count }}
                        </span>
                    </td>
                    <td>
                        @if($prg->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                <i class="bi bi-check-circle me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                <i class="bi bi-dash-circle me-1"></i> Non-Aktif
                            </span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            @if($canManageProgram)
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-1 d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditProgram{{ $prg->id }}"
                                        title="Edit Program">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                @if($hasEvents)
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle p-1 d-flex align-items-center justify-content-center opacity-50"
                                            style="width: 32px; height: 32px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDeleteProgram{{ $prg->id }}"
                                            title="Program tidak dapat dihapus karena sudah memiliki {{ $prg->events_count }} kegiatan">
                                        <i class="bi bi-lock-fill"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDeleteProgram{{ $prg->id }}"
                                            title="Hapus Program">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border px-2 py-1 small" title="Hanya Pengurus Seksi bersangkutan atau Ketua/Sekretaris yang dapat mengelola">
                                    <i class="bi bi-lock-fill me-1"></i> Terkunci
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-card-checklist fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada data Program Kerja yang ditambahkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (< 768px) -->
<div class="d-md-none p-3 d-flex flex-column gap-3">
    @forelse($programs as $prg)
        @php
            $canManageProgram = auth()->user()->canManageSectionProgramOrEvent($prg->section_id);
            $hasEvents = $prg->events_count > 0;
        @endphp
        <div class="card border rounded-4 shadow-sm bg-white overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="min-w-0">
                        <h6 class="fw-bold text-dark mb-1 text-break">{{ $prg->name }}</h6>
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            @if($prg->code)
                                <code class="small text-muted">{{ $prg->code }}</code>
                            @endif
                            @if($prg->year)
                                <span class="badge bg-light text-secondary border rounded-pill small">{{ $prg->year }}</span>
                            @endif
                        </div>
                    </div>
                    @if($prg->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill flex-shrink-0">Aktif</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill flex-shrink-0">Non-Aktif</span>
                    @endif
                </div>

                @if($prg->section)
                    <div class="mb-2 d-flex flex-wrap align-items-center gap-1">
                        <span class="badge bg-light text-dark border rounded-pill small">
                            <i class="bi bi-diagram-2 text-success me-1"></i> {{ $prg->section->name }}
                        </span>
                        <span class="badge bg-dark rounded-pill small">
                            <i class="bi bi-calendar-event me-1"></i> {{ $prg->events_count }} Kegiatan
                        </span>
                    </div>
                @endif

                <div class="bg-light rounded-3 p-2.5 small mb-3">
                    <div class="mb-1.5">
                        <span class="text-muted d-block">Indikator Capaian:</span>
                        <span class="text-secondary">{{ $prg->indicator ?: '-' }}</span>
                    </div>
                    <div class="pt-1.5 border-top">
                        <span class="text-muted d-block">Target Capaian:</span>
                        <span class="fw-semibold text-dark">{{ $prg->target ?: '-' }}</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                    <div>
                        @if($prg->description)
                            <button type="button"
                                    class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetailProgram{{ $prg->id }}">
                                <i class="bi bi-file-earmark-richtext me-1"></i> Deskripsi
                            </button>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                    @if($canManageProgram)
                        <button type="button"
                                class="btn btn-sm btn-outline-warning rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 34px; height: 34px;"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditProgram{{ $prg->id }}"
                                title="Edit Program"
                                aria-label="Edit Program">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button"
                                class="btn btn-sm {{ $hasEvents ? 'btn-outline-secondary opacity-50' : 'btn-outline-danger' }} rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 34px; height: 34px;"
                                data-bs-toggle="modal"
                                data-bs-target="#modalDeleteProgram{{ $prg->id }}"
                                title="{{ $hasEvents ? 'Program tidak dapat dihapus' : 'Hapus Program' }}"
                                aria-label="{{ $hasEvents ? 'Program tidak dapat dihapus' : 'Hapus Program' }}">
                            <i class="bi {{ $hasEvents ? 'bi-lock-fill' : 'bi-trash-fill' }}"></i>
                        </button>
                    @else
                        <span class="badge bg-light text-muted border px-2 py-1 small">
                            <i class="bi bi-lock-fill me-1"></i> Terkunci
                        </span>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted bg-light rounded-4">
            <i class="bi bi-card-checklist fs-1 d-block mb-2 text-secondary"></i>
            Belum ada data Program Kerja yang ditambahkan.
        </div>
    @endforelse
</div>

<!-- Modals Detail, Edit & Hapus Program -->
@foreach($programs as $prg)
    @if($prg->description)
    <div class="modal fade text-start" id="modalDetailProgram{{ $prg->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-isnu text-white border-0 p-3 px-4">
                    <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-richtext me-1"></i> Detail Program Kerja</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <h5 class="fw-bold text-dark mb-1">{{ $prg->name }}</h5>
                        <div class="d-flex flex-wrap align-items-center gap-1.5">
                            @if($prg->section)
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">{{ $prg->section->name }}</span>
                            @endif
                            @if($prg->code)
                                <code class="small text-muted">{{ $prg->code }}</code>
                            @endif
                            @if($prg->year)
                                <span class="badge bg-light text-secondary border rounded-pill">{{ $prg->year }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100 border">
                                <small class="text-muted fw-semibold d-block mb-1"><i class="bi bi-check2-circle text-success me-1"></i> Indikator Capaian</small>
                                <div class="small text-dark">{{ $prg->indicator ?: '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded-3 p-3 h-100 border">
                                <small class="text-muted fw-semibold d-block mb-1"><i class="bi bi-bullseye text-danger me-1"></i> Target Capaian</small>
                                <div class="small fw-semibold text-dark">{{ $prg->target ?: '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 bg-white">
                        <small class="text-muted fw-semibold d-block mb-2 border-bottom pb-2">Deskripsi Program Kerja</small>
                        <div class="program-html-content small text-dark">{!! $prg->description !!}</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal fade text-start" id="modalEditProgram{{ $prg->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-program-wide">
            <form action="{{ route('admin.programs.update', $prg->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" x-data="{
                loading: false,
                async submitForm() {
                    if (window.syncProgramTinyMce) {
                        window.syncProgramTinyMce(this.$el);
                    }
                    this.loading = true;
                    const formData = new FormData(this.$el);
                    try {
                        const res = await fetch(this.$el.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (res.ok && data.success) {
                            const modalEl = document.getElementById('modalEditProgram{{ $prg->id }}');
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                            window.dispatchEvent(new CustomEvent('refresh-programs'));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: data.message || 'Terjadi kesalahan.', type: 'error' } }));
                        }
                    } catch (err) {
                        this.loading = false;
                        console.error(err);
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: 'Terjadi kesalahan server.', type: 'error' } }));
                    }
                }
            }" @submit.prevent="submitForm()">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark border-0 p-3 px-4 flex-shrink-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit Master Program Kerja</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                </div>
                <div x-show="loading" class="progress rounded-0 flex-shrink-0" style="height: 3px;" x-cloak>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Seksi / Bidang Pengampu <span class="text-danger">*</span></label>
                            <select name="section_id" class="form-select" required :disabled="loading">
                                @if(auth()->user()->isSectionOfficer() && !auth()->user()->canManageAllProgramsAndEvents())
                                    @php $mySection = auth()->user()->getOfficerSection(); @endphp
                                    @if($mySection)
                                        <option value="{{ $mySection->id }}" selected>[{{ $mySection->level }}] {{ $mySection->name }} @if($mySection->pac) ({{ $mySection->pac->name }}) @endif</option>
                                    @endif
                                @else
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec->id }}" {{ $prg->section_id == $sec->id ? 'selected' : '' }}>
                                            [{{ $sec->level }}] {{ $sec->name }} @if($sec->pac) ({{ $sec->pac->name }}) @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tahun / Periode</label>
                            <input type="text" name="year" class="form-control" value="{{ $prg->year }}" placeholder="Contoh: 2026" :disabled="loading">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Program Kerja <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $prg->name }}" required :disabled="loading">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode Program</label>
                            <input type="text" name="code" class="form-control" value="{{ $prg->code }}" placeholder="Contoh: PRG-KDR-01" :disabled="loading">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Indikator Keberhasilan / Capaian <span class="text-danger">*</span></label>
                            <textarea name="indicator" class="form-control" rows="3" required :disabled="loading">{{ $prg->indicator }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Capaian <span class="text-danger">*</span></label>
                            <textarea name="target" class="form-control" rows="3" required :disabled="loading">{{ $prg->target }}</textarea>
                        </div>

                        <div class="col-12">
                            <label for="editProgramDescription{{ $prg->id }}" class="form-label fw-semibold">Deskripsi Program Kerja <span class="text-secondary small fw-normal">(Editor HTML)</span></label>
                            <textarea id="editProgramDescription{{ $prg->id }}" name="description" class="form-control program-description-editor" rows="6" :disabled="loading">{{ $prg->description }}</textarea>
                            <div class="form-text small text-muted">Mendukung pemformatan teks, daftar bernomor/bullet, tautan, serta tabel.</div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="editProgramIsActive{{ $prg->id }}" value="1" {{ $prg->is_active ? 'checked' : '' }} :disabled="loading">
                                <label class="form-check-label fw-semibold" for="editProgramIsActive{{ $prg->id }}">Status Program Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold rounded-pill px-4" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-check-lg me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Memperbarui...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade text-start" id="modalDeleteProgram{{ $prg->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" x-data="{
                loading: false,
                async submitDelete() {
                    this.loading = true;
                    const formData = new FormData(this.$refs.deleteForm);
                    try {
                        const res = await fetch(this.$refs.deleteForm.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });
                        const data = await res.json();
                        this.loading = false;
                        if (res.ok && data.success) {
                            const modalEl = document.getElementById('modalDeleteProgram{{ $prg->id }}');
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                            window.dispatchEvent(new CustomEvent('refresh-programs'));
                        } else {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: data.message || 'Terjadi kesalahan.', type: 'error' } }));
                        }
                    } catch (err) {
                        this.loading = false;
                        console.error(err);
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Gagal!', message: 'Terjadi kesalahan server.', type: 'error' } }));
                    }
                }
            }">
                <div x-show="loading" class="progress rounded-0" style="height: 3px;" x-cloak>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Hapus Program Kerja?</h6>
                    @if($prg->events_count > 0)
                        <div class="alert alert-warning small mb-3 text-start">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> Program <strong>"{{ $prg->name }}"</strong> sudah memiliki <strong>{{ $prg->events_count }} kegiatan</strong> yang terhubung, sehingga program ini <strong>tidak dapat dihapus</strong>.
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    @else
                        <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus <strong>"{{ $prg->name }}"</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                        <form x-ref="deleteForm" action="{{ route('admin.programs.destroy', $prg->id) }}" method="POST" @submit.prevent="submitDelete()">
                            @csrf
                            @method('DELETE')
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                                <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" :disabled="loading">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                                    <i class="bi bi-trash-fill me-1" x-show="!loading"></i>
                                    <span x-text="loading ? 'Menghapus...' : 'Ya, Hapus'"></span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="mt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3 pb-3">
    <div class="small text-muted">
        Menampilkan {{ $programs->firstItem() ?? 0 }} - {{ $programs->lastItem() ?? 0 }} dari total {{ $programs->total() }} Program Kerja
    </div>
    <div>
        {{ $programs->links() }}
    </div>
</div>
