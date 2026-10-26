<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Nama Kelompok Kerja</th>
                <th>Tingkat Organisasi</th>
                <th>Wilayah PAC</th>
                <th>Anggota (Pengurus Seksi)</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th class="text-end" style="width: 170px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workgroups as $index => $wg)
                <tr>
                    <td>{{ $workgroups->firstItem() + $index }}</td>
                    <td>
                        <strong class="d-block text-dark">{{ $wg->name }}</strong>
                        @if($wg->code)
                            <code class="small text-muted">{{ $wg->code }}</code>
                        @endif
                    </td>
                    <td>
                        @if($wg->level === 'PC ISNU')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                <i class="bi bi-building me-1"></i> PC ISNU Kota Surabaya
                            </span>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill">
                                <i class="bi bi-geo-alt me-1"></i> PAC ISNU
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($wg->level === 'PAC ISNU')
                            <span class="fw-semibold text-dark">{{ $wg->pac->name ?? '-' }}</span>
                        @else
                            <span class="text-muted small">Semua Wilayah PC</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.workgroups.show', $wg->id) }}" class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1">
                            <i class="bi bi-people-fill"></i>
                            <span class="fw-bold">{{ $wg->members_count }}</span> Pengurus
                        </a>
                    </td>
                    <td>
                        @if($wg->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                <i class="bi bi-check-circle me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill">
                                <i class="bi bi-dash-circle me-1"></i> Non-Aktif
                            </span>
                        @endif
                    </td>
                    <td class="text-secondary small">
                        {{ Str::limit($wg->description, 50) ?: '-' }}
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('admin.workgroups.show', $wg->id) }}" 
                               class="btn btn-sm btn-success rounded-pill px-2.5 py-1 d-flex align-items-center gap-1 shadow-sm" 
                               title="Kelola Anggota Pokja">
                                <i class="bi bi-person-gear"></i> <span class="d-none d-lg-inline small">Kelola</span>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                    style="width: 32px; height: 32px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEditWorkgroup{{ $wg->id }}" 
                                    title="Edit Pokja">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                    style="width: 32px; height: 32px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalDeleteWorkgroup{{ $wg->id }}" 
                                    title="Hapus Pokja">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>

                        <!-- Modal Edit Kelompok Kerja -->
                        <div class="modal fade text-start" id="modalEditWorkgroup{{ $wg->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <form action="{{ route('admin.workgroups.update', $wg->id) }}" method="POST" x-data="{
                                        level: '{{ $wg->level }}',
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
                                                    const modalEl = document.getElementById('modalEditWorkgroup{{ $wg->id }}');
                                                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                                    modal.hide();
                                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                                                    window.dispatchEvent(new CustomEvent('refresh-workgroups'));
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
                                            <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit Master Kelompok Kerja</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                                        </div>
                                        <div x-show="loading" class="progress rounded-0" style="height: 3px;" x-cloak>
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 100%"></div>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Tingkat Organisasi <span class="text-danger">*</span></label>
                                                <select name="level" class="form-select" x-model="level" required :disabled="loading">
                                                    <option value="PC ISNU">PC ISNU Kota Surabaya (Tingkat Kota)</option>
                                                    <option value="PAC ISNU">PAC ISNU (Tingkat Kecamatan)</option>
                                                </select>
                                            </div>

                                            <div class="mb-3" x-show="level === 'PAC ISNU'" x-cloak>
                                                <label class="form-label fw-semibold">Pilih PAC ISNU <span class="text-danger">*</span></label>
                                                <select name="pac_id" class="form-select" :required="level === 'PAC ISNU'" :disabled="loading">
                                                    <option value="">-- Pilih PAC ISNU --</option>
                                                    @foreach($pacs as $pac)
                                                        <option value="{{ $pac->id }}" {{ $wg->pac_id == $pac->id ? 'selected' : '' }}>
                                                            {{ $pac->name }} ({{ $pac->kecamatan }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Nama Kelompok Kerja (Pokja) <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ $wg->name }}" placeholder="Contoh: Kelompok Kerja Transformasi Digital" required :disabled="loading">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Kode Pokja <span class="text-secondary small">(Opsional)</span></label>
                                                <input type="text" name="code" class="form-control" value="{{ $wg->code }}" placeholder="Contoh: PKJ-DIGITAL" :disabled="loading">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Deskripsi / Ruang Lingkup Tugas</label>
                                                <textarea name="description" class="form-control" rows="3" placeholder="Tuliskan tujuan atau ruang lingkup pokok..." :disabled="loading">{{ $wg->description }}</textarea>
                                            </div>

                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="editIsActive{{ $wg->id }}" value="1" {{ $wg->is_active ? 'checked' : '' }} :disabled="loading">
                                                <label class="form-check-label fw-semibold" for="editIsActive{{ $wg->id }}">Status Pokja Aktif</label>
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

                        <!-- Modal Hapus Kelompok Kerja -->
                        <div class="modal fade text-start" id="modalDeleteWorkgroup{{ $wg->id }}" tabindex="-1" aria-hidden="true">
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
                                                const modalEl = document.getElementById('modalDeleteWorkgroup{{ $wg->id }}');
                                                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                                modal.hide();
                                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: 'Berhasil!', message: data.message, type: 'success' } }));
                                                window.dispatchEvent(new CustomEvent('refresh-workgroups'));
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
                                        <h6 class="fw-bold text-dark mb-2">Hapus Kelompok Kerja?</h6>
                                        <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus <strong>"{{ $wg->name }}"</strong>? Status kepengurusan di Seksi untuk anggota pokja ini tidak akan terhapus.</p>
                                        <form x-ref="deleteForm" action="{{ route('admin.workgroups.destroy', $wg->id) }}" method="POST" @submit.prevent="submitDelete()">
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-person-workspace fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada data Kelompok Kerja yang ditambahkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-between align-items-center px-3">
    <div class="small text-muted">
        Menampilkan {{ $workgroups->firstItem() ?? 0 }} - {{ $workgroups->lastItem() ?? 0 }} dari total {{ $workgroups->total() }} Kelompok Kerja
    </div>
    <div>
        {{ $workgroups->links() }}
    </div>
</div>
