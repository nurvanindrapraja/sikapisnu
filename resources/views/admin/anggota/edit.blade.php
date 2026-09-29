@extends('layouts.admin')

@section('title', 'Edit Data Anggota')
@section('header_title', 'Edit Data Anggota')

@section('content')
<style>
@keyframes toastSlideIn {
    from {
        transform: translateX(120%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
.toast-slide-in {
    animation: toastSlideIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
</style>

<div class="card border-0 shadow-sm rounded-4 p-4" @history-updated.window="addToast('Berhasil!', $event.detail.message); refreshContent()" x-init="initLocations()" x-data="{
    loading: false,
    toasts: [],
    provinces: [],
    cities: [],
    kecamatans: [],
    kelurahans: [],
    selectedProvinceCode: '',
    provinceName: '{{ old('province', $member->province ?? 'JAWA TIMUR') }}',
    selectedCityCode: '',
    cityName: '{{ old('city', $member->city ?? 'KOTA SURABAYA') }}',
    selectedKecamatanCode: '',
    kecamatanName: '{{ old('kecamatan', $member->kecamatan) }}',
    selectedKelurahanCode: '',
    kelurahanName: '{{ old('kelurahan', $member->kelurahan) }}',

    deleteModal: null,
    deleteUrl: '',
    deleteTitle: '',
    editModal: null,
    editUrl: '',
    editType: '',
    editForm: {},
    successModal: null,
    successMessage: '',
    redirectUrl: '{{ route('admin.anggota.index') }}',

    openEdit(type, url, data) {
        this.editType = type;
        this.editUrl = url;
        this.editForm = Object.assign({}, data);
        if (!this.editModal) {
            this.editModal = new bootstrap.Modal(document.getElementById('modalEditHistoryItem'));
        }
        this.editModal.show();
    },

    addToast(title, message, type = 'success') {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, title, message, type });
        setTimeout(() => {
            this.removeToast(id);
        }, 4000);
    },

    removeToast(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },

    confirmDelete(url, title) {
        this.deleteUrl = url;
        this.deleteTitle = title;
        if (!this.deleteModal) {
            this.deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDeleteHistory'));
        }
        this.deleteModal.show();
    },

    async executeDelete() {
        if (this.deleteModal) {
            this.deleteModal.hide();
        }
        this.loading = true;
        try {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'DELETE');

            const res = await fetch(this.deleteUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.addToast('Berhasil', data.message || 'Data berhasil dihapus.');
                await this.refreshContent();
            } else {
                this.addToast('Gagal!', data.message || 'Gagal menghapus data.', 'error');
            }
        } catch (err) {
            console.error('AJAX Delete error', err);
            this.addToast('Terjadi Kesalahan', 'Gagal menghapus data.', 'error');
        } finally {
            this.loading = false;
        }
    },

    async submitBiodata(e) {
        e.preventDefault();
        const form = e.target;
        this.loading = true;
        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, {
                method: form.getAttribute('method') || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.successMessage = data.message || 'Data anggota berhasil diperbarui.';
                if (!this.successModal) {
                    this.successModal = new bootstrap.Modal(document.getElementById('modalSuccessBiodata'));
                }
                this.successModal.show();
            } else {
                let msg = data.message || 'Terjadi kesalahan saat menyimpan data.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join(', ');
                }
                this.addToast('Gagal!', msg, 'error');
            }
        } catch (err) {
            console.error('AJAX Submit error', err);
            this.addToast('Terjadi Kesalahan', 'Gagal menghubungkan ke server.', 'error');
        } finally {
            this.loading = false;
        }
    },

    redirectToAnggota() {
        if (this.successModal) {
            this.successModal.hide();
        }
        window.location.href = this.redirectUrl;
    },

    async handleAjaxFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        this.loading = true;
        try {
            const formData = new FormData(form);
            const res = await fetch(form.action, {
                method: form.getAttribute('method') || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.addToast('Berhasil!', data.message || 'Data berhasil disimpan.');
                const modalEl = form.closest('.modal');
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                form.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(i => {
                    if (i.tagName === 'SELECT') {
                        i.selectedIndex = 0;
                    } else if (i.type !== 'file') {
                        i.value = '';
                    }
                });
                await this.refreshContent();
            } else {
                let msg = data.message || 'Terjadi kesalahan saat menyimpan data.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join(', ');
                }
                this.addToast('Gagal!', msg, 'error');
            }
        } catch (err) {
            console.error('AJAX Submit error', err);
            this.addToast('Terjadi Kesalahan', 'Gagal menghubungkan ke server.', 'error');
        } finally {
            this.loading = false;
        }
    },

    async refreshContent() {
        try {
            const res = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newRightCol = doc.getElementById('historyRightCol');
            if (newRightCol) {
                const target = document.getElementById('historyRightCol');
                if (target) {
                    target.innerHTML = newRightCol.innerHTML;
                }
            }
        } catch (e) {
            console.error('Failed to refresh content', e);
        }
    },

    async initLocations() {
        try {
            const res = await fetch('{{ route('api.locations') }}');
            this.provinces = await res.json();

            const targetProvName = this.provinceName || 'JAWA TIMUR';
            const prov = this.provinces.find(p => p.name.toUpperCase() === targetProvName.toUpperCase()) || this.provinces.find(p => String(p.code) === '35') || this.provinces[0];
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

            const targetCityName = this.cityName || 'KOTA SURABAYA';
            const city = this.cities.find(c => c.name.toUpperCase() === targetCityName.toUpperCase()) || this.cities.find(c => String(c.code) === '35.78') || this.cities[0];
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

            if (this.kecamatanName) {
                const kec = this.kecamatans.find(k => k.name.toUpperCase() === this.kecamatanName.toUpperCase());
                if (kec) {
                    this.kecamatanName = kec.name;
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
                    this.kelurahanName = kel.name;
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
    },

    async onKecamatanChange() {
        const kec = this.kecamatans.find(k => String(k.code) === String(this.selectedKecamatanCode));
        this.kecamatanName = kec ? kec.name : '';
        this.selectedKelurahanCode = '';
        this.kelurahanName = '';
        await this.loadKelurahans();
    },

    onKelurahanChange() {
        const kel = this.kelurahans.find(l => String(l.code) === String(this.selectedKelurahanCode));
        this.kelurahanName = kel ? kel.name : '';
    }
}">
    <!-- Top Fixed Progress Bar -->
    <div class="progress rounded-0 position-fixed top-0 start-0 w-100" style="height: 4px; z-index: 99999;" x-show="loading" x-transition>
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
    </div>

    <!-- Toast Slideshow Notification Container Top Right -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999; max-width: 380px; width: 100%;">
        <template x-for="t in toasts" :key="t.id">
            <div class="toast show align-items-center text-white border-0 mb-2 shadow-lg rounded-3 toast-slide-in"
                 :class="t.type === 'error' ? 'bg-danger' : 'bg-success'"
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <i class="bi fs-5" :class="t.type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'"></i>
                        <div>
                            <strong class="d-block text-white" x-text="t.title"></strong>
                            <span x-text="t.message" class="small"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="removeToast(t.id)"></button>
                </div>
            </div>
        </template>
    </div>
    <!-- Header Title & Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-pencil-square text-success me-2"></i> Edit Profil Anggota: {{ $member->full_name }}
        </h5>
        <a href="{{ route('admin.anggota.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-2.5 px-md-3 shadow-sm" title="Kembali ke Daftar">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline ms-1">Kembali ke Daftar</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 mb-4">
            <ul class="m-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2 Desktop Columns Layout (col-lg-6 Left, col-lg-6 Right) -->
    <div class="row g-4">
        <!-- Left Column: Form Edit Biodata Utama -->
        <div class="col-lg-6">
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3">
                    <i class="bi bi-person-vcard text-success me-2"></i> Edit Biodata Utama
                </h6>
                <form action="{{ route('admin.anggota.update', $member->id) }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitBiodata($event)">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $member->full_name) }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">NIK <span class="text-muted small">(Opsional)</span></label>
                            <input type="text" name="nik" class="form-control" value="{{ old('nik', $member->nik) }}" placeholder="3578...">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">No. Whatsapp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone) }}" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Pekerjaan / Profesi Saat Ini <span class="text-danger">*</span></label>
                            <input type="text" name="occupation" class="form-control" list="occupationList" placeholder="Contoh: Dosen, Dokter, Jurnalis, Aktivis NGO / LSM..." value="{{ old('occupation', $member->occupation) }}" required>
                            <datalist id="occupationList">
                                <option value="Jurnalis"></option>
                                <option value="Aktivis NGO / LSM"></option>
                                <option value="Dosen / Akademisi"></option>
                                <option value="Dokter / Tenaga Medis"></option>
                                <option value="Software Engineer / Konsultan IT"></option>
                                <option value="Guru / Pendidik"></option>
                                <option value="Wiraswasta / Pengusaha"></option>
                                <option value="ASN / PNS"></option>
                                <option value="Pengacara / Praktisi Hukum"></option>
                                <option value="Peneliti / Researcher"></option>
                                <option value="Perbankan / Keuangan"></option>
                                <option value="Sastrawan / Budayawan"></option>
                                <option value="Konsultan / Profesional"></option>
                            </datalist>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Status Keanggotaan <span class="text-danger">*</span></label>
                            <select name="membership_status" class="form-select" required>
                                <option value="menunggu_verifikasi" {{ old('membership_status', $member->membership_status) === 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                                <option value="terverifikasi" {{ old('membership_status', $member->membership_status) === 'terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                                <option value="pengurus" {{ old('membership_status', $member->membership_status) === 'pengurus' ? 'selected' : '' }}>Pengurus</option>
                                <option value="perbaikan" {{ old('membership_status', $member->membership_status) === 'perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                <option value="ditolak" {{ old('membership_status', $member->membership_status) === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tempat Lahir <span class="text-danger">*</span></label>
                            <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $member->birth_place) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $member->birth_date ? $member->birth_date->format('Y-m-d') : '') }}" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="L" {{ old('gender', $member->gender) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ old('gender', $member->gender) === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat Rumah Lengkap <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required>{{ old('address', $member->address) }}</textarea>
                        </div>

                        <!-- Cascading Location Dropdowns -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Provinsi <span class="text-danger">*</span></label>
                            <input type="hidden" name="province" :value="provinceName">
                            <select x-model="selectedProvinceCode" @change="onProvinceChange()" class="form-select" required>
                                <option value="">-- Pilih Provinsi --</option>
                                <template x-for="p in provinces" :key="p.code">
                                    <option :value="p.code" x-text="p.name" :selected="p.code === selectedProvinceCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kota / Kabupaten <span class="text-danger">*</span></label>
                            <input type="hidden" name="city" :value="cityName">
                            <select x-model="selectedCityCode" @change="onCityChange()" :disabled="!selectedProvinceCode" class="form-select" required>
                                <option value="">-- Pilih Kota / Kabupaten --</option>
                                <template x-for="c in cities" :key="c.code">
                                    <option :value="c.code" x-text="c.name" :selected="c.code === selectedCityCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                            <input type="hidden" name="kecamatan" :value="kecamatanName">
                            <select x-model="selectedKecamatanCode" @change="onKecamatanChange()" :disabled="!selectedCityCode" class="form-select" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                <template x-for="k in kecamatans" :key="k.code">
                                    <option :value="k.code" x-text="k.name" :selected="k.code === selectedKecamatanCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelurahan <span class="text-danger">*</span></label>
                            <input type="hidden" name="kelurahan" :value="kelurahanName">
                            <select x-model="selectedKelurahanCode" @change="onKelurahanChange()" :disabled="!selectedKecamatanCode" class="form-select" required>
                                <option value="">-- Pilih Kelurahan --</option>
                                <template x-for="l in kelurahans" :key="l.code">
                                    <option :value="l.code" x-text="l.name" :selected="l.code === selectedKelurahanCode"></option>
                                </template>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto Profil</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $member->photo_url }}" alt="" class="rounded-3 border" style="width: 60px; height: 75px; object-fit: cover;">
                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.anggota.index') }}" class="btn btn-secondary rounded-pill px-4 w-100 w-md-auto text-center">Batal</a>
                        <button type="submit" class="btn btn-success fw-bold rounded-pill px-4 w-100 w-md-auto" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-save me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menyimpan...' : 'Simpan Biodata'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: 5 Background History Management Cards -->
        <div class="col-lg-6 space-y-4" id="historyRightCol">
            <!-- 1. Riwayat Pendidikan Card -->
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-mortarboard-fill text-success me-2"></i> Riwayat Pendidikan ({{ $member->educations->count() }})
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 px-md-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddEducation" title="Tambah Pendidikan">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline ms-1">Tambah</span>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($member->educations as $edu)
                        <div class="list-group-item bg-light rounded-3 mb-2 border-0 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-dark d-block">{{ $edu->level }} @if($edu->major){{ $edu->major }}@endif</strong>
                                <small class="text-muted">{{ $edu->institution_name }} ({{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }})</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Pendidikan" @click='$dispatch("open-edit-education", { url: "{{ route('admin.anggota.education.update', [$member->id, $edu->id]) }}", data: {{ json_encode($edu) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Pendidikan" @click="confirmDelete('{{ route('admin.anggota.education.delete', [$member->id, $edu->id]) }}', 'Pendidikan {{ addslashes($edu->level) }} {{ addslashes($edu->institution_name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small fst-italic m-0">Belum ada data pendidikan.</p>
                    @endforelse
                </div>
            </div>

            <!-- 2. Riwayat Organisasi Card -->
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-people-fill text-success me-2"></i> Riwayat Organisasi ({{ $member->organizations->count() }})
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 px-md-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddOrganization" title="Tambah Organisasi">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline ms-1">Tambah</span>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($member->organizations as $org)
                        <div class="list-group-item bg-light rounded-3 mb-2 border-0 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-dark d-block">{{ $org->organization_name }}</strong>
                                <small class="text-muted">{{ $org->position }} @if($org->period)({{ $org->period }})@endif</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Organisasi" @click='$dispatch("open-edit-organization", { url: "{{ route('admin.anggota.organization.update', [$member->id, $org->id]) }}", data: {{ json_encode($org) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Organisasi" @click="confirmDelete('{{ route('admin.anggota.organization.delete', [$member->id, $org->id]) }}', 'Organisasi {{ addslashes($org->organization_name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small fst-italic m-0">Belum ada data organisasi.</p>
                    @endforelse
                </div>
            </div>

            <!-- 3. Riwayat Pekerjaan Card -->
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-briefcase-fill text-success me-2"></i> Riwayat Pekerjaan ({{ $member->employments->count() }})
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 px-md-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddEmployment" title="Tambah Pekerjaan">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline ms-1">Tambah</span>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($member->employments as $emp)
                        <div class="list-group-item bg-light rounded-3 mb-2 border-0 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                @if($emp->employment_status)
                                    <span class="badge bg-secondary me-1">{{ $emp->employment_status }}</span>
                                @endif
                                <strong class="text-dark d-block">{{ $emp->position }}</strong>
                                <small class="text-muted">{{ $emp->company_name }} ({{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }})</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Pekerjaan" @click='$dispatch("open-edit-employment", { url: "{{ route('admin.anggota.employment.update', [$member->id, $emp->id]) }}", data: {{ json_encode($emp) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Pekerjaan" @click="confirmDelete('{{ route('admin.anggota.employment.delete', [$member->id, $emp->id]) }}', 'Pekerjaan {{ addslashes($emp->position) }} di {{ addslashes($emp->company_name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small fst-italic m-0">Belum ada data pekerjaan.</p>
                    @endforelse
                </div>
            </div>

            <!-- 4. Riwayat Kaderisasi NU Card -->
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-award-fill text-success me-2"></i> Kaderisasi NU ({{ $member->nuTrainings->count() }})
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 px-md-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddNuTraining" title="Tambah Kaderisasi NU">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline ms-1">Tambah</span>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($member->nuTrainings as $nu)
                        <div class="list-group-item bg-light rounded-3 mb-2 border-0 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success me-1">{{ $nu->training_type }}</span>
                                <strong class="text-dark">{{ $nu->organizer }}</strong>
                                @if($nu->year)<small class="text-muted">({{ $nu->year }})</small>@endif
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Kaderisasi" @click='$dispatch("open-edit-nu-training", { url: "{{ route('admin.anggota.nu_training.update', [$member->id, $nu->id]) }}", data: {{ json_encode($nu) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Kaderisasi" @click="confirmDelete('{{ route('admin.anggota.nu_training.delete', [$member->id, $nu->id]) }}', 'Kaderisasi {{ addslashes($nu->training_type) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small fst-italic m-0">Belum ada data kaderisasi NU.</p>
                    @endforelse
                </div>
            </div>

            <!-- 5. Sertifikasi Keahlian Card -->
            <div class="card border border-light-subtle shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark m-0">
                        <i class="bi bi-patch-check-fill text-success me-2"></i> Sertifikasi Keahlian ({{ $member->certifications->count() }})
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2.5 px-md-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddCertification" title="Tambah Sertifikasi">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline ms-1">Tambah</span>
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($member->certifications as $cert)
                        <div class="list-group-item bg-light rounded-3 mb-2 border-0 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-dark d-block">{{ $cert->certification_name }}</strong>
                                <small class="text-muted">{{ $cert->field }} @if($cert->issue_year)({{ $cert->issue_year }})@endif</small>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Sertifikasi" @click='$dispatch("open-edit-certification", { url: "{{ route('admin.anggota.certification.update', [$member->id, $cert->id]) }}", data: {{ json_encode($cert) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Sertifikasi" @click="confirmDelete('{{ route('admin.anggota.certification.delete', [$member->id, $cert->id]) }}', 'Sertifikasi {{ addslashes($cert->certification_name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small fst-italic m-0">Belum ada data sertifikasi keahlian.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Success Popup Window for Biodata Submit -->
    <div class="modal fade" id="modalSuccessBiodata" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white border-0 rounded-top-4 justify-content-center position-relative py-3">
                    <h5 class="modal-title fw-bold m-0 text-center">
                        <i class="bi bi-check-circle-fill me-2"></i> Berhasil Disimpan
                    </h5>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-success mb-3">
                        <i class="bi bi-check-circle-fill display-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Pembaruan Data Berhasil</h5>
                    <p class="text-muted mb-0" x-text="successMessage"></p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center p-3">
                    <button type="button" class="btn btn-success fw-bold rounded-pill px-5 py-2 shadow-sm fs-6" data-bs-dismiss="modal" @click="redirectToAnggota()" onclick="window.location.href='{{ route('admin.anggota.index') }}'">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Riwayat (Popup Window) -->
    <div class="modal fade" id="modalConfirmDeleteHistory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hapus Data Ini?</h5>
                    <p class="text-muted mb-0">
                        Apakah Anda yakin ingin menghapus data <strong x-text="deleteTitle" class="text-dark"></strong>? 
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" @click="executeDelete()">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Modal 1: Tambah Pendidikan -->
<div class="modal fade" id="modalAddEducation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.anggota.education.add', $member->id) }}" method="POST" class="modal-content border-0 shadow rounded-4" @submit.prevent="handleAjaxFormSubmit($event)">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-mortarboard-fill me-2"></i> Tambah Riwayat Pendidikan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenjang <span class="text-danger">*</span></label>
                    <select name="level" class="form-select" required>
                        <option value="S1">S1 (Sarjana)</option>
                        <option value="S2">S2 (Magister)</option>
                        <option value="S3">S3 (Doktor)</option>
                        <option value="D3">D3 (Diploma)</option>
                        <option value="SMA/SMK">SMA / SMK / MA</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Institusi / Sekolah / Kampus <span class="text-danger">*</span></label>
                    <input type="text" name="institution_name" class="form-control" placeholder="Contoh: Universitas Airlangga" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jurusan / Program Studi</label>
                    <input type="text" name="major" class="form-control" placeholder="Contoh: Teknik Informatika">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Gelar Akademik</label>
                    <input type="text" name="degree" class="form-control" placeholder="Contoh: S.Kom., M.T.">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Masuk</label>
                        <input type="number" name="start_year" class="form-control" placeholder="2015">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Lulus</label>
                        <input type="number" name="end_year" class="form-control" placeholder="2019">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Memproses...' : 'Simpan Pendidikan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Tambah Organisasi -->
<div class="modal fade" id="modalAddOrganization" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.anggota.organization.add', $member->id) }}" method="POST" class="modal-content border-0 shadow rounded-4" @submit.prevent="handleAjaxFormSubmit($event)">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i> Tambah Riwayat Organisasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Organisasi <span class="text-danger">*</span></label>
                    <input type="text" name="organization_name" class="form-control" placeholder="Contoh: PMII / IPNU / Lakpesdam" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan / Peran</label>
                    <input type="text" name="position" class="form-control" placeholder="Contoh: Ketua Cabang / Anggota">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Periode</label>
                    <input type="text" name="period" class="form-control" placeholder="Contoh: 2018-2021">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Memproses...' : 'Simpan Organisasi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Tambah Pekerjaan -->
<div class="modal fade" id="modalAddEmployment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.anggota.employment.add', $member->id) }}" method="POST" class="modal-content border-0 shadow rounded-4" @submit.prevent="handleAjaxFormSubmit($event)">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-briefcase-fill me-2"></i> Tambah Riwayat Pekerjaan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" placeholder="Contoh: Dinas Pendidikan / PT Surabaya Jaya" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan / Peran <span class="text-danger">*</span></label>
                    <input type="text" name="position" class="form-control" placeholder="Contoh: Staf Analis / Manager" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kategori / Bidang Pekerjaan</label>
                    <select name="employment_status" class="form-select">
                        <option value="">-- Pilih Kategori Pekerjaan --</option>
                        <option value="ASN">ASN</option>
                        <option value="TNI/Polri">TNI/Polri</option>
                        <option value="BUMN">BUMN / BUMD</option>
                        <option value="Swasta">Swasta</option>
                        <option value="Profesional">Profesional</option>
                        <option value="Wirausaha">Wirausaha</option>
                        <option value="Akademisi">Akademisi / Dosen</option>
                        <option value="Tenaga Pendidik">Tenaga Pendidik / Guru</option>
                        <option value="Tenaga Kesehatan">Tenaga Kesehatan / Dokter</option>
                        <option value="Jurnalis">Jurnalis</option>
                        <option value="Aktivis LSM / NGO">Aktivis LSM / NGO</option>
                        <option value="Freelancer">Freelancer</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Mulai</label>
                        <input type="number" name="start_year" class="form-control" placeholder="2020">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Selesai</label>
                        <input type="number" name="end_year" class="form-control" placeholder="Kosongkan jika masih aktif">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Memproses...' : 'Simpan Pekerjaan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Tambah Kaderisasi NU -->
<div class="modal fade" id="modalAddNuTraining" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.anggota.nu_training.add', $member->id) }}" method="POST" class="modal-content border-0 shadow rounded-4" @submit.prevent="handleAjaxFormSubmit($event)">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-award-fill me-2"></i> Tambah Kaderisasi NU</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kaderisasi <span class="text-danger">*</span></label>
                    <input type="text" name="training_type" class="form-control" placeholder="Contoh: PDPKPNU / PKPNU / MKNU / PKN" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Penyelenggara</label>
                    <input type="text" name="organizer" class="form-control" placeholder="Contoh: PCNU Kota Surabaya / PBNU">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tahun Keikutsertaan</label>
                    <input type="number" name="year" class="form-control" placeholder="2022">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor Sertifikat / SK</label>
                    <input type="text" name="certificate_number" class="form-control" placeholder="PKPNU-SBY-2022-001">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Memproses...' : 'Simpan Kaderisasi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 5: Tambah Sertifikasi Keahlian -->
<div class="modal fade" id="modalAddCertification" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.anggota.certification.add', $member->id) }}" method="POST" class="modal-content border-0 shadow rounded-4" @submit.prevent="handleAjaxFormSubmit($event)">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-patch-check-fill me-2"></i> Tambah Sertifikasi Keahlian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Sertifikasi <span class="text-danger">*</span></label>
                    <input type="text" name="certification_name" class="form-control" placeholder="Contoh: Sertifikat Pendidik / Certified IT Professional" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bidang Keahlian</label>
                    <input type="text" name="field" class="form-control" placeholder="Contoh: Pendidikan / Teknologi Informasi / Hukum">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tahun Terbit</label>
                    <input type="number" name="issue_year" class="form-control" placeholder="2021">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor Sertifikat</label>
                    <input type="text" name="certificate_number" class="form-control" placeholder="CERT-2021-998">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Memproses...' : 'Simpan Sertifikasi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 1. Modal Edit Pendidikan (Admin) -->
<div class="modal fade" id="modalEditEducation" tabindex="-1" aria-hidden="true" x-data="{
    editUrl: '',
    editForm: {},
    loading: false,
    async submitEdit(e) {
        e.preventDefault();
        this.loading = true;
        try {
            const formData = new FormData(e.target);
            const res = await fetch(this.editUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = e.target.closest('.modal') || this.$el;
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                window.dispatchEvent(new CustomEvent('history-updated', { detail: { message: data.message || 'Data pendidikan berhasil disimpan.' } }));
            } else { alert(data.message || 'Gagal menyimpan data.'); }
        } catch(err) { console.error(err); } finally { this.loading = false; }
    }
}" @open-edit-education.window="editUrl = $event.detail.url; editForm = Object.assign({}, $event.detail.data); bootstrap.Modal.getOrCreateInstance($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" @submit="submitEdit($event)">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-mortarboard-fill me-2"></i> Edit Riwayat Pendidikan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenjang <span class="text-danger">*</span></label>
                    <select name="level" class="form-select" x-model="editForm.level" required>
                        <option value="S1">S1 (Sarjana)</option>
                        <option value="S2">S2 (Magister)</option>
                        <option value="S3">S3 (Doktor)</option>
                        <option value="D3">D3 (Diploma)</option>
                        <option value="SMA/SMK">SMA / SMK / MA</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kampus / Sekolah <span class="text-danger">*</span></label>
                    <input type="text" name="institution_name" class="form-control" x-model="editForm.institution_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Prodi / Jurusan</label>
                    <input type="text" name="major" class="form-control" x-model="editForm.major">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Gelar Akademik</label>
                    <input type="text" name="degree" class="form-control" x-model="editForm.degree">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Masuk</label>
                        <input type="number" name="start_year" class="form-control" x-model="editForm.start_year">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Lulus</label>
                        <input type="number" name="end_year" class="form-control" x-model="editForm.end_year">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Edit Organisasi (Admin) -->
<div class="modal fade" id="modalEditOrganization" tabindex="-1" aria-hidden="true" x-data="{
    editUrl: '',
    editForm: {},
    loading: false,
    async submitEdit(e) {
        e.preventDefault();
        this.loading = true;
        try {
            const formData = new FormData(e.target);
            const res = await fetch(this.editUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = e.target.closest('.modal') || this.$el;
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                window.dispatchEvent(new CustomEvent('history-updated', { detail: { message: data.message || 'Data organisasi berhasil disimpan.' } }));
            } else { alert(data.message || 'Gagal menyimpan data.'); }
        } catch(err) { console.error(err); } finally { this.loading = false; }
    }
}" @open-edit-organization.window="editUrl = $event.detail.url; editForm = Object.assign({}, $event.detail.data); bootstrap.Modal.getOrCreateInstance($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" @submit="submitEdit($event)">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i> Edit Riwayat Organisasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Organisasi <span class="text-danger">*</span></label>
                    <input type="text" name="organization_name" class="form-control" x-model="editForm.organization_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan / Peran</label>
                    <input type="text" name="position" class="form-control" x-model="editForm.position">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Periode</label>
                    <input type="text" name="period" class="form-control" x-model="editForm.period">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Edit Pekerjaan (Admin) -->
<div class="modal fade" id="modalEditEmployment" tabindex="-1" aria-hidden="true" x-data="{
    editUrl: '',
    editForm: {},
    loading: false,
    async submitEdit(e) {
        e.preventDefault();
        this.loading = true;
        try {
            const formData = new FormData(e.target);
            const res = await fetch(this.editUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = e.target.closest('.modal') || this.$el;
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                window.dispatchEvent(new CustomEvent('history-updated', { detail: { message: data.message || 'Data pekerjaan berhasil disimpan.' } }));
            } else { alert(data.message || 'Gagal menyimpan data.'); }
        } catch(err) { console.error(err); } finally { this.loading = false; }
    }
}" @open-edit-employment.window="editUrl = $event.detail.url; editForm = Object.assign({}, $event.detail.data); bootstrap.Modal.getOrCreateInstance($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" @submit="submitEdit($event)">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-briefcase-fill me-2"></i> Edit Riwayat Pekerjaan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Instansi / Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" x-model="editForm.company_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jabatan / Peran <span class="text-danger">*</span></label>
                    <input type="text" name="position" class="form-control" x-model="editForm.position" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kategori / Bidang Pekerjaan</label>
                    <select name="employment_status" class="form-select" x-model="editForm.employment_status">
                        <option value="">-- Pilih Kategori Pekerjaan --</option>
                        <option value="ASN">ASN</option>
                        <option value="TNI/Polri">TNI/Polri</option>
                        <option value="BUMN">BUMN / BUMD</option>
                        <option value="Swasta">Swasta</option>
                        <option value="Profesional">Profesional</option>
                        <option value="Wirausaha">Wirausaha</option>
                        <option value="Akademisi">Akademisi / Dosen</option>
                        <option value="Tenaga Pendidik">Tenaga Pendidik / Guru</option>
                        <option value="Tenaga Kesehatan">Tenaga Kesehatan / Dokter</option>
                        <option value="Jurnalis">Jurnalis</option>
                        <option value="Aktivis LSM / NGO">Aktivis LSM / NGO</option>
                        <option value="Freelancer">Freelancer</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Mulai</label>
                        <input type="number" name="start_year" class="form-control" x-model="editForm.start_year">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Tahun Selesai</label>
                        <input type="number" name="end_year" class="form-control" x-model="editForm.end_year">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Edit Kaderisasi NU (Admin) -->
<div class="modal fade" id="modalEditNuTraining" tabindex="-1" aria-hidden="true" x-data="{
    editUrl: '',
    editForm: {},
    loading: false,
    async submitEdit(e) {
        e.preventDefault();
        this.loading = true;
        try {
            const formData = new FormData(e.target);
            const res = await fetch(this.editUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = e.target.closest('.modal') || this.$el;
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                window.dispatchEvent(new CustomEvent('history-updated', { detail: { message: data.message || 'Data kaderisasi berhasil disimpan.' } }));
            } else { alert(data.message || 'Gagal menyimpan data.'); }
        } catch(err) { console.error(err); } finally { this.loading = false; }
    }
}" @open-edit-nu-training.window="editUrl = $event.detail.url; editForm = Object.assign({}, $event.detail.data); bootstrap.Modal.getOrCreateInstance($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" @submit="submitEdit($event)">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-award-fill me-2"></i> Edit Kaderisasi NU</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kaderisasi <span class="text-danger">*</span></label>
                    <input type="text" name="training_type" class="form-control" x-model="editForm.training_type" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Penyelenggara</label>
                    <input type="text" name="organizer" class="form-control" x-model="editForm.organizer">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tahun Keikutsertaan</label>
                    <input type="number" name="year" class="form-control" x-model="editForm.year">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor Sertifikat / SK</label>
                    <input type="text" name="certificate_number" class="form-control" x-model="editForm.certificate_number">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal Edit Sertifikasi (Admin) -->
<div class="modal fade" id="modalEditCertification" tabindex="-1" aria-hidden="true" x-data="{
    editUrl: '',
    editForm: {},
    loading: false,
    async submitEdit(e) {
        e.preventDefault();
        this.loading = true;
        try {
            const formData = new FormData(e.target);
            const res = await fetch(this.editUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                const modalEl = e.target.closest('.modal') || this.$el;
                bootstrap.Modal.getOrCreateInstance(modalEl)?.hide();
                window.dispatchEvent(new CustomEvent('history-updated', { detail: { message: data.message || 'Data sertifikasi berhasil disimpan.' } }));
            } else { alert(data.message || 'Gagal menyimpan data.'); }
        } catch(err) { console.error(err); } finally { this.loading = false; }
    }
}" @open-edit-certification.window="editUrl = $event.detail.url; editForm = Object.assign({}, $event.detail.data); bootstrap.Modal.getOrCreateInstance($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <form :action="editUrl" method="POST" class="modal-content border-0 shadow-lg rounded-4" @submit="submitEdit($event)">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-patch-check-fill me-2"></i> Edit Sertifikasi Keahlian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Sertifikasi <span class="text-danger">*</span></label>
                    <input type="text" name="certification_name" class="form-control" x-model="editForm.certification_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Bidang Keahlian</label>
                    <input type="text" name="field" class="form-control" x-model="editForm.field">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tahun Terbit</label>
                    <input type="number" name="issue_year" class="form-control" x-model="editForm.issue_year">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor Sertifikat</label>
                    <input type="text" name="certificate_number" class="form-control" x-model="editForm.certificate_number">
                </div>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
