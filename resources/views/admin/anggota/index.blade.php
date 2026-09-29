@extends('layouts.admin')

@section('title', 'Database Potensi Kader')
@section('header_title', 'Database Anggota')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4" x-init="initLocations()" x-data="{
    deleteModal: null,
    deleteUrl: '',
    deleteName: '',
    confirmDelete(url, name) {
        this.deleteUrl = url;
        this.deleteName = name;
        if (!this.deleteModal) {
            this.deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        }
        this.deleteModal.show();
    },

    loading: false,
    showFilters: {{ request()->hasAny(['province', 'city', 'kecamatan', 'kelurahan', 'status', 'education_level', 'nu_training', 'organization', 'employment', 'certification']) ? 'true' : 'false' }},

    searchQuery: '{{ request('search') }}',
    statusQuery: '{{ request('status') }}',
    educationLevelQuery: '{{ request('education_level') }}',
    nuTrainingQuery: '{{ request('nu_training') }}',
    organizationQuery: '{{ request('organization') }}',
    employmentQuery: '{{ request('employment') }}',
    certificationQuery: '{{ request('certification') }}',

    provinces: [],
    cities: [],
    kecamatans: [],
    kelurahans: [],

    selectedProvinceCode: '',
    provinceName: '{{ request('province') }}',

    selectedCityCode: '',
    cityName: '{{ request('city') }}',

    selectedKecamatanCode: '',
    kecamatanName: '{{ request('kecamatan') }}',

    selectedKelurahanCode: '',
    kelurahanName: '{{ request('kelurahan') }}',

    async initLocations() {
        try {
            const res = await fetch('{{ route('api.locations') }}');
            this.provinces = await res.json();

            if (this.provinceName) {
                const prov = this.provinces.find(p => p.name.toUpperCase() === this.provinceName.toUpperCase());
                if (prov) {
                    await this.$nextTick();
                    this.selectedProvinceCode = String(prov.code);
                    await this.loadCities();
                }
            }
        } catch (e) {
            console.error('Failed to load provinces', e);
        }
    },

    async loadCities() {
        if (!this.selectedProvinceCode) {
            this.cities = [];
            return;
        }
        try {
            const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedProvinceCode);
            this.cities = await res.json();

            if (this.cityName) {
                const city = this.cities.find(c => c.name.toUpperCase() === this.cityName.toUpperCase());
                if (city) {
                    await this.$nextTick();
                    this.selectedCityCode = String(city.code);
                    await this.loadKecamatans();
                }
            }
        } catch (e) {
            console.error('Failed to load cities', e);
        }
    },

    async loadKecamatans() {
        if (!this.selectedCityCode) {
            this.kecamatans = [];
            return;
        }
        try {
            const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedCityCode);
            this.kecamatans = await res.json();

            if (this.kecamatanName) {
                const kec = this.kecamatans.find(k => k.name.toUpperCase() === this.kecamatanName.toUpperCase());
                if (kec) {
                    await this.$nextTick();
                    this.selectedKecamatanCode = String(kec.code);
                    await this.loadKelurahans();
                }
            }
        } catch (e) {
            console.error('Failed to load kecamatans', e);
        }
    },

    async loadKelurahans() {
        if (!this.selectedKecamatanCode) {
            this.kelurahans = [];
            return;
        }
        try {
            const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.selectedKecamatanCode);
            this.kelurahans = await res.json();

            if (this.kelurahanName) {
                const kel = this.kelurahans.find(l => l.name.toUpperCase() === this.kelurahanName.toUpperCase());
                if (kel) {
                    await this.$nextTick();
                    this.selectedKelurahanCode = String(kel.code);
                }
            }
        } catch (e) {
            console.error('Failed to load kelurahans', e);
        }
    },

    async onProvinceChange() {
        const prov = this.provinces.find(p => String(p.code) === String(this.selectedProvinceCode));
        this.provinceName = prov ? prov.name : '';
        this.selectedCityCode = '';
        this.cityName = '';
        this.selectedKecamatanCode = '';
        this.kecamatanName = '';
        this.selectedKelurahanCode = '';
        this.kelurahanName = '';
        this.kecamatans = [];
        this.kelurahans = [];
        await this.loadCities();
        this.applyFilter();
    },

    async onCityChange() {
        const city = this.cities.find(c => String(c.code) === String(this.selectedCityCode));
        this.cityName = city ? city.name : '';
        this.selectedKecamatanCode = '';
        this.kecamatanName = '';
        this.selectedKelurahanCode = '';
        this.kelurahanName = '';
        this.kelurahans = [];
        await this.loadKecamatans();
        this.applyFilter();
    },

    async onKecamatanChange() {
        const kec = this.kecamatans.find(k => String(k.code) === String(this.selectedKecamatanCode));
        this.kecamatanName = kec ? kec.name : '';
        this.selectedKelurahanCode = '';
        this.kelurahanName = '';
        await this.loadKelurahans();
        this.applyFilter();
    },

    onKelurahanChange() {
        const kel = this.kelurahans.find(l => String(l.code) === String(this.selectedKelurahanCode));
        this.kelurahanName = kel ? kel.name : '';
        this.applyFilter();
    },

    async applyFilter() {
        const params = new URLSearchParams();
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.provinceName) params.set('province', this.provinceName);
        if (this.cityName) params.set('city', this.cityName);
        if (this.kecamatanName) params.set('kecamatan', this.kecamatanName);
        if (this.kelurahanName) params.set('kelurahan', this.kelurahanName);
        if (this.statusQuery) params.set('status', this.statusQuery);
        if (this.educationLevelQuery) params.set('education_level', this.educationLevelQuery);
        if (this.nuTrainingQuery) params.set('nu_training', this.nuTrainingQuery);
        if (this.organizationQuery) params.set('organization', this.organizationQuery);
        if (this.employmentQuery) params.set('employment', this.employmentQuery);
        if (this.certificationQuery) params.set('certification', this.certificationQuery);

        const targetUrl = '{{ route('admin.anggota.index') }}' + (params.toString() ? '?' + params.toString() : '');
        await this.fetchUrl(targetUrl);
    },

    async resetFilter() {
        this.searchQuery = '';
        this.statusQuery = '';
        this.educationLevelQuery = '';
        this.nuTrainingQuery = '';
        this.organizationQuery = '';
        this.employmentQuery = '';
        this.certificationQuery = '';
        this.selectedProvinceCode = '';
        this.provinceName = '';
        this.selectedCityCode = '';
        this.cityName = '';
        this.selectedKecamatanCode = '';
        this.kecamatanName = '';
        this.selectedKelurahanCode = '';
        this.kelurahanName = '';
        this.cities = [];
        this.kecamatans = [];
        this.kelurahans = [];
        await this.fetchUrl('{{ route('admin.anggota.index') }}');
    },

    async fetchUrl(url) {
        this.loading = true;
        try {
            window.history.pushState({}, '', url);
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await res.text();
            document.getElementById('membersDataContainer').innerHTML = html;
        } catch (e) {
            console.error('Failed to fetch members data', e);
        } finally {
            this.loading = false;
        }
    },

    handlePagination(e) {
        const link = e.target.closest('a.page-link');
        if (link && link.href) {
            e.preventDefault();
            this.fetchUrl(link.href);
        }
    }
}">
    <div class="card-body p-4">
        <!-- Advanced Multi-Filter Form -->
        <form @submit.prevent="applyFilter()" class="mb-4">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" 
                               x-model="searchQuery" 
                               @input.debounce.300ms="applyFilter()" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="Cari nama, NIK, no. anggota, atau profesi...">
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-success rounded-pill px-3 fw-semibold" @click="showFilters = !showFilters">
                        <i class="bi bi-funnel me-1"></i> Filter <span x-text="showFilters ? '▲' : '▼'" class="small ms-1"></span>
                    </button>
                    <button type="button" 
                            class="btn btn-outline-secondary rounded-pill px-3" 
                            @click="resetFilter()" 
                            x-show="searchQuery || provinceName || cityName || kecamatanName || kelurahanName || statusQuery || educationLevelQuery || nuTrainingQuery || organizationQuery || employmentQuery || certificationQuery">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Filter Drawer Panel (Collapsible - default hidden) -->
            <div x-show="showFilters" x-transition class="p-3 bg-light rounded-4 border mb-3" style="display: none;">
                <div class="row g-3">
                    <!-- Location Filters -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Provinsi</label>
                        <select x-model="selectedProvinceCode" @change="onProvinceChange()" class="form-select form-select-sm">
                            <option value="">-- Semua Provinsi --</option>
                            <template x-for="p in provinces" :key="p.code">
                                <option :value="p.code" x-text="p.name" :selected="p.code === selectedProvinceCode"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Kota / Kabupaten</label>
                        <select x-model="selectedCityCode" @change="onCityChange()" :disabled="!selectedProvinceCode" class="form-select form-select-sm">
                            <option value="">-- Semua Kota / Kab --</option>
                            <template x-for="c in cities" :key="c.code">
                                <option :value="c.code" x-text="c.name" :selected="c.code === selectedCityCode"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Kecamatan</label>
                        <select x-model="selectedKecamatanCode" @change="onKecamatanChange()" :disabled="!selectedCityCode" class="form-select form-select-sm">
                            <option value="">-- Semua Kecamatan --</option>
                            <template x-for="k in kecamatans" :key="k.code">
                                <option :value="k.code" x-text="k.name" :selected="k.code === selectedKecamatanCode"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Kelurahan</label>
                        <select x-model="selectedKelurahanCode" @change="onKelurahanChange()" :disabled="!selectedKecamatanCode" class="form-select form-select-sm">
                            <option value="">-- Semua Kelurahan --</option>
                            <template x-for="l in kelurahans" :key="l.code">
                                <option :value="l.code" x-text="l.name" :selected="l.code === selectedKelurahanCode"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Status & Pendidikan -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Status Keanggotaan</label>
                        <select x-model="statusQuery" class="form-select form-select-sm" @change="applyFilter()">
                            <option value="">-- Semua Status --</option>
                            <option value="terverifikasi">Terverifikasi (Anggota)</option>
                            <option value="pengurus">Pengurus</option>
                            <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">Jenjang Pendidikan</label>
                        <select x-model="educationLevelQuery" class="form-select form-select-sm" @change="applyFilter()">
                            <option value="">-- Semua Pendidikan --</option>
                            <option value="S1">S1 (Sarjana)</option>
                            <option value="S2">S2 (Magister)</option>
                            <option value="S3">S3 (Doktor)</option>
                        </select>
                    </div>

                    <!-- Rekam Jejak & Potensi Filters -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Kaderisasi NU</label>
                        <input type="text" x-model="nuTrainingQuery" @input.debounce.300ms="applyFilter()" class="form-control form-select-sm" placeholder="Cari MKNU, PKPNU...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Riwayat Organisasi</label>
                        <input type="text" x-model="organizationQuery" @input.debounce.300ms="applyFilter()" class="form-control form-select-sm" placeholder="Cari nama organisasi/jabatan...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Riwayat Pekerjaan</label>
                        <input type="text" x-model="employmentQuery" @input.debounce.300ms="applyFilter()" class="form-control form-select-sm" placeholder="Cari instansi/jabatan...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Sertifikasi Keahlian</label>
                        <input type="text" x-model="certificationQuery" @input.debounce.300ms="applyFilter()" class="form-control form-select-sm" placeholder="Cari nama sertifikasi/bidang...">
                    </div>
                </div>
            </div>
        </form>

        <!-- Progress Bar Indicator -->
        <div class="progress rounded-pill mb-3" style="height: 4px;" x-show="loading" x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
        </div>

        <!-- AJAX Content Container -->
        <div id="membersDataContainer" 
             @click="handlePagination($event)" 
             :class="{ 'opacity-50 pointer-events-none': loading }" 
             style="transition: opacity 0.2s ease;">
            @include('admin.anggota.partials.member_list')
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus (Popup Window) -->
    <div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-trash3-fill me-2"></i> Pindahkan Data ke Sampah
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Pindahkan Data Anggota Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda yakin ingin menghapus data anggota <strong x-text="deleteName" class="text-dark"></strong>? 
                        Data ini akan dipindahkan ke menu <strong class="text-dark">Data Sampah</strong> dan dapat dipulihkan kembali sewaktu-waktu.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <form :action="deleteUrl" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-trash me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Memindahkan...' : 'Ya, Pindahkan ke Sampah'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
