@extends('layouts.admin')

@section('title', 'Master Data PAC ISNU')
@section('header_title', 'Master Data PAC ISNU')

@section('content')
<div x-data="{
    provinces: [],
    cities: [],
    kecamatans: [],
    selectedProvinceCode: '',
    provinceName: 'JAWA TIMUR',
    selectedCityCode: '',
    cityName: 'KOTA SURABAYA',
    selectedKecamatanCode: '',
    kecamatanName: '',
    pacName: '',
    pacCode: '',

    deleteModal: null,
    deleteUrl: '',
    deleteTitle: '',

    // Edit Location Cascading States
    editModal: null,
    editUrl: '',
    editProvinces: [],
    editCities: [],
    editKecamatans: [],
    editSelectedProvinceCode: '',
    editProvinceName: '',
    editSelectedCityCode: '',
    editCityName: '',
    editSelectedKecamatanCode: '',
    editKecamatanName: '',
    editCode: '',
    editName: '',

    confirmDelete(url, title) {
        this.deleteUrl = url;
        this.deleteTitle = title;
        if (!this.deleteModal) {
            this.deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDeletePac'));
        }
        this.deleteModal.show();
    },

    async openEditPac(pac) {
        this.editUrl = '{{ url('admin/pac') }}/' + pac.id;
        this.editProvinceName = pac.province || 'JAWA TIMUR';
        this.editCityName = pac.city || 'KOTA SURABAYA';
        this.editKecamatanName = pac.kecamatan || pac.name;
        this.editCode = pac.code;
        this.editName = pac.name;

        await this.initEditLocations();

        if (!this.editModal) {
            this.editModal = new bootstrap.Modal(document.getElementById('modalEditPac'));
        }
        this.editModal.show();
    },

    async initLocations() {
        try {
            const res = await fetch('{{ route('api.locations') }}');
            this.provinces = await res.json();
            const prov = this.provinces.find(p => p.name.toUpperCase() === 'JAWA TIMUR') || this.provinces[0];
            if (prov) {
                this.provinceName = prov.name;
                await this.$nextTick();
                this.selectedProvinceCode = String(prov.code);
                await this.loadCities();
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
            const city = this.cities.find(c => c.name.toUpperCase() === 'KOTA SURABAYA') || this.cities[0];
            if (city) {
                this.cityName = city.name;
                await this.$nextTick();
                this.selectedCityCode = String(city.code);
                await this.loadKecamatans();
            } else {
                this.selectedCityCode = '';
                this.cityName = '';
                this.kecamatans = [];
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
        } catch (e) {
            console.error('Failed to load kecamatans', e);
        }
    },

    async onProvinceChange() {
        const prov = this.provinces.find(p => String(p.code) === String(this.selectedProvinceCode));
        this.provinceName = prov ? prov.name : '';
        this.selectedCityCode = '';
        this.cityName = '';
        this.selectedKecamatanCode = '';
        this.kecamatanName = '';
        this.pacName = '';
        this.pacCode = '';
        this.kecamatans = [];
        await this.loadCities();
    },

    async onCityChange() {
        const city = this.cities.find(c => String(c.code) === String(this.selectedCityCode));
        this.cityName = city ? city.name : '';
        this.selectedKecamatanCode = '';
        this.kecamatanName = '';
        this.pacName = '';
        this.pacCode = '';
        await this.loadKecamatans();
    },

    onKecamatanChange() {
        const kec = this.kecamatans.find(k => String(k.code) === String(this.selectedKecamatanCode));
        if (kec) {
            this.kecamatanName = kec.name;
            this.pacName = kec.name;
            const formattedName = kec.name.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            this.pacCode = 'PAC-' + formattedName;
        } else {
            this.kecamatanName = '';
            this.pacName = '';
        }
    },

    // Edit Modal Cascading Methods
    async initEditLocations() {
        try {
            const res = await fetch('{{ route('api.locations') }}');
            this.editProvinces = await res.json();
            
            const targetProvName = this.editProvinceName || 'JAWA TIMUR';
            const prov = this.editProvinces.find(p => p.name.toUpperCase() === targetProvName.toUpperCase()) || this.editProvinces[0];
            if (prov) {
                this.editProvinceName = prov.name;
                await this.$nextTick();
                this.editSelectedProvinceCode = String(prov.code);
                await this.loadEditCities();
            }
        } catch (e) {
            console.error('Failed to load edit provinces', e);
        }
    },

    async loadEditCities() {
        if (!this.editSelectedProvinceCode) {
            this.editCities = [];
            return;
        }
        try {
            const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.editSelectedProvinceCode);
            this.editCities = await res.json();
            
            const targetCityName = this.editCityName || 'KOTA SURABAYA';
            const city = this.editCities.find(c => c.name.toUpperCase() === targetCityName.toUpperCase()) || this.editCities[0];
            if (city) {
                this.editCityName = city.name;
                await this.$nextTick();
                this.editSelectedCityCode = String(city.code);
                await this.loadEditKecamatans();
            } else {
                this.editSelectedCityCode = '';
                this.editCityName = '';
                this.editKecamatans = [];
            }
        } catch (e) {
            console.error('Failed to load edit cities', e);
        }
    },

    async loadEditKecamatans() {
        if (!this.editSelectedCityCode) {
            this.editKecamatans = [];
            return;
        }
        try {
            const res = await fetch('{{ route('api.locations') }}?parent_code=' + this.editSelectedCityCode);
            this.editKecamatans = await res.json();
            
            if (this.editKecamatanName) {
                const kec = this.editKecamatans.find(k => k.name.toUpperCase() === this.editKecamatanName.toUpperCase());
                if (kec) {
                    this.editKecamatanName = kec.name;
                    await this.$nextTick();
                    this.editSelectedKecamatanCode = String(kec.code);
                }
            }
        } catch (e) {
            console.error('Failed to load edit kecamatans', e);
        }
    },

    async onEditProvinceChange() {
        const prov = this.editProvinces.find(p => String(p.code) === String(this.editSelectedProvinceCode));
        this.editProvinceName = prov ? prov.name : '';
        this.editSelectedCityCode = '';
        this.editCityName = '';
        this.editSelectedKecamatanCode = '';
        this.editKecamatanName = '';
        this.editKecamatans = [];
        await this.loadEditCities();
    },

    async onEditCityChange() {
        const city = this.editCities.find(c => String(c.code) === String(this.editSelectedCityCode));
        this.editCityName = city ? city.name : '';
        this.editSelectedKecamatanCode = '';
        this.editKecamatanName = '';
        await this.loadEditKecamatans();
    },

    onEditKecamatanChange() {
        const kec = this.editKecamatans.find(k => String(k.code) === String(this.editSelectedKecamatanCode));
        if (kec) {
            this.editKecamatanName = kec.name;
        }
    }
}" x-init="initLocations()">

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 mb-4">
            <ul class="m-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Form Tambah PAC ISNU -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3">
                    <i class="bi bi-plus-circle-fill text-success me-2"></i> Tambah PAC ISNU
                </h6>
                <form action="{{ route('admin.pac.store') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <!-- Selection Location Berjenjang -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Provinsi <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="selectedProvinceCode" @change="onProvinceChange()" required>
                            <option value="">-- Pilih Provinsi --</option>
                            <template x-for="p in provinces" :key="p.code">
                                <option :value="String(p.code)" x-text="p.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="province" :value="provinceName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kota / Kabupaten <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="selectedCityCode" @change="onCityChange()" required>
                            <option value="">-- Pilih Kota / Kabupaten --</option>
                            <template x-for="c in cities" :key="c.code">
                                <option :value="String(c.code)" x-text="c.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="city" :value="cityName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kecamatan <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="selectedKecamatanCode" @change="onKecamatanChange()" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            <template x-for="k in kecamatans" :key="k.code">
                                <option :value="String(k.code)" x-text="k.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="kecamatan" :value="kecamatanName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kode PAC (Unik) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" x-model="pacCode" placeholder="Contoh: PAC-GENTENG" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Nama PAC <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" x-model="pacName" placeholder="Contoh: Genteng" required>
                        <span class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                            <i class="bi bi-info-circle me-1"></i> Default nama PAC otomatis disamakan dengan nama Kecamatan, namun tetap dapat Anda ubah.
                        </span>
                    </div>

                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2.5 shadow-sm" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-plus-lg me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan PAC Baru'"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: List PAC ISNU -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold text-dark m-0">
                        <i class="bi bi-diagram-3-fill text-success me-2"></i> Daftar PAC ISNU ({{ $pacs->count() }})
                    </h5>
                </div>

                <div class="list-group list-group-flush">
                    @forelse($pacs as $pac)
                        <div class="list-group-item bg-light rounded-4 mb-3 border-0 p-3.5 shadow-sm">
                            <!-- Desktop Layout (md and above) -->
                            <div class="d-none d-md-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-success-subtle text-success p-2.5 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">
                                            {{ \Illuminate\Support\Str::startsWith($pac->name, 'PAC') ? $pac->name : 'PAC ISNU ' . $pac->name }}
                                            <code class="ms-2 px-2 py-0.5 rounded bg-white text-success border border-success-subtle small fs-7">{{ $pac->code }}</code>
                                        </h6>
                                        <div class="text-muted small">
                                            <i class="bi bi-building me-1"></i> Kec. {{ $pac->kecamatan ?? $pac->name }}, {{ $pac->city ?? 'Kota Surabaya' }}
                                            @if($pac->province && $pac->province !== 'JAWA TIMUR')
                                                , {{ $pac->province }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success rounded-pill px-3 py-1.5 fw-semibold">{{ $pac->members_count }} Anggota</span>
                                    <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Edit PAC ISNU" @click="openEditPac({{ json_encode($pac) }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Hapus PAC ISNU" @click="confirmDelete('{{ route('admin.pac.destroy', $pac->id) }}', '{{ addslashes($pac->name) }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Mobile Layout (sm and below - Requirement 6.1) -->
                            <div class="d-flex d-md-none flex-column gap-3">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                        <h6 class="fw-bold text-dark m-0">
                                            {{ \Illuminate\Support\Str::startsWith($pac->name, 'PAC') ? $pac->name : 'PAC ISNU ' . $pac->name }}
                                        </h6>
                                        <code class="px-2 py-0.5 rounded bg-white text-success border border-success-subtle small font-monospace">{{ $pac->code }}</code>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-geo-alt me-1 text-success"></i> Kec. {{ $pac->kecamatan ?? $pac->name }}, {{ $pac->city ?? 'Kota Surabaya' }}
                                        @if($pac->province && $pac->province !== 'JAWA TIMUR')
                                            , {{ $pac->province }}
                                        @endif
                                    </div>
                                </div>

                                <!-- Bottom Section of Card View -->
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold">
                                        <i class="bi bi-people-fill me-1"></i> {{ $pac->members_count }} Anggota
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Edit PAC ISNU" @click="openEditPac({{ json_encode($pac) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Hapus PAC ISNU" @click="confirmDelete('{{ route('admin.pac.destroy', $pac->id) }}', '{{ addslashes($pac->name) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-diagram-3 display-4 d-block mb-3 opacity-50"></i>
                            <p class="fw-semibold m-0">Belum ada data PAC ISNU.</p>
                            <small>Gunakan form di sebelah kiri untuk menambahkan PAC ISNU baru.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit PAC ISNU (Combobox Berjenjang) -->
    <div class="modal fade" id="modalEditPac" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4">
                @csrf
                @method('PUT')
                <div class="modal-header bg-success text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit PAC ISNU
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Selection Location Berjenjang untuk Edit -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Provinsi <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="editSelectedProvinceCode" @change="onEditProvinceChange()" required>
                            <option value="">-- Pilih Provinsi --</option>
                            <template x-for="p in editProvinces" :key="p.code">
                                <option :value="String(p.code)" x-text="p.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="province" :value="editProvinceName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kota / Kabupaten <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="editSelectedCityCode" @change="onEditCityChange()" required>
                            <option value="">-- Pilih Kota / Kabupaten --</option>
                            <template x-for="c in editCities" :key="c.code">
                                <option :value="String(c.code)" x-text="c.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="city" :value="editCityName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kecamatan <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="editSelectedKecamatanCode" @change="onEditKecamatanChange()" required>
                            <option value="">-- Pilih Kecamatan --</option>
                            <template x-for="k in editKecamatans" :key="k.code">
                                <option :value="String(k.code)" x-text="k.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="kecamatan" :value="editKecamatanName">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kode PAC (Unik) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" x-model="editCode" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama PAC <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" x-model="editName" required>
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

    <!-- Modal Konfirmasi Hapus PAC -->
    <div class="modal fade" id="modalConfirmDeletePac" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus PAC ISNU
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hapus PAC ISNU Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda yakin ingin menghapus data <strong x-text="deleteTitle" class="text-dark"></strong>? 
                        Tindakan ini tidak dapat dibatalkan.
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
                            <span x-text="loading ? 'Menghapus...' : 'Ya, Hapus PAC'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
