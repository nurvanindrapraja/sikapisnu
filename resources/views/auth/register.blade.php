@extends('layouts.app')

@section('title', 'Pendaftaran Anggota Baru')

@php
    $initialStep = 1;
    if (isset($errors) && $errors->any()) {
        if ($errors->has('nik') || $errors->has('gender') || $errors->has('birth_place') || $errors->has('birth_date') || $errors->has('address') || $errors->has('kelurahan') || $errors->has('kecamatan') || $errors->has('occupation') || $errors->has('photo')) {
            $initialStep = 2;
        } elseif ($errors->has('education_level') || $errors->has('employment_company') || $errors->has('nu_training') || $errors->has('certification_name')) {
            $initialStep = 3;
        }
    }
@endphp

@section('content')
<div class="bg-success text-white py-4">
    <div class="container text-center">
        <h2 class="fw-bold m-0"><i class="bi bi-person-plus-fill me-2"></i> Pendaftaran Anggota ISNU Kota Surabaya</h2>
        <p class="mb-0 text-white-50">Lengkapi formulir di bawah ini untuk menjadi anggota terverifikasi</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" x-init="initLocations()" x-data="{
                step: {{ $initialStep }},
                loading: false,
                stepError: '',
                emailError: '',
                isCheckingEmail: false,
                photoPreview: null,

                // Cascading Location Properties
                provinces: [],
                cities: [],
                kecamatans: [],
                kelurahans: [],
                selectedProvinceCode: '',
                provinceName: '{{ old('province', 'JAWA TIMUR') }}',
                selectedCityCode: '',
                cityName: '{{ old('city', 'KOTA SURABAYA') }}',
                selectedKecamatanCode: '',
                kecamatanName: '{{ old('kecamatan', '') }}',
                selectedKelurahanCode: '',
                kelurahanName: '{{ old('kelurahan', '') }}',

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
                },

                async checkEmailLive() {
                    const emailEl = this.$refs.emailInput;
                    if (!emailEl) return;
                    const email = emailEl.value.trim();
                    if (!email) return;

                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) return;

                    try {
                        const response = await fetch('{{ route('check.email') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: email })
                        });
                        const data = await response.json();
                        if (!data.valid) {
                            this.emailError = data.message;
                            this.stepError = data.message;
                        } else {
                            if (this.stepError === this.emailError) {
                                this.stepError = '';
                            }
                            this.emailError = '';
                        }
                    } catch (e) {
                        console.warn('Check email error:', e);
                    }
                },

                async validateStep1() {
                    this.stepError = '';
                    this.emailError = '';

                    const name = this.$refs.nameInput ? this.$refs.nameInput.value.trim() : '';
                    const email = this.$refs.emailInput ? this.$refs.emailInput.value.trim() : '';
                    const phone = this.$refs.phoneInput ? this.$refs.phoneInput.value.trim() : '';
                    const password = this.$refs.passwordInput ? this.$refs.passwordInput.value : '';
                    const passConf = this.$refs.passConfInput ? this.$refs.passConfInput.value : '';

                    if (!name || !email || !phone || !password || !passConf) {
                        this.stepError = 'Mohon lengkapi seluruh entrian yang bertanda bintang (*) pada Informasi Akun terlebih dahulu.';
                        const firstEmpty = [this.$refs.nameInput, this.$refs.emailInput, this.$refs.phoneInput, this.$refs.passwordInput, this.$refs.passConfInput].find(el => el && !el.value.trim());
                        if (firstEmpty) {
                            firstEmpty.focus();
                            firstEmpty.reportValidity();
                        }
                        return;
                    }

                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        this.stepError = 'Format email yang Anda masukkan tidak valid.';
                        this.$refs.emailInput.focus();
                        this.$refs.emailInput.reportValidity();
                        return;
                    }

                    if (password.length < 8) {
                        this.stepError = 'Password minimal harus 8 karakter.';
                        this.$refs.passwordInput.focus();
                        return;
                    }

                    if (password !== passConf) {
                        this.stepError = 'Konfirmasi password tidak cocok dengan password yang Anda ketik.';
                        this.$refs.passConfInput.focus();
                        return;
                    }

                    this.isCheckingEmail = true;
                    try {
                        const response = await fetch('{{ route('check.email') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: email })
                        });
                        const data = await response.json();
                        this.isCheckingEmail = false;

                        if (!data.valid) {
                            this.stepError = data.message;
                            this.emailError = data.message;
                            this.$refs.emailInput.focus();
                            return;
                        }
                    } catch (e) {
                        this.isCheckingEmail = false;
                    }

                    this.stepError = '';
                    this.emailError = '';
                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                validateStep2() {
                    this.stepError = '';
                    const bPlace = this.$refs.birthPlaceInput ? this.$refs.birthPlaceInput.value.trim() : '';
                    const bDate = this.$refs.birthDateInput ? this.$refs.birthDateInput.value.trim() : '';
                    const address = this.$refs.addressInput ? this.$refs.addressInput.value.trim() : '';
                    const province = this.provinceName.trim();
                    const city = this.cityName.trim();
                    const kecamatan = this.kecamatanName.trim();
                    const kelurahan = this.kelurahanName.trim();
                    const occupation = this.$refs.occupationInput ? this.$refs.occupationInput.value.trim() : '';

                    if (!bPlace || !bDate || !address || !province || !city || !kecamatan || !kelurahan || !occupation) {
                        this.stepError = 'Mohon lengkapi seluruh entrian yang bertanda bintang (*) pada Biodata terlebih dahulu.';
                        const firstEmpty = [this.$refs.birthPlaceInput, this.$refs.birthDateInput, this.$refs.addressInput, this.$refs.occupationInput].find(el => el && !el.value.trim());
                        if (firstEmpty) {
                            firstEmpty.focus();
                            firstEmpty.reportValidity();
                        }
                        return;
                    }

                    this.stepError = '';
                    this.step = 3;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                validateStep3() {
                    this.stepError = '';
                    const eduLevel = this.$refs.eduLevelInput ? this.$refs.eduLevelInput.value.trim() : '';
                    const eduInst = this.$refs.eduInstInput ? this.$refs.eduInstInput.value.trim() : '';
                    const eduMajor = this.$refs.eduMajorInput ? this.$refs.eduMajorInput.value.trim() : '';

                    if (!eduLevel || !eduInst || !eduMajor) {
                        this.stepError = 'Mohon lengkapi data Pendidikan Terakhir (Jenjang, Nama Kampus/Institusi, dan Program Studi/Jurusan) terlebih dahulu.';
                        const firstEmpty = [this.$refs.eduLevelInput, this.$refs.eduInstInput, this.$refs.eduMajorInput].find(el => el && !el.value.trim());
                        if (firstEmpty) {
                            firstEmpty.focus();
                            firstEmpty.reportValidity();
                        }
                        return false;
                    }
                    return true;
                },

                handlePhotoChange(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        this.photoPreview = null;
                        return;
                    }

                    const MAX_WIDTH = 1200;
                    const MAX_HEIGHT = 1200;
                    const QUALITY = 0.82;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;

                            if (width > height) {
                                if (width > MAX_WIDTH) {
                                    height *= MAX_WIDTH / width;
                                    width = MAX_WIDTH;
                                }
                            } else {
                                if (height > MAX_HEIGHT) {
                                    width *= MAX_HEIGHT / height;
                                    height = MAX_HEIGHT;
                                }
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;

                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            const compressedDataUrl = canvas.toDataURL('image/jpeg', QUALITY);
                            this.photoPreview = compressedDataUrl;

                            canvas.toBlob((blob) => {
                                if (blob && event.target) {
                                    try {
                                        const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', {
                                            type: 'image/jpeg',
                                            lastModified: Date.now()
                                        });
                                        const dataTransfer = new DataTransfer();
                                        dataTransfer.items.add(compressedFile);
                                        event.target.files = dataTransfer.files;
                                    } catch (err) {
                                        console.warn('File replacement not supported:', err);
                                    }
                                }
                            }, 'image/jpeg', QUALITY);
                        };
                        img.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }">
                
                <!-- Step Indicator Header -->
                <div class="card-header bg-light border-0 p-3 p-md-4">
                    <div class="position-relative">
                        <!-- Connecting Line Background -->
                        <div class="position-absolute top-0 start-0 w-100 d-flex align-items-center" style="height: 36px; padding: 0 16%; z-index: 1;">
                            <div class="w-100 bg-secondary-subtle" style="height: 3px; border-radius: 2px;">
                                <div class="bg-success transition-all" style="height: 3px; border-radius: 2px;" :style="'width: ' + ((step - 1) / 2 * 100) + '%'"></div>
                            </div>
                        </div>

                        <!-- Step Circles & Labels -->
                        <div class="d-flex justify-content-around text-center position-relative" style="z-index: 2;">
                            <div class="flex-fill d-flex flex-column align-items-center" :class="{ 'text-success fw-bold': step >= 1, 'text-muted': step < 1 }">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 shadow-sm transition-all"
                                     :class="step >= 1 ? 'bg-success text-white' : 'bg-secondary text-white'"
                                     style="width: 36px; height: 36px; font-weight: 700; font-size: 0.95rem;">
                                    1
                                </div>
                                <div class="small text-nowrap">1. Akun</div>
                            </div>
                            <div class="flex-fill d-flex flex-column align-items-center" :class="{ 'text-success fw-bold': step >= 2, 'text-muted': step < 2 }">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 shadow-sm transition-all"
                                     :class="step >= 2 ? 'bg-success text-white' : 'bg-secondary text-white'"
                                     style="width: 36px; height: 36px; font-weight: 700; font-size: 0.95rem;">
                                    2
                                </div>
                                <div class="small text-nowrap">2. Biodata</div>
                            </div>
                            <div class="flex-fill d-flex flex-column align-items-center" :class="{ 'text-success fw-bold': step >= 3, 'text-muted': step < 3 }">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 shadow-sm transition-all"
                                     :class="step >= 3 ? 'bg-success text-white' : 'bg-secondary text-white'"
                                     style="width: 36px; height: 36px; font-weight: 700; font-size: 0.95rem;">
                                    3
                                </div>
                                <div class="small text-nowrap">3. Potensi</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if($errors->any())
                        <div class="alert alert-danger rounded-3 small mb-4">
                            <strong class="d-block mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Mohon perbaiki kesalahan berikut:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST" enctype="multipart/form-data" @submit="if (!validateStep3()) { $event.preventDefault(); loading = false; } else { loading = true; }" @keydown.enter.prevent="if (step === 1) { validateStep1(); } else if (step === 2) { validateStep2(); }">
                        @csrf

                        <!-- STEP 1: AKUN LOGIN -->
                        <div x-show="step === 1" data-step="1" class="space-y-4">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-4">
                                <i class="bi bi-key-fill me-2"></i> Langkah 1: Informasi Akun
                            </h5>

                            <div x-show="isCheckingEmail" class="progress mb-3" style="height: 4px;" x-cloak>
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
                            </div>

                            <template x-if="stepError">
                                <div class="alert alert-danger rounded-3 small mb-4 shadow-sm border-danger border-start border-4">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                                        <span x-text="stepError" class="fw-semibold"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                    <input type="text" name="name" x-ref="nameInput" @input="stepError = ''" class="form-control" placeholder="Contoh: Dr. Ahmad Husein, M.Si." value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email Aktif <span class="text-danger">*</span></label>
                                    <input type="email" name="email" x-ref="emailInput" @blur="checkEmailLive()" @input="stepError = ''; emailError = ''" class="form-control" :class="{ 'is-invalid': emailError }" placeholder="email@contoh.com" value="{{ old('email') }}" required>
                                    <div class="text-danger small mt-1 fw-semibold" x-text="emailError" x-show="emailError"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" x-ref="phoneInput" @input="stepError = ''" class="form-control" placeholder="081234567890" value="{{ old('phone') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password" x-ref="passwordInput" @input="stepError = ''" class="form-control" placeholder="Minimal 8 karakter" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password_confirmation" x-ref="passConfInput" @input="stepError = ''" class="form-control" placeholder="Ulangi password" required>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top text-end">
                                <button type="button" class="btn btn-sm btn-isnu-primary px-3 px-md-4 py-2 rounded-pill d-inline-flex align-items-center justify-content-center" @click="validateStep1()" :disabled="isCheckingEmail">
                                    <span x-show="!isCheckingEmail" class="d-inline-flex align-items-center">Lanjut ke Biodata <i class="bi bi-arrow-right ms-1"></i></span>
                                    <span x-show="isCheckingEmail" class="d-inline-flex align-items-center" x-cloak>
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Memeriksa Email...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 2: BIODATA UTAMA -->
                        <div x-show="step === 2" data-step="2" class="space-y-4" style="display: none;">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-4">
                                <i class="bi bi-person-vcard-fill me-2"></i> Langkah 2: Biodata Identitas Utama
                            </h5>

                            <template x-if="stepError">
                                <div class="alert alert-danger rounded-3 small mb-4 shadow-sm border-danger border-start border-4">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                                        <span x-text="stepError" class="fw-semibold"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">NIK (Nomor Induk Kependudukan) <span class="text-secondary small">(Opsional)</span></label>
                                    <input type="text" name="nik" class="form-control" placeholder="16 Digit NIK KTP (Opsional)" maxlength="16" value="{{ old('nik') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select" required>
                                        <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tempat Lahir <span class="text-danger">*</span></label>
                                    <input type="text" name="birth_place" x-ref="birthPlaceInput" @input="stepError = ''" class="form-control" placeholder="Surabaya" value="{{ old('birth_place') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" name="birth_date" x-ref="birthDateInput" @input="stepError = ''" class="form-control" value="{{ old('birth_date') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Alamat Rumah Lengkap <span class="text-secondary small">(Jalan, RT/RW, No. Rumah)</span> <span class="text-danger">*</span></label>
                                    <textarea name="address" x-ref="addressInput" @input="stepError = ''" class="form-control" rows="2" placeholder="Jl. Darmo No. 12, RT 01 RW 02..." required>{{ old('address') }}</textarea>
                                </div>
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
                                    <label class="form-label fw-semibold">Pekerjaan / Aktivitas Saat Ini <span class="text-danger">*</span></label>
                                    <input type="text" name="occupation" x-ref="occupationInput" @input="stepError = ''" class="form-control" list="occupationList" placeholder="Contoh: Dosen, Dokter, Jurnalis, Aktivis NGO / LSM, ASN..." value="{{ old('occupation') }}" required>
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
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Foto Profil Resmi <span class="text-secondary small">(Pas foto formal)</span></label>
                                    <div class="d-flex align-items-center gap-3">
                                        <template x-if="photoPreview">
                                            <div class="position-relative flex-shrink-0">
                                                <img :src="photoPreview" class="rounded-circle object-fit-cover border border-2 border-success shadow-sm" style="width: 72px; height: 72px;" alt="Preview Foto">
                                                <button type="button" @click="photoPreview = null; $refs.photoInput.value = ''" class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;" title="Hapus foto">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!photoPreview">
                                            <div class="bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center border flex-shrink-0" style="width: 72px; height: 72px;">
                                                <i class="bi bi-person-fill fs-3"></i>
                                            </div>
                                        </template>
                                        <div class="flex-grow-1">
                                            <input type="file" name="photo" x-ref="photoInput" @change="handlePhotoChange($event)" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                                            <div class="form-text small">Pilih foto (JPG/PNG, Maks 2MB). Pratinjau foto akan muncul otomatis.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 px-md-4 py-2 rounded-pill" @click="step = 1; stepError = ''">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-sm btn-isnu-primary px-3 px-md-4 py-2 rounded-pill" @click="validateStep2()">
                                    Lanjut ke Potensi <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: POTENSI & REKAM JEJAK -->
                        <div x-show="step === 3" class="space-y-4" style="display: none;">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-4">
                                <i class="bi bi-briefcase-fill me-2"></i> Langkah 3: Potensi & Rekam Jejak
                            </h5>

                            <div x-show="loading" class="progress mb-3" style="height: 4px;" x-cloak>
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 100%"></div>
                            </div>

                            <template x-if="stepError">
                                <div class="alert alert-danger rounded-3 small mb-4 shadow-sm border-danger border-start border-4">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                                        <span x-text="stepError" class="fw-semibold"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="row g-3">
                                <!-- Pendidikan Terakhir -->
                                <div class="col-12"><h6 class="fw-bold text-dark mb-0"><i class="bi bi-mortarboard-fill text-success me-1"></i> Pendidikan Terakhir <span class="text-danger small">* (Wajib)</span></h6></div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Jenjang <span class="text-danger">*</span></label>
                                    <select name="education_level" x-ref="eduLevelInput" class="form-select form-select-sm" required>
                                        <option value="">-- Pilih Jenjang --</option>
                                        <option value="S1">S1 (Sarjana)</option>
                                        <option value="S2">S2 (Magister)</option>
                                        <option value="S3">S3 (Doktor)</option>
                                        <option value="Diploma">Diploma</option>
                                        <option value="SMA/SMK/MA">SMA/SMK/MA</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Nama Kampus / Institusi <span class="text-danger">*</span></label>
                                    <input type="text" name="education_institution" x-ref="eduInstInput" class="form-control form-control-sm" placeholder="Universitas Airlangga, ITS, UIN..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Program Studi / Jurusan <span class="text-danger">*</span></label>
                                    <input type="text" name="education_major" x-ref="eduMajorInput" class="form-control form-control-sm" placeholder="Teknik Informatika, Hukum..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Periode (Tahun Masuk - Lulus)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="education_start_year" class="form-control" placeholder="Masuk" min="1950" max="2030">
                                        <span class="input-group-text">-</span>
                                        <input type="number" name="education_end_year" class="form-control" placeholder="Lulus" min="1950" max="2030">
                                    </div>
                                </div>

                                <!-- Instansi / Tempat Kerja Terakhir -->
                                <div class="col-12 mt-4"><h6 class="fw-bold text-dark mb-0"><i class="bi bi-building-fill text-success me-1"></i> Instansi / Tempat Kerja Terakhir</h6></div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Nama Instansi / Perusahaan</label>
                                    <input type="text" name="employment_company" class="form-control form-control-sm" placeholder="PT / Instansi / Sekolah">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Jabatan / Posisi</label>
                                    <input type="text" name="employment_position" class="form-control form-control-sm" placeholder="Dosen, Manager, Dokter...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Kategori Pekerjaan</label>
                                    <select name="employment_status" class="form-select form-select-sm">
                                        <option value="">-- Kategori Pekerjaan --</option>
                                        <option value="ASN">ASN</option>
                                        <option value="TNI/Polri">TNI/Polri</option>
                                        <option value="BUMN">BUMN / BUMD</option>
                                        <option value="Swasta">Swasta</option>
                                        <option value="Profesional">Profesional</option>
                                        <option value="Wirausaha">Wirausaha</option>
                                        <option value="Akademisi">Akademisi / Dosen</option>
                                        <option value="Tenaga Pendidik">Tenaga Pendidik / Guru</option>
                                        <option value="Tenaga Kesehatan">Tenaga Kesehatan / Dokter</option>
                                        <option value="Freelancer">Freelancer</option>
                                        <option value="Jurnalis">Jurnalis</option>
                                        <option value="Aktivis LSM / NGO">Aktivis LSM / NGO</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Tahun Masuk Kerja</label>
                                    <input type="number" name="employment_start_year" class="form-control form-control-sm" placeholder="Contoh: 2020" min="1950" max="2030">
                                </div>

                                <!-- Kaderisasi NU Terakhir -->
                                <div class="col-12 mt-4"><h6 class="fw-bold text-dark mb-0"><i class="bi bi-award-fill text-success me-1"></i> Kaderisasi NU Terakhir</h6></div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Jenis Kaderisasi NU</label>
                                    <input type="text" name="nu_training" class="form-control form-control-sm" placeholder="Contoh: MKISNU, PDPKP, PMKNU" value="{{ old('nu_training') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Penyelenggara</label>
                                    <input type="text" name="nu_organizer" class="form-control form-control-sm" placeholder="Contoh: PCNU Surabaya, PP ISNU">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Tahun Pelaksanaan</label>
                                    <input type="number" name="nu_year" class="form-control form-control-sm" placeholder="Contoh: 2022" min="1950" max="2030">
                                </div>

                                <!-- Sertifikasi Keahlian Utama -->
                                <div class="col-12 mt-4"><h6 class="fw-bold text-dark mb-0"><i class="bi bi-patch-check-fill text-success me-1"></i> Sertifikasi Keahlian Utama</h6></div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Nama Sertifikasi</label>
                                    <input type="text" name="certification_name" class="form-control form-control-sm" placeholder="Contoh: Sertifikasi Dosen, Advokat...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Bidang Keahlian</label>
                                    <input type="text" name="certification_field" class="form-control form-control-sm" placeholder="Contoh: Hukum, Kedokteran, IT...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Tahun Terbit</label>
                                    <input type="number" name="certification_year" class="form-control form-control-sm" placeholder="Contoh: 2023" min="1950" max="2030">
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 px-md-4 py-2 rounded-pill" @click="step = 2">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="submit" class="btn btn-sm btn-warning fw-bold px-3 px-md-4 py-2 rounded-pill text-dark shadow-sm" :disabled="loading">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                                    <i class="bi bi-send-fill me-1" x-show="!loading"></i>
                                    <span x-text="loading ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
