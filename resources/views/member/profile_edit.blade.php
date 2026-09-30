@extends('layouts.member')

@section('title', 'Edit Profil & Potensi')

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

<div @potensi-updated.window="addToast('Berhasil!', $event.detail.message); refreshContent()" x-data="{
    loading: false,
    toasts: [],
    deleteModal: null,
    deleteUrl: '',
    deleteTitle: '',
    editModal: null,
    editUrl: '',
    editType: '',
    editForm: {},
    successModal: null,
    successMessage: '',
    redirectUrl: '{{ route('member.dashboard') }}',

    openEdit(type, url, data) {
        this.editType = type;
        this.editUrl = url;
        this.editForm = Object.assign({}, data);
        if (!this.editModal) {
            this.editModal = new bootstrap.Modal(document.getElementById('modalEditPotensiItem'));
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

    confirmDelete(url, title = 'data ini') {
        this.deleteUrl = url;
        this.deleteTitle = title;
        if (!this.deleteModal) {
            this.deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDeletePotensi'));
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
                method: form.method || 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.successMessage = data.message || 'Profil Anda berhasil diperbarui.';
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

    redirectToDashboard() {
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
                method: form.method || 'POST',
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
                // Reset form inputs except CSRF token and hidden fields
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
            const newRightCol = doc.getElementById('potensiRightCol');
            if (newRightCol) {
                const target = document.getElementById('potensiRightCol');
                if (target) {
                    target.innerHTML = newRightCol.innerHTML;
                }
            }
        } catch (e) {
            console.error('Failed to refresh content', e);
        }
    }
}">

    <!-- Top Fixed Progress Bar -->
    <div class="progress rounded-0 position-fixed top-0 start-0 w-100" style="height: 4px; z-index: 99999;" x-show="loading" x-transition>
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
    </div>

    <!-- Toast Slideshow Notification Container Top Right -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999; pointer-events: none;">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast show align-items-center text-white border-0 shadow-lg mb-2 toast-slide-in"
                 :class="toast.type === 'error' ? 'bg-danger' : 'bg-success'"
                 style="pointer-events: auto; min-width: 300px; border-radius: 12px;"
                 role="alert">
                <div class="d-flex p-3 align-items-center">
                    <div class="me-3 fs-3">
                        <i class="bi" :class="toast.type === 'error' ? 'bi-x-circle-fill' : 'bi-check-circle-fill'"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold" x-text="toast.title"></div>
                        <div class="small text-white-50" x-text="toast.message"></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-1 m-auto" @click="removeToast(toast.id)"></button>
                </div>
            </div>
        </template>
    </div>

    <div class="row g-2 g-lg-4" :class="{ 'opacity-75 pointer-events-none': loading }">
        <!-- Main Profile Edit Form -->
        <div class="col-lg-7">
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4" x-init="initLocations()" x-data="{
                photoPreview: '{{ $member->photo ? asset('storage/'.$member->photo) : '' }}',
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

                onPhotoChange(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            this.photoPreview = event.target.result;
                        };
                        reader.readAsDataURL(file);
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-gear text-success me-2"></i> Edit Biodata Utama</h5>
                    <a href="{{ route('member.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <form action="{{ route('member.profile.update') }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitBiodata($event)">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Lengkap & Gelar</label>
                            <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $member->full_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pekerjaan / Profesi Saat Ini</label>
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $member->birth_place) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $member->birth_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select name="gender" class="form-select" required>
                                <option value="L" {{ $member->gender === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="P" {{ $member->gender === 'P' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat Rumah Lengkap <span class="text-secondary small">(Jalan, RT/RW, No. Rumah)</span></label>
                            <textarea name="address" class="form-control" rows="2" required>{{ old('address', $member->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Provinsi</label>
                            <input type="hidden" name="province" :value="provinceName">
                            <select x-model="selectedProvinceCode" @change="onProvinceChange()" class="form-select" required>
                                <option value="">-- Pilih Provinsi --</option>
                                <template x-for="p in provinces" :key="p.code">
                                    <option :value="p.code" x-text="p.name" :selected="p.code === selectedProvinceCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kota / Kabupaten</label>
                            <input type="hidden" name="city" :value="cityName">
                            <select x-model="selectedCityCode" @change="onCityChange()" :disabled="!selectedProvinceCode" class="form-select" required>
                                <option value="">-- Pilih Kota / Kabupaten --</option>
                                <template x-for="c in cities" :key="c.code">
                                    <option :value="c.code" x-text="c.name" :selected="c.code === selectedCityCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kecamatan</label>
                            <input type="hidden" name="kecamatan" :value="kecamatanName">
                            <select x-model="selectedKecamatanCode" @change="onKecamatanChange()" :disabled="!selectedCityCode" class="form-select" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                <template x-for="k in kecamatans" :key="k.code">
                                    <option :value="k.code" x-text="k.name" :selected="k.code === selectedKecamatanCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelurahan</label>
                            <input type="hidden" name="kelurahan" :value="kelurahanName">
                            <select x-model="selectedKelurahanCode" @change="onKelurahanChange()" :disabled="!selectedKecamatanCode" class="form-select" required>
                                <option value="">-- Pilih Kelurahan --</option>
                                <template x-for="l in kelurahans" :key="l.code">
                                    <option :value="l.code" x-text="l.name" :selected="l.code === selectedKelurahanCode"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Foto Profil (Pas foto formal)</label>
                            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                                <div class="position-relative flex-shrink-0">
                                    <template x-if="photoPreview">
                                        <img :src="photoPreview" alt="Preview Foto" class="rounded-3 shadow-sm object-fit-cover" style="width: 80px; height: 100px; border: 2px solid #198754;">
                                    </template>
                                    <template x-if="!photoPreview">
                                        <div class="bg-white rounded-3 d-flex flex-column align-items-center justify-content-center text-muted shadow-sm" style="width: 80px; height: 100px; border: 2px dashed #ced4da;">
                                            <i class="bi bi-person-bounding-box fs-3 text-secondary"></i>
                                            <span class="extra-small mt-1 text-center text-secondary" style="font-size: 10px;">Belum Ada</span>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="photo" @change="onPhotoChange($event)" class="form-control form-control-sm mb-1" accept="image/jpeg,image/png">
                                    <div class="form-text extra-small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Format: JPG, JPEG, PNG (Maks 2MB). Pilih file untuk langsung melihat pratinjau foto.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-isnu px-4 rounded-pill fw-bold" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-save me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menyimpan...' : 'Simpan Perubahan Profil'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card Pengaturan Keamanan Akun / Ubah Password -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h5 class="fw-bold text-success border-bottom pb-2 mb-3">
                    <i class="bi bi-shield-lock-fill me-2"></i> Pengaturan Akun & Keamanan
                </h5>

                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="row g-2 text-dark small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Email Akun:</span>
                            <strong class="text-break">{{ auth()->user()->email }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block">Nomor HP / WhatsApp:</span>
                            <strong>{{ auth()->user()->phone ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">
                    <i class="bi bi-key-fill text-warning me-1"></i> Ubah Password Akun
                </h6>
                <p class="small text-muted mb-3">
                    Gunakan password minimal 8 karakter agar akun Anda tetap aman.
                </p>

                <form action="{{ route('member.profile.change_password') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control form-control-sm" placeholder="Masukkan password saat ini" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Minimal 8 karakter" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Ulangi password baru" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark px-4 shadow-sm" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-shield-check me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Memperbarui...' : 'Simpan Password Baru'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Add Potensi Items -->
        <div class="col-lg-5" id="potensiRightCol">
            <!-- Add Education -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-success me-1"></i> Tambah Riwayat Pendidikan</h6>
                <form action="{{ route('member.profile.education.add') }}" method="POST" @submit.prevent="handleAjaxFormSubmit($event)">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <select name="level" class="form-select form-select-sm" required>
                                <option value="S1">S1 (Sarjana)</option>
                                <option value="S2">S2 (Magister)</option>
                                <option value="S3">S3 (Doktor)</option>
                                <option value="Diploma">Diploma</option>
                                <option value="SMA/SMK/MA">SMA/SMK/MA</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="text" name="institution_name" class="form-control form-control-sm" placeholder="Nama Kampus / Sekolah" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="major" class="form-control form-control-sm" placeholder="Prodi / Jurusan">
                        </div>
                        <div class="col-6">
                            <input type="text" name="degree" class="form-control form-control-sm" placeholder="Gelar (misal: S.T.)">
                        </div>
                        <div class="col-6">
                            <input type="number" name="start_year" class="form-control form-control-sm" placeholder="Tahun Masuk (2018)">
                        </div>
                        <div class="col-6">
                            <input type="number" name="end_year" class="form-control form-control-sm" placeholder="Tahun Lulus (2022)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <span x-text="loading ? 'Memproses...' : '+ Tambah Pendidikan'"></span>
                    </button>
                </form>

                <div class="mt-3 border-top pt-2">
                    @foreach($member->educations as $edu)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <div>
                                <strong>{{ $edu->level }}</strong> {{ $edu->major }} ({{ $edu->institution_name }})
                                @if($edu->degree) <span class="badge bg-light text-dark border">{{ $edu->degree }}</span> @endif
                                @if($edu->start_year || $edu->end_year)
                                    <div class="text-muted extra-small"><i class="bi bi-calendar-range me-1"></i>{{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }}</div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-link text-primary p-0 border-0 ms-2" title="Edit" @click='$dispatch("open-edit-education", { url: "{{ route('member.profile.education.update', $edu->id) }}", data: {{ json_encode($edu) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 border-0 ms-1" title="Hapus" @click="confirmDelete('{{ route('member.profile.education.delete', $edu->id) }}', 'Pendidikan {{ $edu->level }} {{ $edu->institution_name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Add Organization -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-success me-1"></i> Tambah Riwayat Organisasi</h6>
                <form action="{{ route('member.profile.organization.add') }}" method="POST" @submit.prevent="handleAjaxFormSubmit($event)">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <input type="text" name="organization_name" class="form-control form-control-sm" placeholder="Nama Organisasi" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="position" class="form-control form-control-sm" placeholder="Peran / Jabatan">
                        </div>
                        <div class="col-6">
                            <input type="text" name="period" class="form-control form-control-sm" placeholder="Periode (misal: 2020-2022)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <span x-text="loading ? 'Memproses...' : '+ Tambah Organisasi'"></span>
                    </button>
                </form>
                <div class="mt-3 border-top pt-2">
                    @foreach($member->organizations as $org)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <div>
                                <strong>{{ $org->organization_name }}</strong>
                                @if($org->position) - {{ $org->position }} @endif
                                @if($org->period) <span class="text-muted ms-1">({{ $org->period }})</span> @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-link text-primary p-0 border-0 ms-2" title="Edit" @click='$dispatch("open-edit-organization", { url: "{{ route('member.profile.organization.update', $org->id) }}", data: {{ json_encode($org) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 border-0 ms-1" title="Hapus" @click="confirmDelete('{{ route('member.profile.organization.delete', $org->id) }}', 'Organisasi {{ $org->organization_name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Add Employment -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase-fill text-success me-1"></i> Tambah Riwayat Pekerjaan</h6>
                <form action="{{ route('member.profile.employment.add') }}" method="POST" @submit.prevent="handleAjaxFormSubmit($event)">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="company_name" class="form-control form-control-sm" placeholder="Nama Pekerjaan / Instansi" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="position" class="form-control form-control-sm" placeholder="Peran / Jabatan" required>
                        </div>
                        <div class="col-12">
                            <select name="employment_status" class="form-select form-select-sm">
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
                        <div class="col-6">
                            <input type="number" name="start_year" class="form-control form-control-sm" placeholder="Tahun Masuk (2020)">
                        </div>
                        <div class="col-6">
                            <input type="number" name="end_year" class="form-control form-control-sm" placeholder="Tahun Keluar (2023)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <span x-text="loading ? 'Memproses...' : '+ Tambah Pekerjaan'"></span>
                    </button>
                </form>
                <div class="mt-3 border-top pt-2">
                    @foreach($member->employments as $emp)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <div>
                                @if($emp->employment_status)
                                    <span class="badge bg-secondary me-1">{{ $emp->employment_status }}</span>
                                @endif
                                <strong>{{ $emp->company_name }}</strong> - {{ $emp->position }}
                                @if($emp->start_year || $emp->end_year)
                                    <div class="text-muted extra-small"><i class="bi bi-calendar-range me-1"></i>{{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }}</div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-link text-primary p-0 border-0 ms-2" title="Edit" @click='$dispatch("open-edit-employment", { url: "{{ route('member.profile.employment.update', $emp->id) }}", data: {{ json_encode($emp) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 border-0 ms-1" title="Hapus" @click="confirmDelete('{{ route('member.profile.employment.delete', $emp->id) }}', 'Pekerjaan {{ $emp->company_name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Add NU Training -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-award-fill text-success me-1"></i> Tambah Kaderisasi NU</h6>
                <form action="{{ route('member.profile.nu_training.add') }}" method="POST" @submit.prevent="handleAjaxFormSubmit($event)">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="training_type" class="form-control form-control-sm" placeholder="Nama Kaderisasi (mis: MAKESTA)" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="organizer" class="form-control form-control-sm" placeholder="Penyelenggara">
                        </div>
                        <div class="col-6">
                            <input type="text" name="certificate_number" class="form-control form-control-sm" placeholder="No Sertifikat">
                        </div>
                        <div class="col-6">
                            <input type="number" name="year" class="form-control form-control-sm" placeholder="Tahun (mis: 2021)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <span x-text="loading ? 'Memproses...' : '+ Tambah Kaderisasi'"></span>
                    </button>
                </form>
                <div class="mt-3 border-top pt-2">
                    @foreach($member->nuTrainings as $nu)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <div>
                                <strong>{{ $nu->training_type }}</strong>
                                @if($nu->organizer) - {{ $nu->organizer }} @endif
                                @if($nu->year) <span class="text-muted">({{ $nu->year }})</span> @endif
                                @if($nu->certificate_number)
                                    <div class="text-muted extra-small">No. Sertifikat: {{ $nu->certificate_number }}</div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-link text-primary p-0 border-0 ms-2" title="Edit" @click='$dispatch("open-edit-nu-training", { url: "{{ route('member.profile.nu_training.update', $nu->id) }}", data: {{ json_encode($nu) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 border-0 ms-1" title="Hapus" @click="confirmDelete('{{ route('member.profile.nu_training.delete', $nu->id) }}', 'Kaderisasi {{ $nu->training_type }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Add Certification -->
            <div class="card card-custom p-3 p-md-4 mb-3 mb-lg-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-patch-check-fill text-success me-1"></i> Tambah Sertifikasi Keahlian</h6>
                <form action="{{ route('member.profile.certification.add') }}" method="POST" @submit.prevent="handleAjaxFormSubmit($event)">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="text" name="certification_name" class="form-control form-control-sm" placeholder="Nama Sertifikasi" required>
                        </div>
                        <div class="col-6">
                            <input type="text" name="field" class="form-control form-control-sm" placeholder="Bidang Keahlian">
                        </div>
                        <div class="col-6">
                            <input type="text" name="certificate_number" class="form-control form-control-sm" placeholder="No Sertifikat">
                        </div>
                        <div class="col-6">
                            <input type="number" name="issue_year" class="form-control form-control-sm" placeholder="Tahun Perolehan">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" :disabled="loading">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <span x-text="loading ? 'Memproses...' : '+ Tambah Sertifikasi'"></span>
                    </button>
                </form>
                <div class="mt-3 border-top pt-2">
                    @foreach($member->certifications as $cert)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <div>
                                <strong>{{ $cert->certification_name }}</strong>
                                @if($cert->field) ({{ $cert->field }}) @endif
                                @if($cert->issue_year) <span class="text-muted">- {{ $cert->issue_year }}</span> @endif
                                @if($cert->certificate_number)
                                    <div class="text-muted extra-small">No. Sertifikat: {{ $cert->certificate_number }}</div>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-link text-primary p-0 border-0 ms-2" title="Edit" @click='$dispatch("open-edit-certification", { url: "{{ route('member.profile.certification.update', $cert->id) }}", data: {{ json_encode($cert) }} })'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0 border-0 ms-1" title="Hapus" @click="confirmDelete('{{ route('member.profile.certification.delete', $cert->id) }}', 'Sertifikasi {{ $cert->certification_name }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Success Popup Window for Profile Submit -->
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
                    <h5 class="fw-bold text-dark mb-2">Pembaruan Profil Berhasil</h5>
                    <p class="text-muted mb-0" x-text="successMessage"></p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center p-3">
                    <button type="button" class="btn btn-success fw-bold rounded-pill px-5 py-2 shadow-sm fs-6" data-bs-dismiss="modal" @click="redirectToDashboard()" onclick="window.location.href='{{ route('member.dashboard') }}'">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Data Potensi -->
    <div class="modal fade" id="modalConfirmDeletePotensi" tabindex="-1" aria-hidden="true">
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
                        Apakah Anda yakin ingin menghapus <strong x-text="deleteTitle" class="text-dark"></strong>? 
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" :disabled="loading" @click="executeDelete()">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                        <i class="bi bi-trash me-1" x-show="!loading"></i>
                        <span x-text="loading ? 'Menghapus...' : 'Ya, Hapus Data'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Modal Edit Pendidikan -->
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
                    window.dispatchEvent(new CustomEvent('potensi-updated', { detail: { message: data.message || 'Data pendidikan berhasil disimpan.' } }));
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
                            <option value="Diploma">Diploma</option>
                            <option value="SMA/SMK/MA">SMA/SMK/MA</option>
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

    <!-- 2. Modal Edit Organisasi -->
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
                    window.dispatchEvent(new CustomEvent('potensi-updated', { detail: { message: data.message || 'Data organisasi berhasil disimpan.' } }));
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

    <!-- 3. Modal Edit Pekerjaan -->
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
                    window.dispatchEvent(new CustomEvent('potensi-updated', { detail: { message: data.message || 'Data pekerjaan berhasil disimpan.' } }));
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
                        <label class="form-label fw-semibold">Nama Pekerjaan / Instansi <span class="text-danger">*</span></label>
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
                            <label class="form-label fw-semibold">Tahun Masuk</label>
                            <input type="number" name="start_year" class="form-control" x-model="editForm.start_year">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Tahun Keluar</label>
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

    <!-- 4. Modal Edit Kaderisasi NU -->
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
                    window.dispatchEvent(new CustomEvent('potensi-updated', { detail: { message: data.message || 'Data kaderisasi berhasil disimpan.' } }));
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

    <!-- 5. Modal Edit Sertifikasi -->
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
                    window.dispatchEvent(new CustomEvent('potensi-updated', { detail: { message: data.message || 'Data sertifikasi berhasil disimpan.' } }));
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
                        <label class="form-label fw-semibold">Tahun Perolehan</label>
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
