@extends('layouts.admin')

@section('title', 'Kelola Pengurus ISNU')
@section('header_title', 'Kelola Pengurus ISNU')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4" x-data="{
    loading: false,
    search: '{{ request('search') }}',
    level: '{{ request('level') }}',
    pacId: '{{ request('pac_id') }}',
    positionTitle: '{{ request('position_title') }}',

    // Modal Form Penetapan State
    promoteModal: null,
    promoteMemberId: '',
    promoteMemberName: '',
    promoteLevel: 'PC ISNU',
    promotePacId: '',

    openPromoteModal() {
        if (!this.promoteModal) {
            this.promoteModal = new bootstrap.Modal(document.getElementById('modalPromoteOfficer'));
        }
        this.promoteModal.show();
    },

    async fetchOfficersData(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('officersContainer').innerHTML = html;
        } catch (e) {
            console.error('Error fetching officers data', e);
        } finally {
            this.loading = false;
        }
    },

    applyFilter() {
        const params = new URLSearchParams();
        if (this.search) params.set('search', this.search);
        if (this.level) params.set('level', this.level);
        if (this.level === 'PAC ISNU' && this.pacId) params.set('pac_id', this.pacId);
        if (this.positionTitle) params.set('position_title', this.positionTitle);
        
        const url = '{{ route('admin.pengurus.index') }}?' + params.toString();
        this.fetchOfficersData(url);
    },

    resetFilter() {
        this.search = '';
        this.level = '';
        this.pacId = '';
        this.positionTitle = '';
        this.fetchOfficersData('{{ route('admin.pengurus.index') }}');
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchOfficersData(link.href);
        }
    }
}">
    <div class="card-body p-4">
        <!-- Top Action Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 gap-2">
            <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-award-fill text-warning fs-4"></i>
                <span class="d-none d-sm-inline">Daftar Pengurus ISNU Kota Surabaya</span>
                <span class="d-inline d-sm-none">Pengurus ISNU</span>
            </h5>

            <!-- Responsive Button: Desktop text + icon, Mobile icon only -->
            <button type="button" @click="openPromoteModal()" class="btn btn-success rounded-pill fw-bold d-flex align-items-center gap-1 shadow-sm px-3 py-2">
                <i class="bi bi-plus-lg fs-6"></i>
                <span class="d-none d-md-inline">Tetapkan Pengurus Baru</span>
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <form @submit.prevent="applyFilter()" class="mb-4 bg-light p-3 rounded-4 border">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Cari Nama / NIK</label>
                    <input type="text" x-model="search" @input.debounce.300ms="applyFilter()" class="form-control form-control-sm" placeholder="Nama atau no. anggota...">
                </div>

                <!-- 4.3 Filter Tingkat Kepengurusan -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Tingkat Kepengurusan</label>
                    <select x-model="level" @change="applyFilter()" class="form-select form-select-sm">
                        <option value="">-- Semua Tingkat --</option>
                        <option value="PC ISNU">PC ISNU (Kota)</option>
                        <option value="PAC ISNU">PAC ISNU (Kecamatan)</option>
                    </select>
                </div>

                <!-- 4.3 Filter Lokasi PAC (Tampil jika PAC ISNU dipilih) -->
                <div class="col-md-3" x-show="level === 'PAC ISNU'" x-transition>
                    <label class="form-label fw-bold small text-muted">Lokasi PAC ISNU</label>
                    <select x-model="pacId" @change="applyFilter()" class="form-select form-select-sm">
                        <option value="">-- Semua PAC --</option>
                        @foreach($pacs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4.3 Filter Jabatan Pengurus -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Jabatan Pengurus</label>
                    <input type="text" x-model="positionTitle" @input.debounce.300ms="applyFilter()" class="form-control form-control-sm" placeholder="Cari Ketua, Sekretaris...">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                <button type="button" @click="resetFilter()" class="btn btn-sm btn-outline-secondary rounded-pill px-3" x-show="search || level || pacId || positionTitle">
                    <i class="bi bi-x-circle me-1"></i> Reset Filter
                </button>
                <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 fw-bold">
                    <i class="bi bi-search me-1"></i> Terpilih
                </button>
            </div>
        </form>

        <!-- Progress Bar Indicator -->
        <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
        </div>

        <!-- AJAX Container for Desktop Table & Mobile Card View -->
        <div id="officersContainer" 
             @click="handlePagination($event)" 
             :class="{ 'opacity-50 pointer-events-none': loading }" 
             style="transition: opacity 0.2s ease;">
            @include('admin.pengurus.partials.officer_list')
        </div>
    </div>

    <!-- Modal Form Penetapan Pengurus ISNU (4.7) -->
    <div class="modal fade" id="modalPromoteOfficer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form id="formPromoteOfficer" action="#" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4" x-data="{
                selectedMemberId: '',
                levelChoice: 'PC ISNU',
                updateAction() {
                    if (this.selectedMemberId) {
                        $el.action = '{{ url('admin/pengurus') }}/' + this.selectedMemberId + '/promote';
                    }
                }
            }" @submit="updateAction()">
                @csrf
                <div class="modal-header bg-success text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-award-fill me-2"></i> Form Penetapan Pengurus ISNU
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Pilih Anggota -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Anggota ISNU <span class="text-danger">*</span></label>
                        <select name="member_id" class="form-select" x-model="selectedMemberId" required>
                            <option value="">-- Pilih Anggota Terverifikasi --</option>
                            @foreach($eligibleMembers as $em)
                                <option value="{{ $em->id }}">{{ $em->full_name }} ({{ $em->member_number ?? 'NIK: '.$em->nik }}) - {{ $em->kecamatan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <!-- 4.7.1 & 4.7.2 Tingkat Kepengurusan choices: PC ISNU & PAC ISNU -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tingkat Kepengurusan <span class="text-danger">*</span></label>
                            <select name="level" class="form-select" x-model="levelChoice" required>
                                <option value="PC ISNU">PC ISNU (Kota Surabaya)</option>
                                <option value="PAC ISNU">PAC ISNU (Kecamatan)</option>
                            </select>
                        </div>

                        <!-- 4.7.2 PAC List dropdown if PAC ISNU selected -->
                        <div class="col-md-6" x-show="levelChoice === 'PAC ISNU'" x-transition>
                            <label class="form-label fw-bold small">List PAC ISNU <span class="text-danger">*</span></label>
                            <select name="pac_id" class="form-select" :required="levelChoice === 'PAC ISNU'">
                                <option value="">-- Pilih PAC ISNU --</option>
                                @foreach($pacs as $p)
                                    <option value="{{ $p->id }}">PAC {{ $p->name }} ({{ $p->kecamatan }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jabatan Pengurus <span class="text-danger">*</span></label>
                            <input type="text" name="position_title" class="form-control" placeholder="Contoh: Ketua III / Sekretaris / Wakil Ketua" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Periode Kepengurusan <span class="text-danger">*</span></label>
                            <input type="text" name="period" class="form-control" placeholder="Contoh: 2026 - 2030" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nomor SK (Opsional)</label>
                            <input type="text" name="sk_number" class="form-control" placeholder="Contoh: 045/SK/PC-ISNU/2026">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">File SK PDF / Gambar (Opsional)</label>
                            <input type="file" name="sk_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>
                <!-- 4.7.4 Buttons: Batal & Simpan full width on mobile -->
                <div class="modal-footer border-0 bg-light rounded-bottom-4 flex-column flex-sm-row justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 w-100 w-sm-auto" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4 w-100 w-sm-auto" :disabled="!selectedMemberId">
                        <i class="bi bi-save me-1"></i> Simpan Penetapan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembatalan Status Pengurus -->
    <div class="modal fade" id="modalDemoteOfficer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 text-center">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Pembatalan Pengurus
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-person-x-fill text-danger display-3 d-block mb-3"></i>
                    <p class="fs-6 text-dark mb-1">Apakah Anda yakin ingin membatalkan status pengurus untuk:</p>
                    <h6 class="fw-bold text-danger mb-3" id="demoteMemberName"></h6>
                    <p class="small text-muted mb-0">Anggota ini akan kembali ke status <strong>Anggota Terverifikasi</strong> biasa dan jenis Kartu Digital kembali ke Kartu Anggota.</p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <form id="formDemoteOfficer" action="" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-x-circle me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Memproses...' : 'Ya, Batalkan Status Pengurus'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openDemoteModal(url, name) {
        document.getElementById('formDemoteOfficer').action = url;
        document.getElementById('demoteMemberName').textContent = name;
        const modalEl = document.getElementById('modalDemoteOfficer');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
    </script>
</div>
@endsection
