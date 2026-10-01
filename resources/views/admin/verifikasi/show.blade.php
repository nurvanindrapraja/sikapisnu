@extends('layouts.admin')

@section('title', 'Validasi Pendaftaran')
@section('header_title', 'Validasi Pendaftaran')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <!-- Back Icon & Button -->
    <a href="{{ route('admin.verifikasi.index') }}" class="btn btn-outline-secondary rounded-pill px-2.5 px-md-3 fw-semibold d-inline-flex align-items-center" title="Kembali ke Verifikasi Keanggotaan">
        <i class="bi bi-arrow-left me-0 me-md-1"></i> <span class="d-none d-md-inline">Kembali ke Verifikasi Keanggotaan</span>
    </a>
    <span class="badge @if($member->membership_status === 'menunggu_verifikasi') bg-secondary text-white border border-secondary @elseif($member->membership_status === 'terverifikasi') bg-success-subtle text-success border border-success-subtle @elseif($member->membership_status === 'pengurus') bg-warning text-dark border border-warning @else bg-secondary text-white @endif px-3 py-1.5 rounded-pill fs-7 text-uppercase fw-bold">
        Status: {{ str_replace('_', ' ', $member->membership_status) }}
    </span>
</div>

<div class="row g-4">
    <!-- Left Column: Main Identity & Action Panel -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center mb-4">
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="rounded-3 border border-3 border-success mx-auto mb-3 object-fit-cover shadow-sm" style="width: 140px; height: 165px;">
            <h5 class="fw-bold text-dark mb-1">{{ $member->full_name }}</h5>
            <span class="badge @if($member->membership_status === 'menunggu_verifikasi') bg-secondary text-white @elseif($member->membership_status === 'terverifikasi') bg-success text-white @elseif($member->membership_status === 'pengurus') bg-warning text-dark @else bg-secondary text-white @endif px-3 py-1 mb-3 text-uppercase">
                {{ str_replace('_', ' ', $member->membership_status) }}
            </span>

            <div class="text-start small space-y-2 border-top pt-3">
                <div><span class="text-muted">NIK:</span> <strong class="float-end font-monospace">{{ $member->nik }}</strong></div>
                <div><span class="text-muted">Email:</span> <strong class="float-end">{{ $member->email }}</strong></div>
                <div><span class="text-muted">No. HP / WA:</span> <strong class="float-end">{{ $member->phone }}</strong></div>
                <div><span class="text-muted">Pekerjaan:</span> <strong class="float-end">{{ $member->occupation }}</strong></div>
                <div><span class="text-muted">MWC NU:</span> <strong class="float-end">{{ $member->mwc ? $member->mwc->name : '-' }}</strong></div>
            </div>

            <!-- Action Panel -->
            <div class="mt-4 pt-3 border-top d-flex flex-column gap-2">
                @if(!$member->user || !$member->user->is_active || $member->membership_status === 'menunggu_verifikasi')
                    <!-- Verify Account Modal Trigger (Aktifkan Login) -->
                    <button type="button" class="btn btn-primary w-100 fw-bold rounded-pill py-2.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVerifyAccount">
                        <i class="bi bi-person-check-fill me-1"></i> 1. Verifikasi Pendaftaran Akun
                    </button>
                    <small class="text-muted text-center d-block mb-1" style="font-size: 0.75rem;">
                        *Mengaktifkan akun agar pendaftar bisa login ke aplikasi (belum diterbitkan kartu)
                    </small>
                @endif

                @if($member->membership_status !== 'terverifikasi' && $member->membership_status !== 'pengurus')
                    <!-- Approve Member Modal Trigger (Terbitkan Kartu) -->
                    <button type="button" class="btn btn-success w-100 fw-bold rounded-pill py-2.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="bi bi-card-checklist me-1"></i> 2. Verifikasi Anggota & Terbitkan Kartu
                    </button>

                    <!-- Request Revision Modal Trigger -->
                    <button type="button" class="btn btn-warning w-100 fw-bold rounded-pill py-2 text-dark" data-bs-toggle="modal" data-bs-target="#modalRevision">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan untuk Perbaikan
                    </button>

                    <!-- Reject Modal Trigger -->
                    <button type="button" class="btn btn-outline-danger w-100 fw-semibold rounded-pill py-2" data-bs-toggle="modal" data-bs-target="#modalReject">
                        <i class="bi bi-x-circle me-1"></i> Tolak Pendaftaran
                    </button>
                @else
                    <div class="alert alert-success border-0 small m-0">
                        <i class="bi bi-check-circle-fill me-1"></i> Anggota ini telah terverifikasi penuh dengan Nomor Anggota: <strong>{{ $member->member_number }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Complete Biodata & Potensi Slider -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-3 mb-3"><i class="bi bi-person-badge-fill text-success me-2"></i> Rincian Identitas Anggota</h6>

            <div class="row g-3 small">
                <div class="col-md-6">
                    <span class="text-muted d-block">Tempat, Tanggal Lahir</span>
                    <strong>{{ $member->birth_place }}, {{ $member->birth_date ? $member->birth_date->translatedFormat('d F Y') : '-' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted d-block">Jenis Kelamin</span>
                    <strong>{{ $member->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong>
                </div>
                <div class="col-12">
                    <span class="text-muted d-block">Alamat Lengkap</span>
                    <strong>{{ $member->address }}, Kel. {{ $member->kelurahan }}, Kec. {{ $member->kecamatan }}, {{ $member->city }}</strong>
                </div>
            </div>
        </div>

        <!-- Rekam Jejak Potensi Kader Component (Accordion) -->
        <div class="card border-0 shadow-sm rounded-4 p-4" x-data="{ openSection: 'edu' }">
            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-journal-bookmark-fill text-success"></i> Rekam Jejak Potensi Kader
            </h6>

            <div class="d-flex flex-column gap-2">
                <!-- 1. Riwayat Pendidikan -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'edu' ? null : 'edu'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-mortarboard-fill"></i>
                            <span>Riwayat Pendidikan ({{ $member->educations->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'edu' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'edu'" class="px-3 pb-3 border-top bg-white">
                        <div class="pt-3">
                            @forelse($member->educations as $edu)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-success">
                                    <strong class="text-dark fs-6 d-block">{{ $edu->level }} {{ $edu->major }}</strong>
                                    <div class="text-secondary small">{{ $edu->institution_name }} ({{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }})</div>
                                    @if($edu->degree)
                                        <small class="text-muted d-block mt-1">Gelar: {{ $edu->degree }}</small>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-mortarboard display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data riwayat pendidikan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 2. Riwayat Organisasi -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'org' ? null : 'org'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-people-fill"></i>
                            <span>Riwayat Organisasi ({{ $member->organizations->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'org' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'org'" class="px-3 pb-3 border-top bg-white" style="display: none;">
                        <div class="pt-3">
                            @forelse($member->organizations as $org)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-info">
                                    <strong class="text-dark fs-6 d-block">{{ $org->organization_name }}</strong>
                                    <div class="text-secondary small">Jabatan: <strong>{{ $org->position ?? '-' }}</strong> @if($org->period) (Periode: {{ $org->period }}) @endif</div>
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-diagram-3 display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data riwayat organisasi.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 3. Riwayat Pekerjaan & Profesi -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'emp' ? null : 'emp'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-briefcase-fill"></i>
                            <span>Riwayat Pekerjaan & Profesi ({{ $member->employments->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'emp' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'emp'" class="px-3 pb-3 border-top bg-white" style="display: none;">
                        <div class="pt-3">
                            @forelse($member->employments as $emp)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-primary">
                                    <strong class="text-dark fs-6 d-block">{{ $emp->position }}</strong>
                                    <div class="text-secondary small">{{ $emp->company_name }} @if($emp->employment_status)<span class="badge bg-secondary ms-1">{{ $emp->employment_status }}</span>@endif ({{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }})</div>
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-briefcase display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data riwayat pekerjaan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 4. Riwayat Kaderisasi NU -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'nu' ? null : 'nu'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-award-fill"></i>
                            <span>Riwayat Kaderisasi NU ({{ $member->nuTrainings->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'nu' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'nu'" class="px-3 pb-3 border-top bg-white" style="display: none;">
                        <div class="pt-3">
                            @forelse($member->nuTrainings as $nu)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-warning">
                                    <span class="badge bg-success me-2 fs-6">{{ $nu->training_type }}</span>
                                    <strong>{{ $nu->organizer }}</strong> (Tahun {{ $nu->year }})
                                    @if($nu->certificate_number)
                                        <div class="small text-muted mt-1">No. Sertifikat: <code>{{ $nu->certificate_number }}</code></div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-award display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data kaderisasi NU.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 5. Sertifikasi Keahlian -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'cert' ? null : 'cert'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-patch-check-fill"></i>
                            <span>Sertifikasi Keahlian ({{ $member->certifications->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'cert' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'cert'" class="px-3 pb-3 border-top bg-white" style="display: none;">
                        <div class="pt-3">
                            @forelse($member->certifications as $cert)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-dark">
                                    <strong class="text-dark fs-6 d-block">{{ $cert->certification_name }}</strong>
                                    <div class="text-secondary small">Lembaga: {{ $cert->issuing_organization }} | Bidang: <span class="badge bg-info text-white">{{ $cert->field }}</span></div>
                                    @if($cert->certificate_number)
                                        <small class="text-muted d-block mt-1">No. Sertifikat: <code>{{ $cert->certificate_number }}</code></small>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-patch-check display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Tidak ada data sertifikasi keahlian.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 6. Kehadiran Presensi Kegiatan -->
                <div class="border rounded-4 bg-light overflow-hidden transition-all">
                    <button type="button" 
                            @click="openSection = openSection === 'presence' ? null : 'presence'" 
                            class="w-100 p-3 bg-light border-0 d-flex align-items-center justify-content-between text-start transition-all">
                        <div class="d-flex align-items-center gap-2 text-success fw-bold">
                            <i class="bi bi-calendar-check-fill"></i>
                            <span>Kehadiran Presensi Kegiatan ({{ $member->presences->count() }})</span>
                        </div>
                        <i class="bi text-secondary transition-all fs-6" :class="openSection === 'presence' ? 'bi-chevron-up text-success' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="openSection === 'presence'" class="px-3 pb-3 border-top bg-white" style="display: none;">
                        <div class="pt-3">
                            @forelse($member->presences->load('event') as $p)
                                <div class="p-3 bg-light rounded-3 mb-2 border-start border-4 border-success d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-dark fs-6 d-block">{{ $p->event->title ?? 'Kegiatan ISNU' }}</strong>
                                        <div class="text-secondary small">
                                            <i class="bi bi-clock me-1"></i> {{ $p->attended_at ? $p->attended_at->translatedFormat('d F Y H:i') : '-' }} WIB
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Hadir
                                    </span>
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-calendar-x display-6 d-block mb-1 text-secondary opacity-50"></i>
                                    Belum ada riwayat presensi kegiatan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Verify Account -->
<div class="modal fade" id="modalVerifyAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.verifikasi.verify_account', $member->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-check-fill me-2"></i> Verifikasi Pendaftaran Akun
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="text-primary mb-3">
                    <i class="bi bi-person-check-fill display-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Aktifkan Login Akun Pendaftar?</h5>
                <p class="text-muted mb-0">
                    Apakah Anda yakin ingin memverifikasi akun pendaftaran <strong class="text-dark">{{ $member->full_name }}</strong>? Langkah ini akan <strong>mengaktifkan akun agar pendaftar dapat masuk (login)</strong> ke dalam aplikasi.
                    <br><small class="text-secondary mt-2 d-block">(Catatan: Kartu Anggota Digital belum diterbitkan pada tahap ini).</small>
                </p>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <i class="bi bi-person-check-fill me-1" x-show="!loading"></i>
                    <span x-text="loading ? 'Memproses...' : 'Ya, Aktifkan Login Akun'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Approve Member & Issue Card -->
<div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.verifikasi.approve', $member->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-card-checklist me-2"></i> Verifikasi Anggota ISNU & Terbitkan Kartu
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="text-success mb-3">
                    <i class="bi bi-card-checklist display-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Verifikasi Anggota & Terbitkan Kartu Digital?</h5>
                <p class="text-muted mb-0">
                    Apakah Anda yakin ingin menyetujui <strong class="text-dark">{{ $member->full_name }}</strong> sebagai Anggota Resmi ISNU serta menerbitkan Nomor Anggota dan Kartu Anggota Digital?
                </p>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <i class="bi bi-card-checklist me-1" x-show="!loading"></i>
                    <span x-text="loading ? 'Menerbitkan...' : 'Ya, Terbitkan Kartu Anggota'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Revision -->
<div class="modal fade" id="modalRevision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.verifikasi.revision', $member->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="modal-header bg-warning text-dark border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan untuk Perbaikan Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label fw-semibold">Catatan Revisi / Alasan Pengembalian <span class="text-danger">*</span></label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Mohon melengkapi foto profil yang jelas dan riwayat pendidikan terakhir..." required></textarea>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Mengirim...' : 'Kirim Catatan Perbaikan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.verifikasi.reject', $member->id) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-1"></i> Tolak Pendaftaran Anggota</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold" :disabled="loading">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                    <span x-text="loading ? 'Menolak...' : 'Tolak Pendaftaran'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
