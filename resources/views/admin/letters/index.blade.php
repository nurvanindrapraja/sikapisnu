@extends('layouts.admin')

@section('title', 'Konsep Surat & E-TTD')
@section('header_title', 'Konsep Surat & Penandatanganan Digital (E-TTD)')

@section('content')
<div class="container-fluid px-0" x-data="{ 
    search: '{{ addslashes(request('search', '')) }}',
    status: '{{ addslashes(request('status', '')) }}',
    letterType: '{{ addslashes(request('letter_type', '')) }}',
    loading: false,
    deletingId: null, 
    downloadingId: null,
    targetDeleteId: null,
    targetDeleteNumber: '',
    setStatus(newStatus) {
        this.status = newStatus;
        this.applyFilter();
    },
    applyFilter() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.status) params.set('status', this.status);
        if (this.letterType) params.set('letter_type', this.letterType);
        if (this.search) params.set('search', this.search);
        
        const url = '{{ route('admin.letters.index') }}' + (params.toString() ? '?' + params.toString() : '');
        this.fetchLettersData(url);
    },
    async fetchLettersData(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            const container = document.getElementById('lettersContainer');
            if (container) {
                container.innerHTML = html;
                if (window.Alpine) {
                    window.Alpine.initTree(container);
                }
            }
        } catch (e) {
            console.error('Error fetching letters data', e);
        } finally {
            this.loading = false;
        }
    },
    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchLettersData(link.href);
        }
    },
    openDeleteModal(id, number) {
        this.targetDeleteId = id;
        this.targetDeleteNumber = number;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteLetterConfirmModal'));
        modal.show();
    },
    previewModalLetterId: null,
    isPreviewLoading: false,
    openPreviewModal(id) {
        this.previewModalLetterId = id;
        this.isPreviewLoading = true;
        const modalEl = document.getElementById('previewLetterModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        const contentEl = document.getElementById('previewLetterModalContent');
        contentEl.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Memuat pratinjau...</span>
                </div>
                <div class="text-muted small mt-3">Sedang memuat pratinjau surat resmi...</div>
            </div>
        `;

        fetch('{{ url('/admin/letters') }}/' + id + '/preview', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Network error');
            return res.text();
        })
        .then(html => {
            contentEl.innerHTML = html;
            this.isPreviewLoading = false;
        })
        .catch(err => {
            console.error('Error loading letter preview:', err);
            contentEl.innerHTML = `
                <div class="modal-header border-bottom px-4 py-3 bg-light">
                    <h5 class="modal-title fw-bold text-danger mb-0">Gagal Memuat Pratinjau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> Terjadi kesalahan saat memuat dokumen pratinjau. Silakan coba kembali atau buka langsung halaman rincian.
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-2 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                    <a href="{{ url('/admin/letters') }}/${id}" class="btn btn-primary rounded-pill px-3">Buka Rincian</a>
                </div>
            `;
            this.isPreviewLoading = false;
        });
    },
    executeDelete() {
        if (this.targetDeleteId) {
            this.deletingId = this.targetDeleteId;
            const form = document.getElementById('delete-letter-' + this.targetDeleteId);
            if (form) form.submit();
        }
    }
}">

    <!-- Header & Action Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Daftar Konsep Surat & E-TTD</h4>
            <p class="text-muted small mb-0">Kelola pengajuan konsep surat berbasis template resmi dan penandatanganan digital bertahap (Sekretaris & Ketua).</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#chooseLetterTypeModal">
                <i class="bi bi-plus-circle"></i>
                <span>Ajukan Konsep Surat Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Status Tabs -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('admin.letters.index') }}" 
                   @click.prevent="setStatus('')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="!status ? 'btn-success text-white' : 'btn-outline-secondary'">
                    Semua <span class="badge ms-1 rounded-pill" :class="!status ? 'bg-white text-success' : 'bg-secondary'">{{ $statusCounts['all'] }}</span>
                </a>
                <a href="{{ route('admin.letters.index', ['status' => 'draft']) }}" 
                   @click.prevent="setStatus('draft')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="status === 'draft' ? 'btn-secondary text-white' : 'btn-outline-secondary'">
                    Draft <span class="badge ms-1 rounded-pill" :class="status === 'draft' ? 'bg-white text-secondary' : 'bg-secondary'">{{ $statusCounts['draft'] }}</span>
                </a>
                <a href="{{ route('admin.letters.index', ['status' => 'menunggu_sekretaris']) }}" 
                   @click.prevent="setStatus('menunggu_sekretaris')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="status === 'menunggu_sekretaris' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark'">
                    Menunggu Sekretaris <span class="badge bg-warning text-dark ms-1 rounded-pill">{{ $statusCounts['menunggu_sekretaris'] }}</span>
                </a>
                <a href="{{ route('admin.letters.index', ['status' => 'menunggu_ketua']) }}" 
                   @click.prevent="setStatus('menunggu_ketua')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="status === 'menunggu_ketua' ? 'btn-info text-dark' : 'btn-outline-info text-dark'">
                    Menunggu Ketua <span class="badge bg-info text-dark ms-1 rounded-pill">{{ $statusCounts['menunggu_ketua'] }}</span>
                </a>
                <a href="{{ route('admin.letters.index', ['status' => 'selesai']) }}" 
                   @click.prevent="setStatus('selesai')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="status === 'selesai' ? 'btn-success text-white' : 'btn-outline-success'">
                    Selesai & Tertandatangani <span class="badge ms-1 rounded-pill" :class="status === 'selesai' ? 'bg-white text-success' : 'bg-success'">{{ $statusCounts['selesai'] }}</span>
                </a>
                <a href="{{ route('admin.letters.index', ['status' => 'ditolak']) }}" 
                   @click.prevent="setStatus('ditolak')"
                   class="btn btn-sm rounded-pill px-3"
                   :class="status === 'ditolak' ? 'btn-danger text-white' : 'btn-outline-danger'">
                    Ditolak / Revisi <span class="badge ms-1 rounded-pill" :class="status === 'ditolak' ? 'bg-white text-danger' : 'bg-danger'">{{ $statusCounts['ditolak'] }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Live Search & Filter Jenis Surat -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Box -->
                <div class="col-12 col-md-8 col-lg-8">
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted ps-3">
                                <i class="bi bi-search" x-show="!loading"></i>
                                <span class="spinner-border spinner-border-sm text-success" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            </span>
                            <input type="text" 
                                   x-model="search" 
                                   @input.debounce.300ms="applyFilter()" 
                                   class="form-control bg-light border-start-0 py-2 ps-2 pe-5" 
                                   placeholder="Cari nomor surat, perihal, tujuan, kegiatan, kode surat...">
                        </div>
                        <!-- Clear Button -->
                        <button type="button" 
                                class="btn btn-link text-muted position-absolute end-0 top-50 translate-middle-y me-2 p-1 text-decoration-none" 
                                x-show="search" 
                                @click="search = ''; applyFilter()" 
                                x-cloak 
                                title="Hapus Pencarian" 
                                style="z-index: 5;">
                            <i class="bi bi-x-circle-fill text-secondary fs-6"></i>
                        </button>
                    </div>
                </div>

                <!-- Dropdown Filter Jenis Surat -->
                <div class="col-12 col-md-4 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted ps-3 border-end-0">
                            <i class="bi bi-tag-fill text-secondary"></i>
                        </span>
                        <select class="form-select bg-light border-start-0 py-2" 
                                x-model="letterType" 
                                @change="applyFilter()"
                                aria-label="Filter Jenis Surat">
                            <option value="">Semua Jenis Surat</option>
                            <option value="surat_biasa">Surat Dinas / Biasa</option>
                            <option value="surat_mandat">Surat Mandat</option>
                            <option value="surat_rekomendasi">Surat Rekomendasi</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Letters List (Desktop Table + Mobile Card View) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 position-relative">
        <!-- Loading Overlay -->
        <template x-if="loading">
            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75" style="z-index: 10;">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Memuat data...</span>
                </div>
            </div>
        </template>

        <div id="lettersContainer">
            @include('admin.letters.partials.letter_list')
        </div>
    </div>

    <!-- Modal Pemilihan Jenis Surat -->
    <div class="modal fade" id="chooseLetterTypeModal" tabindex="-1" aria-labelledby="chooseLetterTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-bottom py-3 px-4 bg-light">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="chooseLetterTypeModalLabel">
                            <i class="bi bi-file-earmark-plus text-success me-2"></i>Pilih Jenis Surat Yang Akan Dibuat
                        </h5>
                        <p class="text-muted small mb-0">Pilih format template resmi PC ISNU Kota Surabaya yang sesuai dengan kebutuhan surat Anda.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Pilihan 1: Surat Dinas / Biasa -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded-4 p-3 hover-shadow transition-all d-flex flex-column justify-content-between text-start" style="border-color: #dee2e6;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="p-3 bg-success-subtle text-success rounded-3">
                                            <i class="bi bi-file-earmark-text-fill fs-3"></i>
                                        </div>
                                        <span class="badge bg-success-subtle text-success small rounded-pill">Template Baku</span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-2">Surat Dinas / Biasa</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                                        Format baku surat keluar kedinasan PC ISNU: surat undangan, permohonan narasumber, audiensi, atau pemberitahuan dengan kop resmi dan tujuan.
                                    </p>
                                </div>
                                <a href="{{ route('admin.letters.create', ['type' => 'surat_biasa']) }}" class="btn btn-outline-success rounded-pill w-100 fw-semibold btn-sm py-2">
                                    Pilih Surat Dinas <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Pilihan 2: Surat Mandat -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded-4 p-3 hover-shadow transition-all d-flex flex-column justify-content-between text-start" style="border-color: #dee2e6;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="p-3 bg-primary-subtle text-primary rounded-3">
                                            <i class="bi bi-award-fill fs-3"></i>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary small rounded-pill">Mandat Resmi</span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-2">Surat Mandat</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                                        Surat penugasan delegasi resmi PC ISNU untuk mengikutsertakan anggota/pengurus pada kegiatan, konferensi, atau musyawarah organisasi.
                                    </p>
                                </div>
                                <a href="{{ route('admin.letters.create', ['type' => 'surat_mandat']) }}" class="btn btn-outline-primary rounded-pill w-100 fw-semibold btn-sm py-2">
                                    Pilih Surat Mandat <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Pilihan 3: Surat Rekomendasi -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded-4 p-3 hover-shadow transition-all d-flex flex-column justify-content-between text-start" style="border-color: #dee2e6;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3">
                                            <i class="bi bi-patch-check-fill fs-3"></i>
                                        </div>
                                        <span class="badge bg-warning-subtle text-warning-emphasis small rounded-pill">Rekomendasi</span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-2">Surat Rekomendasi</h6>
                                    <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                                        Menerbitkan surat rekomendasi resmi organisasi untuk anggota/pengurus bagi keperluan beasiswa, pelantikan kepengurusan, studi lanjut, atau pekerjaan.
                                    </p>
                                </div>
                                <a href="{{ route('admin.letters.create', ['type' => 'surat_rekomendasi']) }}" class="btn btn-outline-warning rounded-pill w-100 fw-semibold btn-sm py-2">
                                    Pilih Rekomendasi <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Konsep Surat -->
    <div class="modal fade" id="deleteLetterConfirmModal" tabindex="-1" aria-labelledby="deleteLetterConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-center position-relative">
                    <div class="p-3 bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-trash3-fill fs-2"></i>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <h5 class="modal-title fw-bold text-dark mb-2" id="deleteLetterConfirmModalLabel">Hapus Konsep Surat?</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus konsep surat nomor <strong class="text-dark" x-text="targetDeleteNumber"></strong>?
                    </p>
                    <p class="text-danger small mt-2 mb-0 fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Tindakan ini permanen dan tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal" :disabled="deletingId !== null">
                        Batal
                    </button>
                    <button type="button" class="btn btn-danger rounded-pill flex-fill py-2 fw-semibold d-inline-flex align-items-center justify-content-center" :disabled="deletingId !== null" @click="executeDelete()">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="deletingId !== null" x-cloak></span>
                        <i class="bi bi-trash me-1" x-show="deletingId === null"></i>
                        <span x-text="deletingId !== null ? 'Menghapus...' : 'Ya, Hapus'">Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview Konsep Surat -->
    <div class="modal fade" id="previewLetterModal" tabindex="-1" aria-labelledby="previewLetterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden" id="previewLetterModalContent">
                <!-- Content will be loaded dynamically via AJAX -->
            </div>
        </div>
    </div>

</div>
@endsection
