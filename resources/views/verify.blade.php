@extends('layouts.app')

@section('title', 'Verifikasi Kartu Anggota ISNU')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center">
                <!-- Header Status -->
                <div class="p-4 {{ $isValid ? 'bg-success text-white' : 'bg-danger text-white' }}">
                    <div class="bg-white p-2 rounded-3 shadow-sm d-inline-block mb-3">
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 52px; object-fit: contain;">
                    </div>
                    <h5 class="fw-bold m-0 text-uppercase">ISNU KOTA SURABAYA</h5>
                    <div class="mt-3">
                        @if($isValid)
                            <span class="badge bg-white text-success fs-6 fw-bold px-4 py-2 rounded-pill shadow-sm">
                                <i class="bi bi-check-circle-fill text-success me-2"></i> KARTU TERVERIFIKASI SAH
                            </span>
                        @else
                            <span class="badge bg-white text-danger fs-6 fw-bold px-4 py-2 rounded-pill shadow-sm">
                                <i class="bi bi-x-circle-fill text-danger me-2"></i> KARTU TIDAK VALID / EXPIRED
                            </span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if($isValid && $member)
                        <div class="mb-4">
                            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="rounded-3 border border-3 border-success shadow-sm object-fit-cover mb-3" style="width: 120px; height: 145px;">
                            <h4 class="fw-bold text-dark mb-1">{{ $member->full_name }}</h4>
                            <span class="badge {{ $member->membership_status === 'pengurus' ? 'bg-warning text-dark' : 'bg-success' }} px-3 py-2 fw-bold text-uppercase fs-6">
                                STATUS: {{ strtoupper($member->membership_status) }}
                            </span>
                        </div>

                        <div class="table-responsive text-start border rounded-3 overflow-hidden mb-4">
                            <table class="table table-striped table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted w-40" style="font-size: 0.85rem;">Nomor Anggota</th>
                                        <td class="fw-bold font-monospace text-success">{{ $member->member_number }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted" style="font-size: 0.85rem;">Status Keanggotaan</th>
                                        <td class="fw-bold text-capitalize">{{ $member->membership_status }}</td>
                                    </tr>
                                    @if($member->membership_status === 'pengurus' && $member->activePosition)
                                        <tr>
                                            <th class="bg-light text-muted" style="font-size: 0.85rem;">Jabatan Pengurus</th>
                                            <td class="fw-bold text-warning">{{ $member->activePosition->position_title }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-muted" style="font-size: 0.85rem;">Periode Kepengurusan</th>
                                            <td class="fw-bold">{{ $member->activePosition->period }}</td>
                                        </tr>
                                    @endif
                                    @if($member->mwc)
                                        <tr>
                                            <th class="bg-light text-muted" style="font-size: 0.85rem;">MWC NU Kecamatan</th>
                                            <td class="fw-semibold">{{ $member->mwc->name }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th class="bg-light text-muted" style="font-size: 0.85rem;">Tanggal Diterbitkan</th>
                                        <td class="text-secondary">{{ $card->issued_at ? $card->issued_at->translatedFormat('d F Y') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted" style="font-size: 0.85rem;">Status Kartu</th>
                                        <td class="text-success fw-bold"><i class="bi bi-shield-check me-1"></i> AKTIF & RESMI</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-success border-0 bg-success-subtle text-success small text-center mb-0">
                            <i class="bi bi-info-circle-fill me-1"></i> Data di atas diverifikasi langsung dari basis data resmi Pimpinan Cabang ISNU Kota Surabaya.
                        </div>
                    @else
                        <div class="py-4">
                            <i class="bi bi-shield-exclamation text-danger display-1 mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark">{{ $message }}</h5>
                            <p class="text-secondary small">QR Code yang Anda scan tidak terdaftar atau telah dinonaktifkan oleh administrator.</p>
                        </div>
                    @endif

                    <div class="mt-4 pt-3 border-top">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
