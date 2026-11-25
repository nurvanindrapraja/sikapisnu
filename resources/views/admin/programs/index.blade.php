@extends('layouts.admin')

@section('title', 'Master Program Kerja Seksi - SIKAP ISNU')
@section('header_title', 'Master Program Kerja')

@section('styles')
<style>
    @media (min-width: 1200px) {
        .modal-program-wide {
            max-width: 1140px;
        }
    }
    .tox-tinymce {
        border-radius: 0.75rem !important;
        border-color: #dee2e6 !important;
    }
    .tox-tinymce:focus-within {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }
    .program-html-content p:last-child,
    .program-html-content ul:last-child,
    .program-html-content ol:last-child {
        margin-bottom: 0;
    }
    .program-html-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0.75rem;
    }
    .program-html-content table th,
    .program-html-content table td {
        border: 1px solid #dee2e6;
        padding: 0.4rem 0.6rem;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-start align-items-md-center gap-2 mb-4">
    <div class="min-w-0">
        <h4 class="fw-bold text-dark m-0">
            <i class="bi bi-card-checklist text-success me-2"></i> Master Program Kerja Seksi
        </h4>
        <p class="text-secondary small m-0 mt-1">
            Kelola program kerja masing-masing Seksi / Bidang PC & PAC ISNU lengkap dengan indikator dan target capaian
        </p>
    </div>
    @if(auth()->user()->canManageAnyProgramOrEvent())
    <button type="button"
            class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm d-inline-flex align-items-center justify-content-center btn-mobile-icon"
            data-bs-toggle="modal"
            data-bs-target="#modalAddProgram"
            title="Tambah Program Baru"
            aria-label="Tambah Program Baru">
        <i class="bi bi-plus-lg"></i>
        <span class="d-none d-md-inline ms-1">Tambah Program Baru</span>
    </button>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Metrik Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-journal-bookmark-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Program</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Program PC ISNU</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pc'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-geo-alt-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Program PAC ISNU</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['pac'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-calendar-check-fill fs-5"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Kegiatan Terhubung</span>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['events_count'] }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Filter & Table -->
@php
    $filterOwners = $sections->map(fn($s) => [
        'key' => 'section:' . $s->id,
        'type' => 'section',
        'name' => $s->name,
        'level' => $s->level,
        'pac_id' => $s->pac_id ? (string) $s->pac_id : '',
        'pac_name' => $s->pac?->display_name ?? $s->pac?->name ?? '',
    ])->concat($workgroups->map(fn($w) => [
        'key' => 'workgroup:' . $w->id,
        'type' => 'workgroup',
        'name' => $w->name,
        'level' => $w->level,
        'pac_id' => $w->pac_id ? (string) $w->pac_id : '',
        'pac_name' => $w->pac?->display_name ?? $w->pac?->name ?? '',
    ]))->values();
@endphp
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    search: '{{ request('search') }}',
    level: '{{ request('level') }}',
    pac_id: '{{ request('pac_id') }}',
    owner_key: '{{ request('owner_key') ?: (request('section_id') ? 'section:' . request('section_id') : (request('workgroup_id') ? 'workgroup:' . request('workgroup_id') : '')) }}',
    status: '{{ request('status') }}',
    loading: false,
    ownersData: {{ Js::from($filterOwners) }},
    get filteredOwners() {
        return this.ownersData.filter(o => {
            if (this.level && o.level !== this.level) return false;
            if (this.level === 'PAC ISNU' && this.pac_id && o.pac_id !== String(this.pac_id)) return false;
            return true;
        });
    },
    formatOwnerLabel(o) {
        const prefix = o.type === 'workgroup' ? 'Pokja' : 'Seksi';
        const scope = o.pac_name ? o.pac_name : o.level;
        return `[${prefix} - ${scope}] ${o.name}`;
    },
    onLevelChange() {
        if (this.level !== 'PAC ISNU') {
            this.pac_id = '';
        }
        this.owner_key = '';
        this.fetchPrograms();
    },
    onPacChange() {
        this.owner_key = '';
        this.fetchPrograms();
    },
    fetchPrograms() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.level) params.append('level', this.level);
        if (this.level === 'PAC ISNU' && this.pac_id) params.append('pac_id', this.pac_id);
        if (this.owner_key) params.append('owner_key', this.owner_key);
        if (this.status) params.append('status', this.status);

        fetch(`{{ route('admin.programs.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('programListContainer');
            if (window.destroyProgramTinyMceIn) {
                window.destroyProgramTinyMceIn(container);
            }
            container.innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    }
}" @refresh-programs.window="fetchPrograms()">
    <div class="card-header bg-white border-0 p-3 p-md-4">
        <div class="row g-2 align-items-center">
            <!-- 1. Pencarian Nama Program -->
            <div :class="level === 'PAC ISNU' ? 'col-md-3' : 'col-md-4'">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="search" @input.debounce.400ms="fetchPrograms()" class="form-control bg-light border-start-0" placeholder="Cari nama program / indikator / target...">
                </div>
            </div>

            <!-- 2. Pilih Tingkatan (PC / PAC) & Daftar PAC -->
            @if(auth()->user()->isOfficerPac() && !(auth()->user()->isSuperAdmin() || auth()->user()->isAdminKota() || auth()->user()->role === 'admin'))
                <div class="col-md-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle p-2 px-3 rounded-pill w-100 text-truncate">
                        <i class="bi bi-geo-alt-fill me-1"></i> {{ auth()->user()->getManagedPac()?->display_name ?? 'PAC ISNU' }}
                    </span>
                </div>
            @else
                <div class="col-md-2">
                    <select x-model="level" @change="onLevelChange()" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Tingkat --</option>
                        <option value="PC ISNU">PC ISNU</option>
                        <option value="PAC ISNU">PAC ISNU</option>
                    </select>
                </div>

                <div class="col-md-2" x-show="level === 'PAC ISNU'" x-cloak>
                    <select x-model="pac_id" @change="onPacChange()" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua PAC ISNU --</option>
                        @foreach($pacs as $pac)
                            <option value="{{ $pac->id }}">{{ $pac->display_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- 3. Pilih List Seksi / Pokja (Terfilter oleh Tingkatan & PAC) -->
            <div :class="level === 'PAC ISNU' ? 'col-md-2' : 'col-md-3'">
                <select x-model="owner_key" @change="fetchPrograms()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Seksi / Pokja --</option>
                    <template x-for="o in filteredOwners" :key="o.key">
                        <option :value="o.key" x-text="formatOwnerLabel(o)"></option>
                    </template>
                </select>
            </div>

            <!-- 4. Status -->
            <div class="col-md-2">
                <select x-model="status" @change="fetchPrograms()" class="form-select form-select-sm bg-light">
                    <option value="">-- Semua Status --</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-Aktif</option>
                </select>
            </div>

            <!-- 5. Reset -->
            <div class="col-md-1 text-end">
                <button type="button" @click="search=''; level=''; pac_id=''; owner_key=''; status=''; fetchPrograms()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div x-show="loading" class="progress mt-3" style="height: 3px;" x-cloak>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>

    <div class="card-body p-0" id="programListContainer">
        @include('admin.programs.partials.program_list')
    </div>
</div>

<!-- Modal Tambah Program Kerja Baru -->
<div class="modal fade" id="modalAddProgram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-program-wide">
        <form action="{{ route('admin.programs.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" x-data="{
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
                        const modalEl = document.getElementById('modalAddProgram');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        if (window.resetProgramTinyMce) {
                            window.resetProgramTinyMce(this.$el);
                        }
                        this.$el.reset();
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
            <div class="modal-header bg-isnu text-white border-0 p-3 px-4 flex-shrink-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Tambah Master Program Kerja Seksi / Pokja</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
            </div>
            <div x-show="loading" class="progress rounded-0 flex-shrink-0" style="height: 3px;" x-cloak>
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    @php
                        $singleOwnerOption = ($formSections->count() + $formWorkgroups->count()) === 1;
                    @endphp
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Pilih Seksi / Bidang / Pokja Pengampu <span class="text-danger">*</span></label>
                        <select name="owner_key" class="form-select" required :disabled="loading">
                            @if(!$singleOwnerOption)
                                <option value="">-- Pilih Seksi / Bidang / Pokja --</option>
                            @endif
                            @if($formSections->isNotEmpty())
                                <optgroup label="Seksi / Bidang">
                                    @foreach($formSections as $sec)
                                        <option value="section:{{ $sec->id }}" {{ $singleOwnerOption ? 'selected' : '' }}>
                                            [{{ $sec->level }}] {{ $sec->name }} @if($sec->pac) ({{ $sec->pac->display_name }}) @endif
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($formWorkgroups->isNotEmpty())
                                <optgroup label="Kelompok Kerja (Pokja)">
                                    @foreach($formWorkgroups as $wg)
                                        <option value="workgroup:{{ $wg->id }}" {{ $singleOwnerOption && $formSections->isEmpty() ? 'selected' : '' }}>
                                            [Pokja {{ $wg->level }}] {{ $wg->name }} @if($wg->pac) ({{ $wg->pac->display_name }}) @endif
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tahun / Periode <span class="text-secondary small">(Opsional)</span></label>
                        <input type="text" name="year" class="form-control" value="{{ date('Y') }}" placeholder="Contoh: 2026 atau 2026-2030" :disabled="loading">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nama Program Kerja <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Program Penguatan Ideologi & Pelatihan Kader Intelektual" required :disabled="loading">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kode Program <span class="text-secondary small">(Opsional)</span></label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: PRG-KDR-01" :disabled="loading">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Indikator Keberhasilan / Capaian <span class="text-danger">*</span></label>
                        <textarea name="indicator" class="form-control" rows="3" placeholder="Contoh: Terlaksananya pembinaan ideologi kader sarjana dan peningkatan pemahaman ASWAJA..." required :disabled="loading"></textarea>
                        <div class="form-text small text-muted">Kriteria atau parameter untuk mengukur ketercapaian program.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Capaian <span class="text-danger">*</span></label>
                        <textarea name="target" class="form-control" rows="3" placeholder="Contoh: 100 Kader sarjana tersertifikasi dalam 2 angkatan pelatihan..." required :disabled="loading"></textarea>
                        <div class="form-text small text-muted">Ukuran hasil kuantitatif maupun kualitatif yang diharapkan.</div>
                    </div>

                    <div class="col-12">
                        <label for="addProgramDescription" class="form-label fw-semibold">Deskripsi Program Kerja <span class="text-secondary small fw-normal">(Editor HTML)</span></label>
                        <textarea id="addProgramDescription" name="description" class="form-control program-description-editor" rows="6" placeholder="Tuliskan latar belakang, tujuan, tahapan, atau rincian program kerja..." :disabled="loading"></textarea>
                        <div class="form-text small text-muted">Mendukung pemformatan teks, daftar bernomor/bullet, tautan, serta tabel.</div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="addProgramIsActive" value="1" checked :disabled="loading">
                            <label class="form-check-label fw-semibold" for="addProgramIsActive">Status Program Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 py-3 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <i class="bi bi-check-lg me-1" x-show="!loading"></i>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Program'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    // Izinkan interaksi dialog TinyMCE (Link, Table, Source Code) di dalam Bootstrap 5 Modal
    document.addEventListener('focusin', function (e) {
        if (e.target.closest('.tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root') !== null) {
            e.stopImmediatePropagation();
        }
    });

    function initProgramEditor(textarea) {
        if (!textarea || !textarea.id || typeof window.tinymce === 'undefined') {
            return;
        }

        const existing = window.tinymce.get(textarea.id);
        if (existing) {
            existing.remove();
        }

        window.tinymce.init({
            selector: '#' + CSS.escape(textarea.id),
            license_key: 'gpl',
            menubar: false,
            branding: false,
            promotion: false,
            height: 320,
            min_height: 260,
            plugins: 'lists link table code wordcount',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat | code',
            toolbar_mode: 'sliding',
            statusbar: true,
            resize: true,
            content_style: 'body { font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.65; color: #212529; } p { margin: 0 0 0.6rem; } ul, ol { padding-left: 1.4rem; margin: 0 0 0.6rem; } table { border-collapse: collapse; width: 100%; } table td, table th { border: 1px solid #ced4da; padding: 6px 8px; }',
            setup: function (editor) {
                editor.on('change keyup blur', function () {
                    editor.save();
                });
            }
        });
    }

    window.syncProgramTinyMce = function (scopeEl) {
        if (typeof window.tinymce === 'undefined' || !scopeEl) {
            return;
        }
        scopeEl.querySelectorAll('textarea.program-description-editor').forEach(function (ta) {
            const ed = ta.id ? window.tinymce.get(ta.id) : null;
            if (ed) {
                ed.save();
            }
        });
    };

    window.resetProgramTinyMce = function (scopeEl) {
        if (typeof window.tinymce === 'undefined' || !scopeEl) {
            return;
        }
        scopeEl.querySelectorAll('textarea.program-description-editor').forEach(function (ta) {
            const ed = ta.id ? window.tinymce.get(ta.id) : null;
            if (ed) {
                ed.setContent('');
                ed.save();
            }
        });
    };

    window.destroyProgramTinyMceIn = function (scopeEl) {
        if (typeof window.tinymce === 'undefined' || !scopeEl) {
            return;
        }
        scopeEl.querySelectorAll('textarea.program-description-editor').forEach(function (ta) {
            const ed = ta.id ? window.tinymce.get(ta.id) : null;
            if (ed) {
                ed.remove();
            }
        });
    };

    // Lazy init TinyMCE ketika modal Tambah/Edit Program dibuka, dan bersihkan saat modal ditutup
    document.addEventListener('shown.bs.modal', function (event) {
        const modalEl = event.target;
        if (!modalEl) {
            return;
        }
        modalEl.querySelectorAll('textarea.program-description-editor').forEach(function (ta) {
            initProgramEditor(ta);
        });
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        const modalEl = event.target;
        if (!modalEl) {
            return;
        }
        window.destroyProgramTinyMceIn(modalEl);
    });
})();
</script>
@endsection
