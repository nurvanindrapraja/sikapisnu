@extends('layouts.app')

@section('title', 'Presensi Kehadiran - ' . $event->title)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <!-- Header Logo & Branding -->
            <div class="text-center mb-4">
                <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 65px;" class="mb-2">
                <h5 class="fw-bold text-success m-0">PC ISNU KOTA SURABAYA</h5>
                <small class="text-muted">Sistem Informasi Keanggotaan & Presensi Kegiatan</small>
            </div>

            <!-- Main Presensi Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-isnu text-white text-center p-4">
                    <span class="badge bg-white text-dark px-3 py-1.5 rounded-pill font-semibold mb-2 shadow-sm">
                        <i class="bi bi-qr-code-scan me-1 text-success"></i> FORM PRESENSI KEHADIRAN
                    </span>
                    <h4 class="fw-bold text-white mb-2">{{ $event->title }}</h4>
                    <div class="d-flex justify-content-center flex-wrap gap-2 text-white opacity-75 small">
                        <span><i class="bi bi-geo-alt me-1"></i> {{ ucfirst($event->method) }} - {{ $event->location }}</span>
                        <span>•</span>
                        <span><i class="bi bi-calendar-event me-1"></i> {{ $event->event_date->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if(session('success'))
                        <div class="alert alert-success rounded-4 text-center p-4 shadow-sm mb-4 border-success">
                            <i class="bi bi-check-circle-fill text-success display-4 d-block mb-2"></i>
                            <h5 class="fw-bold text-dark mb-1">Presensi Berhasil Diterima!</h5>
                            <p class="text-secondary small mb-3">{{ session('success') }}</p>

                            @if($event->method === 'daring' && $event->meeting_link)
                                <div class="bg-white p-3 rounded-3 border mt-3 text-start">
                                    <small class="text-muted d-block fw-semibold mb-1"><i class="bi bi-camera-video-fill text-primary me-1"></i> Link Virtual Meeting Daring:</small>
                                    <a href="{{ $event->meeting_link }}" target="_blank" class="btn btn-primary btn-sm w-100 rounded-pill font-bold">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Gabung Ruang Meeting Zoom/Google Meet
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill fs-4 text-info"></i>
                            <div class="small fw-semibold text-dark">{{ session('info') }}</div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger rounded-4 p-3 mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                            <div class="small fw-semibold text-dark">{{ session('error') }}</div>
                        </div>
                    @endif

                    <!-- CASE 1: Presensi Belum Dimulai -->
                    @if($status === 'not_started')
                        <div class="text-center py-4">
                            <div class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3 p-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-hourglass-split display-5"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Acara Belum Dimulai</h4>
                            <p class="text-secondary mb-4 px-md-3">{{ $message }}</p>
                            
                            <div class="bg-light p-3 rounded-4 border d-inline-block text-start small">
                                <div><strong class="text-muted">Presensi Dibuka:</strong> {{ $event->presence_start_at->translatedFormat('l, d F Y') }} jam {{ $event->presence_start_at->format('H:i') }} WIB</div>
                                <div><strong class="text-muted">Presensi Ditutup:</strong> {{ $event->presence_end_at->translatedFormat('l, d F Y') }} jam {{ $event->presence_end_at->format('H:i') }} WIB</div>
                            </div>
                        </div>

                    <!-- CASE 2: Presensi Telah Selesai / Berakhir -->
                    @elseif($status === 'ended')
                        <div class="text-center py-4">
                            <div class="bg-secondary-subtle text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 p-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-clock-history display-5"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Acara Telah Selesai</h4>
                            <p class="text-secondary mb-4 px-md-3">Masa akses presensi kehadiran untuk kegiatan ini telah berakhir pada <strong>{{ $event->presence_end_at->translatedFormat('d F Y H:i') }} WIB</strong>.</p>
                            
                            <div class="alert alert-secondary rounded-3 small m-0">
                                Terima kasih atas partisipasi dan kehadiran Bapak/Ibu/Rekan-rekan dalam kegiatan ini.
                            </div>
                        </div>

                    <!-- CASE 3: Presensi Aktif -->
                    @else
                        @if($alreadyAttended && !session('success'))
                            <div class="alert alert-success rounded-4 text-center p-4 mb-4">
                                <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                                <h5 class="fw-bold text-dark">Anda Sudah Melakukan Presensi</h5>
                                <p class="text-muted small m-0">Kehadiran Anda telah tercatat dalam sistem SIKAP ISNU Kota Surabaya.</p>
                            </div>
                        @else
                            <form action="{{ route('event.presence.submit', $event->unique_code) }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                                @csrf
                                <div class="space-y-4">
                                    @guest
                                        <div class="alert alert-info border-info-subtle bg-info-subtle text-dark rounded-4 p-3 mb-4 shadow-sm">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2.5">
                                                    <div class="bg-info text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                                        <i class="bi bi-person-lock fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block text-dark small">Sudah Memiliki Akun SIKAP ISNU?</strong>
                                                        <span class="text-secondary extra-small">Silakan <a href="{{ route('login') }}" class="fw-bold text-info text-decoration-underline">Login terlebih dahulu</a> sebelum mengisi form agar presensi ini otomatis terhubung dengan profil Anda.</span>
                                                    </div>
                                                </div>
                                                <a href="{{ route('login') }}" class="btn btn-sm btn-info text-white rounded-pill px-3 py-1.5 fw-bold shadow-sm ms-auto ms-sm-0">
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

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', $member?->full_name ?? $user?->name ?? '') }}" placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Si." required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">NIK atau Nomor Anggota ISNU <span class="text-secondary small">(Opsional)</span></label>
                                        <input type="text" name="nik_or_member_number" class="form-control" value="{{ old('nik_or_member_number', $member?->member_number ?? $member?->nik ?? '') }}" placeholder="Kosongkan jika tamu / belum memiliki nomor anggota">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $member?->phone ?? $user?->phone ?? '') }}" placeholder="081234567890" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Asal Utusan / PAC ISNU / Instansi <span class="text-danger">*</span></label>
                                        <input type="text" name="institution_or_pac" class="form-control" value="{{ old('institution_or_pac', $defaultInstitution) }}" placeholder="Contoh: PAC ISNU Gayungan / Universitas Airlangga / Umum" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Catatan / Pesan Kesan <span class="text-secondary small">(Opsional)</span></label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan (Opsional)..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-isnu-primary w-100 py-3 rounded-pill fw-bold fs-6 shadow-sm" :disabled="loading">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                                        <i class="bi bi-check2-square me-1" x-show="!loading"></i>
                                        <span x-text="loading ? 'Menyimpan Presensi...' : 'Kirim Presensi Kehadiran'"></span>
                                    </button>
                                </div>
                            </form>
                        @endif
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
