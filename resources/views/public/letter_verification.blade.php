@extends('layouts.app')

@section('title', 'Verifikasi Keaslian Surat Digital - SIKAP ISNU Kota Surabaya')

@section('content')
<div class="container py-5 my-3">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <!-- Card Header Status Keaslian -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-body p-4 p-md-5 text-center @if($letter->status === 'selesai') bg-success-subtle text-success-emphasis @else bg-warning-subtle text-warning-emphasis @endif">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white shadow-sm mb-3" style="width: 72px; height: 72px;">
                        @if($letter->status === 'selesai')
                            <i class="bi bi-patch-check-fill text-success fs-1"></i>
                        @else
                            <i class="bi bi-clock-history text-warning fs-1"></i>
                        @endif
                    </div>
                    
                    <h3 class="fw-bold mb-2">
                        @if($letter->status === 'selesai')
                            DOKUMEN RESMI TERVERIFIKASI
                        @else
                            DOKUMEN DALAM PROSES PENANDATANGANAN
                        @endif
                    </h3>
                    <p class="mb-0 fs-6 text-muted">
                        @if($letter->isLevelPac())
                            Sistem Informasi Keanggotaan dan Pengurus (SIKAP-ISNU) {{ $letter->pac_display_name }}
                        @else
                            Sistem Informasi Keanggotaan dan Pengurus (SIKAP-ISNU) Pimpinan Cabang ISNU Kota Surabaya
                        @endif
                    </p>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <div class="row g-4">
                        <!-- Kop Surat Resmi (Sesuai Gambar Template Asli) -->
                        <div class="col-md-12">
                            <div class="d-flex align-items-center mb-3 border rounded-3 p-2 bg-white overflow-hidden shadow-none">
                                <div class="p-2 flex-shrink-0 text-center" style="width: 85px;">
                                    <img src="{{ asset('images/kop_logo_isnu.jpeg') }}" alt="Logo ISNU" style="width: 68px; height: auto;" class="img-fluid">
                                </div>
                                <div class="flex-grow-1 p-3 ps-3 text-dark" style="background-color: #94b3d6; border-bottom-left-radius: 18px; font-family: 'Trebuchet MS', 'Plus Jakarta Sans', sans-serif;">
                                    @if($letter->isLevelPac())
                                        <div class="fw-normal text-uppercase text-dark" style="font-size: 0.9rem; letter-spacing: 0.8px; line-height: 1.1; margin-bottom: 2px;">
                                            PIMPINAN ANAK CABANG
                                        </div>
                                        <div class="fw-bold text-uppercase text-dark" style="font-size: 1.15rem; line-height: 1.1; letter-spacing: -0.2px;">
                                            IKATAN SARJANA NAHDLATUL ULAMA
                                        </div>
                                        <div class="fw-bold text-uppercase text-dark" style="font-size: 1.15rem; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 4px;">
                                            KECAMATAN {{ strtoupper($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? 'SURABAYA')) }}
                                        </div>
                                    @else
                                        <div class="fw-normal text-uppercase text-dark" style="font-size: 0.95rem; letter-spacing: 0.8px; line-height: 1.1; margin-bottom: 2px;">
                                            PIMPINAN CABANG
                                        </div>
                                        <div class="fw-bold text-uppercase text-dark" style="font-size: 1.25rem; line-height: 1.1; letter-spacing: -0.2px;">
                                            IKATAN SARJANA NAHDLATUL
                                        </div>
                                        <div class="fw-bold text-uppercase text-dark" style="font-size: 1.25rem; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 4px;">
                                            ULAMA KOTA SURABAYA
                                        </div>
                                    @endif
                                    <div style="font-size: 0.78rem; color: #111; line-height: 1.3;">
                                        Jalan Bubutan VI/2 Surabaya, HP: +6281330278721, +6281334300341
                                    </div>
                                    <div style="font-size: 0.78rem; color: #111; line-height: 1.3;">
                                        Email: <a href="mailto:isnukotasurabaya@gmail.com" class="text-primary text-decoration-underline">isnukotasurabaya@gmail.com</a>, Website: <a href="https://www.isnusurabaya.or.id" target="_blank" class="text-primary text-decoration-underline">https://www.isnusurabaya.or.id</a>
                                    </div>
                                </div>
                                <div class="flex-shrink-0" style="width: 14px; align-self: stretch; background-color: #006837;"></div>
                            </div>
                        </div>

                        <!-- Data Surat -->
                        <div class="col-md-12">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-file-earmark-text me-2"></i>Informasi Dokumen Surat
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted" style="width: 25%;">Nomor Surat</td>
                                            <td style="width: 3%;">:</td>
                                            <td><strong class="fs-6">{{ $letter->formatted_letter_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Kode Unik Sistem</td>
                                            <td>:</td>
                                            <td><span class="badge bg-light text-dark border font-monospace">{{ $letter->letter_code }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Perihal</td>
                                            <td>:</td>
                                            <td><span class="fw-semibold">{{ $letter->perihal }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tanggal Surat</td>
                                            <td>:</td>
                                            <td>{{ $letter->formatted_date_letter }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tujuan Surat</td>
                                            <td>:</td>
                                            <td>{{ $letter->tujuan_surat }} ({{ $letter->kota_tujuan ?? 'Surabaya' }})</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Lampiran</td>
                                            <td>:</td>
                                            <td>{{ $letter->lampiran ?: '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Status Dokumen</td>
                                            <td>:</td>
                                            <td>
                                                <span class="{{ $letter->status_badge_class }} px-3 py-1 rounded-pill">
                                                    {{ $letter->status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Data Penandatangan Digital -->
                        <div class="col-md-12 pt-3">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-shield-check me-2"></i>Data Penandatangan Digital (E-TTD)
                            </h5>

                            <div class="row g-3">
                                <!-- Penandatangan 1: Sekretaris -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border bg-light h-100">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="p-2 bg-success text-white rounded-3 me-2">
                                                <i class="bi bi-pen"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Penandatangan Tahap 1</div>
                                                <div class="fw-bold">{{ $letter->secretary_position ?? 'Sekretaris' }}</div>
                                            </div>
                                        </div>

                                        @if($letter->secretary_signed_at)
                                            <div class="mb-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Sudah Ditandatangani
                                                </span>
                                            </div>
                                            <ul class="list-unstyled small mb-0">
                                                <li><strong>Nama:</strong> {{ $letter->secretary_name }}</li>
                                                <li><strong>No. Registrasi / NIRA:</strong> {{ $letter->secretary_registration_number }}</li>
                                                <li><strong>Waktu TTD:</strong> {{ $letter->secretary_signed_at->format('d F Y, H:i:s') }} WIB</li>
                                            </ul>
                                        @else
                                            <div class="text-muted small fst-italic">
                                                <i class="bi bi-hourglass-split me-1"></i>Menunggu penandatanganan oleh Sekretaris.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Penandatangan 2: Ketua -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border bg-light h-100">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="p-2 bg-primary text-white rounded-3 me-2">
                                                <i class="bi bi-award"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Penandatangan Tahap 2</div>
                                                <div class="fw-bold">{{ $letter->chairman_position ?? 'Ketua' }}</div>
                                            </div>
                                        </div>

                                        @if($letter->chairman_signed_at)
                                            <div class="mb-2">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Sudah Ditandatangani
                                                </span>
                                            </div>
                                            <ul class="list-unstyled small mb-0">
                                                <li><strong>Nama:</strong> {{ $letter->chairman_name }}</li>
                                                <li><strong>No. Registrasi / NIRA:</strong> {{ $letter->chairman_registration_number }}</li>
                                                <li><strong>Waktu TTD:</strong> {{ $letter->chairman_signed_at->format('d F Y, H:i:s') }} WIB</li>
                                            </ul>
                                        @else
                                            <div class="text-muted small fst-italic">
                                                <i class="bi bi-hourglass-split me-1"></i>Menunggu penandatanganan oleh Ketua.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ringkasan Isi Surat -->
                        <div class="col-md-12 pt-3">
                            <h5 class="fw-bold text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-card-text me-2"></i>Ringkasan Isi Surat
                            </h5>
                            <div class="p-4 rounded-3 border bg-white" style="line-height: 1.6; text-align: justify;">
                                @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                                    {!! $letter->isi_surat !!}
                                @else
                                    {!! nl2br(e($letter->isi_surat)) !!}
                                @endif
                            </div>
                        </div>

                        <!-- Unduh Dokumen Resmi jika Selesai -->
                        @if($letter->status === 'selesai')
                            <div class="col-md-12 text-center pt-3" x-data="{ isDownloading: false }">
                                <div class="p-4 rounded-4 bg-light border text-center">
                                    <h6 class="fw-bold mb-2">Unduh Salinan Dokumen Resmi</h6>
                                    <p class="text-muted small mb-3">
                                        Anda dapat mengunduh dokumen resmi dalam format PDF ber-QRCode dan berkop resmi PC ISNU Surabaya.
                                    </p>
                                    <button type="button" class="btn btn-success px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center"
                                            :disabled="isDownloading"
                                            @click="downloadPdfWithSpinner('{{ route('surat.download-pdf', $letter->letter_code) }}', 'Surat_{{ \Illuminate\Support\Str::slug($letter->letter_number, '_') }}.pdf', (val) => isDownloading = val)">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" x-show="isDownloading" x-cloak></span>
                                        <i class="bi bi-file-earmark-pdf-fill me-2" x-show="!isDownloading"></i>
                                        <span x-text="isDownloading ? 'Mengunduh Dokumen...' : 'Unduh Salinan PDF'">Unduh Salinan PDF</span>
                                    </button>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

                <div class="card-footer bg-light p-3 text-center small text-muted border-0">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i>
                    Dokumen ini dijamin keasliannya dan diterbitkan secara resmi melalui Sistem Informasi Keanggotaan dan Pengurus ISNU Kota Surabaya.
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
