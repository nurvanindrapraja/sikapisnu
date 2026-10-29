<div class="table-responsive">
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
                <tr>
                    <td>{{ $programs->firstItem() + $index }}</td>
                    <td>
                        <strong class="d-block text-dark">{{ $prg->name }}</strong>
                        <div class="d-flex align-items-center gap-1.5 mt-0.5">
                            @if($prg->code)
                                <code class="small text-muted">{{ $prg->code }}</code>
                            @endif
                            @if($prg->year)
                                <span class="badge bg-light text-secondary border small px-2 py-0.5 rounded-pill">{{ $prg->year }}</span>
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
                        @php
                            $canManageProgram = auth()->user()->canManageSectionProgramOrEvent($prg->section_id);
                            $hasEvents = $prg->events_count > 0;
                        @endphp
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

                        <!-- Modal Edit Program -->
                        <div class="modal fade text-start" id="modalEditProgram{{ $prg->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <form action="{{ route('admin.programs.update', $prg->id) }}" method="POST" x-data="{
                                        loading: false,
                                        async submitForm() {
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
                                        <div class="modal-header bg-warning text-dark border-0 p-3">
                                            <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit Master Program Kerja</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                                        </div>
                                        <div x-show="loading" class="progress rounded-0" style="height: 3px;" x-cloak>
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 100%"></div>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label fw-semibold">Seksi / Bidang Pengampu <span class="text-danger">*</span></label>
                                                    <select name="section_id" class="form-select" required :disabled="loading">
                                                        @if(auth()->user()->isWakilPengurus() && !auth()->user()->canManageAllProgramsAndEvents())
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
                                                    <label class="form-label fw-semibold">Deskripsi Program Kerja</label>
                                                    <textarea name="description" class="form-control" rows="2" :disabled="loading">{{ $prg->description }}</textarea>
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-check form-switch mt-1">
                                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="editProgramIsActive{{ $prg->id }}" value="1" {{ $prg->is_active ? 'checked' : '' }} :disabled="loading">
                                                        <label class="form-check-label fw-semibold" for="editProgramIsActive{{ $prg->id }}">Status Program Aktif</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light border-0 px-4 py-3">
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
                        </div>

                        <!-- Modal Hapus Program -->
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

<div class="mt-4 d-flex justify-content-between align-items-center px-3">
    <div class="small text-muted">
        Menampilkan {{ $programs->firstItem() ?? 0 }} - {{ $programs->lastItem() ?? 0 }} dari total {{ $programs->total() }} Program Kerja
    </div>
    <div>
        {{ $programs->links() }}
    </div>
</div>
