@extends('layouts.admin')

@section('title', 'Master Lokasi Berjenjang')
@section('header_title', 'Master Lokasi Berjenjang')

@section('content')
<div x-data="{
    loading: false,
    search: '{{ request('search') }}',

    provinces: [],
    cities: [],
    kecamatans: [],
    kelurahans: [],

    selectedProvCode: '',
    selectedProvName: '',

    selectedCityCode: '',
    selectedCityName: '',

    selectedKecCode: '',
    selectedKecName: '',

    // Add Modal State
    addModal: null,
    addLevel: 'province',
    addParentCode: '',
    addCode: '',
    addName: '',

    openAddModal(level, parentCode = '') {
        this.addLevel = level;
        this.addParentCode = parentCode;
        this.addCode = '';
        this.addName = '';

        // Auto-generate suggested code pattern
        if (level === 'province') {
            this.addCode = '99';
        } else if (level === 'city' && parentCode) {
            this.addCode = parentCode + '.99';
        } else if (level === 'kecamatan' && parentCode) {
            this.addCode = parentCode + '.99';
        } else if (level === 'kelurahan' && parentCode) {
            this.addCode = parentCode + '.9999';
        }

        if (!this.addModal) {
            this.addModal = new bootstrap.Modal(document.getElementById('modalAddLocation'));
        }
        this.addModal.show();
    },

    // Edit Location Modal State
    editModal: null,
    editUrl: '',
    editCode: '',
    editName: '',

    openEditModal(id, code, name) {
        this.editUrl = '{{ url('admin/locations') }}/' + id;
        this.editCode = code;
        this.editName = name;
        if (!this.editModal) {
            this.editModal = new bootstrap.Modal(document.getElementById('modalEditLocation'));
        }
        this.editModal.show();
    },

    // Delete Location Modal State
    deleteModal: null,
    deleteUrl: '',
    deleteName: '',
    deleteCode: '',

    confirmDeleteLocation(url, name, code) {
        this.deleteUrl = url;
        this.deleteName = name;
        this.deleteCode = code;
        if (!this.deleteModal) {
            this.deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDeleteLocation'));
        }
        this.deleteModal.show();
    },

    async fetchLocationsData(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('locationsDataContainer').innerHTML = html;
        } catch (e) {
            console.error('Error fetching locations data', e);
        } finally {
            this.loading = false;
        }
    },

    onSearchSubmit() {
        const params = new URLSearchParams();
        if (this.search) params.set('search', this.search);
        const url = '{{ route('admin.locations.index') }}?' + params.toString();
        this.fetchLocationsData(url);
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchLocationsData(link.href);
        }
    },

    async initProvinces() {
        try {
            const res = await fetch('{{ route('api.locations') }}');
            this.provinces = await res.json();
        } catch (e) {
            console.error('Failed to load provinces', e);
        }
    },

    async onProvinceChange() {
        const p = this.provinces.find(item => String(item.code) === String(this.selectedProvCode));
        this.selectedProvName = p ? p.name : '';
        this.selectedCityCode = '';
        this.selectedCityName = '';
        this.selectedKecCode = '';
        this.selectedKecName = '';
        this.cities = [];
        this.kecamatans = [];
        this.kelurahans = [];

        if (this.selectedProvCode) {
            try {
                const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedProvCode);
                this.cities = await res.json();
            } catch (e) {
                console.error('Failed to load cities', e);
            }
        }
    },

    async onCityChange() {
        const c = this.cities.find(item => String(item.code) === String(this.selectedCityCode));
        this.selectedCityName = c ? c.name : '';
        this.selectedKecCode = '';
        this.selectedKecName = '';
        this.kecamatans = [];
        this.kelurahans = [];

        if (this.selectedCityCode) {
            try {
                const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedCityCode);
                this.kecamatans = await res.json();
            } catch (e) {
                console.error('Failed to load kecamatans', e);
            }
        }
    },

    async onKecamatanChange() {
        const k = this.kecamatans.find(item => String(item.code) === String(this.selectedKecCode));
        this.selectedKecName = k ? k.name : '';
        this.kelurahans = [];

        if (this.selectedKecCode) {
            try {
                const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedKecCode);
                this.kelurahans = await res.json();
            } catch (e) {
                console.error('Failed to load kelurahans', e);
            }
        }
    }
}" x-init="initProvinces()">

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 mb-4">
            <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal Menyimpan Data Lokasi:</strong>
            <ul class="m-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div>
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-geo-alt-fill text-success me-2"></i> Master Data Lokasi Berjenjang</h5>
                    <small class="text-muted">Kelola data Provinsi, Kota/Kabupaten, Kecamatan, dan Kelurahan/Desa.</small>
                </div>
                <button type="button" @click="openAddModal('province')" class="btn btn-success rounded-pill fw-bold btn-sm px-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Provinsi Baru
                </button>
            </div>

            <!-- Cascading Selector Grids -->
            <div class="row g-3 mb-4">
                <!-- Level 1: Provinsi -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-dark mb-0">1. Provinsi</label>
                            <span class="badge bg-success rounded-pill" x-text="provinces.length + ' item'"></span>
                        </div>
                        <select x-model="selectedProvCode" @change="onProvinceChange()" class="form-select form-select-sm mb-3">
                            <option value="">-- Pilih Provinsi --</option>
                            <template x-for="p in provinces" :key="p.code">
                                <option :value="p.code" x-text="p.name"></option>
                            </template>
                        </select>

                        <div class="d-grid">
                            <button type="button" @click="openAddModal('province')" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Provinsi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Level 2: Kota / Kabupaten -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-dark mb-0">2. Kota / Kabupaten</label>
                            <span class="badge bg-success rounded-pill" x-text="cities.length + ' item'"></span>
                        </div>
                        <select x-model="selectedCityCode" @change="onCityChange()" :disabled="!selectedProvCode" class="form-select form-select-sm mb-3">
                            <option value="">-- Pilih Kota / Kab --</option>
                            <template x-for="c in cities" :key="c.code">
                                <option :value="c.code" x-text="c.name"></option>
                            </template>
                        </select>

                        <div class="d-grid">
                            <button type="button" @click="openAddModal('city', selectedProvCode)" :disabled="!selectedProvCode" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Kota/Kab
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Level 3: Kecamatan -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-dark mb-0">3. Kecamatan</label>
                            <span class="badge bg-success rounded-pill" x-text="kecamatans.length + ' item'"></span>
                        </div>
                        <select x-model="selectedKecCode" @change="onKecamatanChange()" :disabled="!selectedCityCode" class="form-select form-select-sm mb-3">
                            <option value="">-- Pilih Kecamatan --</option>
                            <template x-for="k in kecamatans" :key="k.code">
                                <option :value="k.code" x-text="k.name"></option>
                            </template>
                        </select>

                        <div class="d-grid">
                            <button type="button" @click="openAddModal('kecamatan', selectedCityCode)" :disabled="!selectedCityCode" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Kecamatan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Level 4: Kelurahan / Desa -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-dark mb-0">4. Kelurahan / Desa</label>
                            <span class="badge bg-success rounded-pill" x-text="kelurahans.length + ' item'"></span>
                        </div>
                        <select class="form-select form-select-sm mb-3" :disabled="!selectedKecCode">
                            <option value="">-- Daftar Kelurahan --</option>
                            <template x-for="l in kelurahans" :key="l.code">
                                <option :value="l.code" x-text="l.name"></option>
                            </template>
                        </select>

                        <div class="d-grid">
                            <button type="button" @click="openAddModal('kelurahan', selectedKecCode)" :disabled="!selectedKecCode" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Kelurahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Added Locations Section with Search & AJAX Pagination -->
            <div class="border-top pt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-journal-text me-2 text-success"></i> Lokasi Hasil Kustomisasi / Tambahan</h6>
                    
                    <!-- Search Bar for Locations -->
                    <form @submit.prevent="onSearchSubmit()" class="d-flex gap-2 w-100 w-md-auto">
                        <input type="text" x-model="search" @input.debounce.300ms="onSearchSubmit()" class="form-control form-control-sm" placeholder="Cari nama atau kode lokasi...">
                    </form>
                </div>

                <!-- Progress Bar Indicator -->
                <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
                </div>

                <!-- AJAX Data Container (Desktop Table & Mobile Card View) -->
                <div id="locationsDataContainer" 
                     @click="handlePagination($event)" 
                     :class="{ 'opacity-50 pointer-events-none': loading }" 
                     style="transition: opacity 0.2s ease;">
                    @include('admin.locations.partials.location_list')
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Lokasi Berjenjang -->
    <div class="modal fade" id="modalAddLocation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.locations.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                @csrf
                <div class="modal-header bg-success text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle-fill me-2"></i> Tambah Master Lokasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Tingkat Lokasi <span class="text-danger">*</span></label>
                        <input type="text" readonly name="level" x-model="addLevel" class="form-control bg-light text-uppercase fw-bold">
                    </div>

                    <div class="mb-3" x-show="addParentCode">
                        <label class="form-label fw-bold small">Kode Parent (Induk)</label>
                        <input type="text" readonly name="parent_code" x-model="addParentCode" class="form-control bg-light font-monospace">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Lokasi Unik <span class="text-danger">*</span></label>
                        <input type="text" name="code" x-model="addCode" class="form-control font-monospace" required placeholder="Contoh: 35.78.32">
                        <small class="text-muted fs-7">Format: Provinsi (2 digit), Kota (5 digit), Kec (8 digit), Kel (13 digit). Tidak boleh sama dengan kode yang sudah ada.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="name" x-model="addName" class="form-control" required placeholder="Contoh: SURABAYA BARAT">
                        <small class="text-muted fs-7">Nama lokasi tidak boleh sama dengan nama yang sudah ada.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-save me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Simpan...' : 'Simpan Lokasi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Lokasi -->
    <div class="modal fade" id="modalEditLocation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit Master Lokasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Kode Lokasi Unik <span class="text-danger">*</span></label>
                        <input type="text" name="code" x-model="editCode" class="form-control font-monospace" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lokasi <span class="text-danger">*</span></label>
                        <input type="text" name="name" x-model="editName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-save me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Lokasi -->
    <div class="modal fade" id="modalConfirmDeleteLocation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Lokasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hapus Lokasi Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda yakin ingin menghapus data lokasi <strong x-text="deleteName" class="text-dark"></strong> (<code x-text="deleteCode"></code>)? 
                        Tindakan ini akan menghapus lokasi kustom tersebut dari sistem.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <form :action="deleteUrl" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-trash me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menghapus...' : 'Ya, Hapus Lokasi'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
