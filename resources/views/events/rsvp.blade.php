@extends('layouts.app')

@section('title', 'Konfirmasi Kehadiran - ' . $event->title)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <!-- Header Logo & Branding -->
            <div class="text-center mb-4">
                <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 65px;" class="mb-2">
                <h5 class="fw-bold text-success m-0">PC ISNU KOTA SURABAYA</h5>
                <small class="text-muted">Sistem Informasi Keanggotaan & Konfirmasi Kehadiran Kegiatan</small>
            </div>

            <!-- Main RSVP Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header text-white text-center p-4" style="background: linear-gradient(135deg, #0b5ed7 0%, #0d6efd 55%, #0a4fa3 100%);">
                    <span class="badge bg-white text-primary px-3 py-1.5 rounded-pill fw-semibold mb-2 shadow-sm">
                        <i class="bi bi-calendar2-check-fill me-1"></i> KONFIRMASI KEHADIRAN (RSVP)
                    </span>
                    <h4 class="fw-bold text-white mb-2">{{ $event->title }}</h4>
                    <div class="d-flex justify-content-center flex-wrap gap-2 text-white opacity-90 small mb-2">
                        <span><i class="bi bi-geo-alt me-1"></i> {{ ucfirst($event->method) }} - {{ $event->location }}</span>
                        <span>&bull;</span>
                        <span><i class="bi bi-calendar-event me-1"></i> {{ $event->event_date->translatedFormat('l, d F Y') }}</span>
                        <span>&bull;</span>
                        <span><i class="bi bi-clock me-1"></i> {{ $event->start_time }} - {{ $event->end_time }} WIB</span>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill px-3 py-1">
                            <i class="bi bi-people-fill me-1"></i> Estimasi Peserta Hadir Saat Ini: <strong>{{ $attendingCount }} Orang</strong>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if($event->description)
                        <div class="bg-light rounded-3 p-3 mb-4 small text-secondary border">
                            <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Deskripsi Kegiatan:</strong>
                            {{ $event->description }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success rounded-4 text-center p-4 shadow-sm mb-4 border-success">
                            <i class="bi bi-check-circle-fill text-success display-4 d-block mb-2"></i>
                            <h5 class="fw-bold text-dark mb-1">Konfirmasi Kehadiran Tercatat!</h5>
                            <p class="text-secondary small mb-0">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                            <div class="small fw-semibold text-dark">{{ session('error') }}</div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger rounded-4 p-3 mb-4 small">
                            <strong class="d-block mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Mohon periksa isian Anda:</strong>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($rsvpStatus !== 'open')
                        <div class="text-center py-4">
                            <div class="bg-secondary-subtle text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 p-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-calendar-x display-5"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Konfirmasi Kehadiran Ditutup</h4>
                            <p class="text-secondary mb-3 px-md-3">{{ $rsvpMessage }}</p>

                            @if($event->rsvp_deadline_at)
                                <div class="bg-light p-3 rounded-4 border d-inline-block text-start small">
                                    <div><strong class="text-muted">Batas Akhir Konfirmasi:</strong> {{ $event->rsvp_deadline_at->translatedFormat('l, d F Y H:i') }} WIB</div>
                                </div>
                            @endif
                        </div>
                    @else
                        @if($event->rsvp_deadline_at)
                            <div class="alert alert-light border rounded-3 py-2 px-3 mb-4 small d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history text-primary"></i>
                                <span>Batas akhir konfirmasi kehadiran: <strong class="text-dark">{{ $event->rsvp_deadline_at->translatedFormat('l, d F Y H:i') }} WIB</strong></span>
                            </div>
                        @endif

                        @if($existingRsvp)
                            <div class="alert alert-primary bg-primary-subtle border-primary-subtle rounded-4 p-3 mb-4 d-flex align-items-start gap-3">
                                <i class="bi bi-check2-circle fs-4 text-primary mt-0.5"></i>
                                <div class="small">
                                    <strong class="text-dark d-block">Konfirmasi Anda Sebelumnya: {{ $existingRsvp->status_label }}</strong>
                                    <span class="text-secondary">Tercatat pada {{ $existingRsvp->confirmed_at->translatedFormat('d M Y H:i') }} WIB. Anda dapat memperbarui status kehadiran melalui formulir di bawah ini jika ada perubahan.</span>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('event.rsvp.submit', $event->unique_code) }}" method="POST"
                              x-data="{
                                  loading: false,
                                  status: '{{ old('attendance_status', $existingRsvp?->attendance_status ?? 'hadir') }}'
                              }"
                              @submit="loading = true">
                            @csrf

                            @guest
                                <div class="alert alert-info border-info-subtle bg-info-subtle text-dark rounded-4 p-3 mb-4 shadow-sm">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-info text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                                <i class="bi bi-person-lock fs-5"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block text-dark small">Sudah Memiliki Akun SIKAP ISNU?</strong>
                                                <span class="text-secondary small">Silakan <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="fw-bold text-primary text-decoration-underline">Login terlebih dahulu</a> agar data profil Anda terisi otomatis.</span>
                                            </div>
                                        </div>
                                        <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="btn btn-sm btn-primary text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm ms-auto ms-sm-0">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Login Akun
                                        </a>
                                    </div>
                                </div>
                            @endguest

                            @if($member)
                                <div class="alert alert-success-subtle border border-success-subtle rounded-3 p-3 d-flex align-items-center gap-3 mb-4">
                                    <img src="{{ $member->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-success" style="width: 48px; height: 48px;">
                                    <div>
                                        <small class="text-muted d-block">Terdeteksi Akun Member:</small>
                                        <strong class="text-dark d-block">{{ $member->full_name }}</strong>
                                        <span class="badge bg-success small">{{ $member->member_number }}</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Pilihan Status Kehadiran -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold d-block mb-2">Apakah Anda berencana hadir pada kegiatan ini? <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-4">
                                        <label class="w-100 border rounded-4 p-3 text-center cursor-pointer transition h-100 d-flex flex-column align-items-center justify-content-center"
                                               :class="status === 'hadir' ? 'border-success bg-success-subtle text-success shadow-sm' : 'bg-white text-secondary'"
                                               style="cursor: pointer;">
                                            <input type="radio" name="attendance_status" value="hadir" class="d-none" x-model="status" required>
                                            <i class="bi bi-check-circle-fill fs-3 mb-1" :class="status === 'hadir' ? 'text-success' : 'text-muted'"></i>
                                            <span class="fw-bold small d-block">Akan Hadir</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">InsyaAllah hadir</span>
                                        </label>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <label class="w-100 border rounded-4 p-3 text-center cursor-pointer transition h-100 d-flex flex-column align-items-center justify-content-center"
                                               :class="status === 'ragu' ? 'border-warning bg-warning-subtle text-warning-emphasis shadow-sm' : 'bg-white text-secondary'"
                                               style="cursor: pointer;">
                                            <input type="radio" name="attendance_status" value="ragu" class="d-none" x-model="status">
                                            <i class="bi bi-question-circle-fill fs-3 mb-1" :class="status === 'ragu' ? 'text-warning' : 'text-muted'"></i>
                                            <span class="fw-bold small d-block">Masih Ragu</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">Tentatif / diusahakan</span>
                                        </label>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <label class="w-100 border rounded-4 p-3 text-center cursor-pointer transition h-100 d-flex flex-column align-items-center justify-content-center"
                                               :class="status === 'tidak_hadir' ? 'border-danger bg-danger-subtle text-danger shadow-sm' : 'bg-white text-secondary'"
                                               style="cursor: pointer;">
                                            <input type="radio" name="attendance_status" value="tidak_hadir" class="d-none" x-model="status">
                                            <i class="bi bi-x-circle-fill fs-3 mb-1" :class="status === 'tidak_hadir' ? 'text-danger' : 'text-muted'"></i>
                                            <span class="fw-bold small d-block">Tidak Hadir</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">Berhalangan hadir</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $existingRsvp?->name ?? $member?->full_name ?? $user?->name ?? '') }}" placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Si." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">NIK atau Nomor Anggota ISNU <span class="text-secondary small">(Opsional)</span></label>
                                <input type="text" name="nik_or_member_number" class="form-control" value="{{ old('nik_or_member_number', $existingRsvp?->nik_or_member_number ?? $member?->member_number ?? $member?->nik ?? '') }}" placeholder="Kosongkan jika tamu / belum memiliki nomor anggota">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $existingRsvp?->phone ?? $member?->phone ?? $user?->phone ?? '') }}" placeholder="081234567890" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Asal Utusan / PAC ISNU / Instansi <span class="text-danger">*</span></label>
                                <input type="text" name="institution_or_pac" class="form-control" value="{{ old('institution_or_pac', $existingRsvp?->institution_or_pac ?? $defaultInstitution) }}" placeholder="Contoh: PAC ISNU Gayungan / Universitas Airlangga / Umum" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Catatan Tambahan <span class="text-secondary small">(Opsional)</span></label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Misal: izin datang sedikit terlambat, atau alasan berhalangan hadir...">{{ old('notes', $existingRsvp?->notes ?? '') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold fs-6 shadow-sm" :disabled="loading">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                                <i class="bi bi-send-check-fill me-1" x-show="!loading"></i>
                                <span x-text="loading ? 'Menyimpan Konfirmasi...' : '{{ $existingRsvp ? 'Perbarui Konfirmasi Kehadiran' : 'Kirim Konfirmasi Kehadiran' }}'"></span>
                            </button>
                        </form>
                    @endif
                </div>

                <div class="card-footer bg-light border-0 text-center p-3 small text-muted">
                    &copy; {{ date('Y') }} PC ISNU Kota Surabaya. Hak Cipta Dilindungi.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
