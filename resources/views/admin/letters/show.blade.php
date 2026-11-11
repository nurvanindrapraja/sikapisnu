@extends('layouts.admin')

@section('title', 'Detail Konsep Surat & E-TTD')
@section('header_title', 'Detail Konsep Surat & E-TTD')

@section('content')
<div class="container-fluid px-0" x-data="{ isSubmittingToSecretary: false, isSigningSec: false, isSigningChair: false, isRejecting: false, isDownloadingPdf: false }">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="{{ $letter->letter_type_badge_class }} px-2 py-0.5 rounded-pill small">
                    {{ $letter->letter_type_label }}
                </span>
                <span class="{{ $letter->status_badge_class }} px-3 py-1 rounded-pill">
                    {{ $letter->status_label }}
                </span>
                <span class="text-muted small font-monospace">Kode: {{ $letter->letter_code }}</span>
            </div>
            <h4 class="fw-bold mb-0 text-dark">{{ $letter->formatted_letter_number }}</h4>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.letters.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            @if($letter->status === 'selesai')
                <button type="button" 
                        class="btn btn-outline-success rounded-pill px-3 d-inline-flex align-items-center"
                        :disabled="isDownloadingPdf"
                        @click="downloadPdfWithSpinner('{{ route('admin.letters.download-pdf', $letter->id) }}', 'Surat_{{ \Illuminate\Support\Str::slug($letter->letter_number, '_') }}.pdf', (val) => isDownloadingPdf = val)">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isDownloadingPdf" x-cloak></span>
                    <i class="bi bi-file-earmark-pdf me-1" x-show="!isDownloadingPdf"></i>
                    <span x-text="isDownloadingPdf ? 'Mengunduh...' : 'Unduh PDF'">Unduh PDF</span>
                </button>
            @else
                <button type="button" 
                        class="btn btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center"
                        disabled
                        title="File PDF baru dapat diunduh ketika surat sudah ditandatangani secara lengkap oleh Sekretaris dan Ketua.">
                    <i class="bi bi-lock-fill me-1"></i>
                    <span>Unduh PDF (Terkunci)</span>
                </button>
            @endif
            <a href="{{ route('surat.verifikasi', $letter->letter_code) }}" target="_blank" class="btn btn-outline-info rounded-pill px-3">
                <i class="bi bi-qr-code-scan me-1"></i> Halaman Verifikasi
            </a>
            @if(auth()->user()->canEditLetter($letter))
                <a href="{{ route('admin.letters.edit', $letter->id) }}" class="btn btn-light border rounded-pill px-3">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endif
            @if(auth()->user()->isSuperAdmin() || (($letter->status === 'draft' || $letter->status === 'ditolak') && auth()->user()->canEditLetter($letter)))
                <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteLetterModal">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($letter->status === 'ditolak' && $letter->rejection_note)
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-2"></i>Surat Ditolak / Memerlukan Revisi:</h6>
            <p class="mb-0 small">{{ $letter->rejection_note }}</p>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Document Details & Preview -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Kop Preview Persis Gambar Template -->
                    <div class="d-flex align-items-center mb-4 overflow-hidden shadow-sm" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <!-- Logo ISNU -->
                        <div class="p-2 flex-shrink-0 text-center" style="width: 95px;">
                            <img src="{{ asset('images/kop_logo_isnu.jpeg') }}" alt="Logo ISNU" style="width: 78px; height: auto;" class="img-fluid">
                        </div>
                        <!-- Banner Biru Muda Lembut -->
                        <div class="flex-grow-1 p-3 ps-3 text-dark" style="background-color: #94b3d6; border-bottom-left-radius: 18px; font-family: 'Trebuchet MS', 'Plus Jakarta Sans', sans-serif;">
                            @if($letter->isLevelPac())
                                <div class="fw-normal text-uppercase text-dark" style="font-size: 0.95rem; letter-spacing: 0.8px; line-height: 1.1; margin-bottom: 2px;">
                                    PIMPINAN ANAK CABANG
                                </div>
                                <div class="fw-bold text-uppercase text-dark" style="font-size: 1.25rem; line-height: 1.1; letter-spacing: -0.2px;">
                                    IKATAN SARJANA NAHDLATUL ULAMA
                                </div>
                                <div class="fw-bold text-uppercase text-dark" style="font-size: 1.25rem; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 5px;">
                                    KECAMATAN {{ strtoupper($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? 'SURABAYA')) }}
                                </div>
                            @else
                                <div class="fw-normal text-uppercase text-dark" style="font-size: 1.05rem; letter-spacing: 0.8px; line-height: 1.1; margin-bottom: 2px;">
                                    PIMPINAN CABANG
                                </div>
                                <div class="fw-bold text-uppercase text-dark" style="font-size: 1.4rem; line-height: 1.1; letter-spacing: -0.2px;">
                                    IKATAN SARJANA NAHDLATUL
                                </div>
                                <div class="fw-bold text-uppercase text-dark" style="font-size: 1.4rem; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 5px;">
                                    ULAMA KOTA SURABAYA
                                </div>
                            @endif
                            <div style="font-size: 0.82rem; color: #111; line-height: 1.3;">
                                Jalan Bubutan VI/2 Surabaya, HP: +6281330278721, +6281334300341
                            </div>
                            <div style="font-size: 0.82rem; color: #111; line-height: 1.3;">
                                Email: <a href="mailto:isnukotasurabaya@gmail.com" class="text-primary text-decoration-underline">isnukotasurabaya@gmail.com</a>, Website: <a href="https://www.isnusurabaya.or.id" target="_blank" class="text-primary text-decoration-underline">https://www.isnusurabaya.or.id</a>
                            </div>
                        </div>
                        <!-- Strip Hijau Tua Kanan -->
                        <div class="flex-shrink-0" style="width: 16px; align-self: stretch; background-color: #006837;">
                        </div>
                    </div>

                    <style>
                        .letter-preview-body table {
                            width: 100%;
                            margin: 12px 0;
                            border-collapse: collapse;
                        }
                        .letter-preview-body table.table-bordered,
                        .letter-preview-body table[border="1"] {
                            border: 1px solid #333;
                        }
                        .letter-preview-body table.table-bordered th,
                        .letter-preview-body table.table-bordered td,
                        .letter-preview-body table[border="1"] th,
                        .letter-preview-body table[border="1"] td {
                            border: 1px solid #333;
                            padding: 6px 10px;
                        }
                        .letter-preview-body table.table-bordered th,
                        .letter-preview-body table[border="1"] th {
                            background-color: #f1f5f9;
                            font-weight: 600;
                        }
                        .letter-preview-body table.table-borderless,
                        .letter-preview-body table[border="0"],
                        .letter-preview-body table:not(.table-bordered):not([border="1"]) {
                            border: none;
                        }
                        .letter-preview-body table.table-borderless th,
                        .letter-preview-body table.table-borderless td,
                        .letter-preview-body table[border="0"] th,
                        .letter-preview-body table[border="0"] td,
                        .letter-preview-body table:not(.table-bordered):not([border="1"]) th,
                        .letter-preview-body table:not(.table-bordered):not([border="1"]) td {
                            border: none;
                            padding: 4px 8px;
                            vertical-align: top;
                        }
                    </style>

                    @if($letter->isMandat())
                        <!-- Layout Khusus SURAT MANDAT (Sesuai template_mandat.docx) -->
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-uppercase text-decoration-underline mb-1" style="font-family: 'Times New Roman', Times, serif; letter-spacing: 1.5px;">SURAT MANDAT</h4>
                            <div class="font-monospace text-dark fw-medium small">Nomor : {{ $letter->formatted_letter_number }}</div>
                        </div>

                        <p class="mb-3 text-justify" style="line-height: 1.7;">
                            Yang bertandatangan dibawah ini adalah Pengurus {{ $letter->isLevelPac() ? 'Pimpinan Anak Cabang Ikatan Sarjana Nahdlatul Ulama (PAC ISNU) Kecamatan ' . ($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? '')) : 'Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya' }} merekomendasikan kepada:
                        </p>

                        <div class="p-3 bg-light rounded-3 mb-3 letter-preview-body text-justify" style="line-height: 1.7;">
                            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                                {!! $letter->isi_surat !!}
                            @else
                                {!! nl2br(e($letter->isi_surat)) !!}
                            @endif
                        </div>

                        <p class="mb-3 text-justify" style="line-height: 1.7;">
                            Untuk mendaftar dan mengikuti kegiatan <strong>{{ $letter->kegiatan }}</strong> pada tanggal <strong>{{ $letter->tanggal_pelaksanaan }}</strong> di <strong>{{ $letter->lokasi_pelaksanaan }}</strong>.
                        </p>

                        <p class="mb-4 text-justify" style="line-height: 1.7;">
                            Demikian surat mandat ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
                        </p>

                        <div class="text-end text-muted small mb-3">
                            {{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}
                        </div>
                    @elseif($letter->isRekomendasi())
                        <!-- Layout Khusus SURAT REKOMENDASI (Sesuai template_rekom.docx) -->
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-uppercase text-decoration-underline mb-1" style="font-family: 'Times New Roman', Times, serif; letter-spacing: 1.5px;">SURAT REKOMENDASI</h4>
                            <div class="font-monospace text-dark fw-medium small">Nomor : {{ $letter->formatted_letter_number }}</div>
                        </div>

                        <p class="mb-3 text-justify" style="line-height: 1.7;">
                            Yang bertandatangan dibawah ini adalah Pengurus Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya merekomendasikan kepada:
                        </p>

                        <div class="p-3 bg-light rounded-3 mb-3 letter-preview-body text-justify" style="line-height: 1.7;">
                            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                                {!! $letter->isi_surat !!}
                            @else
                                {!! nl2br(e($letter->isi_surat)) !!}
                            @endif
                        </div>

                        <p class="mb-4 text-justify" style="line-height: 1.7;">
                            Demikian surat rekomendasi ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
                        </p>

                        <div class="text-end text-muted small mb-3">
                            {{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}
                        </div>
                    @else
                        <!-- Layout Standar SURAT DINAS / SURAT BIASA -->
                        <!-- Meta Surat -->
                        <div class="row mb-4">
                            <div class="col-md-7">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td style="width: 25%;" class="text-muted">Nomor</td>
                                        <td style="width: 5%;">:</td>
                                        <td><strong>{{ $letter->formatted_letter_number }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Lampiran</td>
                                        <td>:</td>
                                        <td>{{ $letter->lampiran ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Perihal</td>
                                        <td>:</td>
                                        <td><strong>{{ $letter->perihal }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-5 text-md-end text-muted small pt-1">
                                {{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}
                            </div>
                        </div>

                        <!-- Penerima -->
                        <div class="mb-4">
                            <div class="text-muted small">Kepada Yang Terhormat:</div>
                            <div class="fw-bold fs-6">{{ $letter->tujuan_surat }}</div>
                            <div class="text-muted small">di - {{ $letter->kota_tujuan ?? 'Surabaya' }}</div>
                        </div>

                        <!-- Salam & Isi -->
                        <div class="fst-italic fw-semibold text-dark mb-3">
                            Assalamu'alaikum Warahmatullahi Wabarakatuh
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4 text-justify letter-preview-body" style="line-height: 1.7;">
                            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                                {!! $letter->isi_surat !!}
                            @else
                                {!! nl2br(e($letter->isi_surat)) !!}
                            @endif
                        </div>

                        <div class="fst-italic fw-semibold text-dark mb-5">
                            Wallahul Muwaffiq Ila Aqwamith Thariq<br>
                            Wassalamu'alaikum Warahmatullahi Wabarakatuh
                        </div>
                    @endif

                    <!-- Blok Tanda Tangan 2 Kolom -->
                    <div class="text-center fw-bold mb-4">
                        @if($letter->isLevelPac())
                            PIMPINAN ANAK CABANG<br>
                            IKATAN SARJANA NAHDLATUL ULAMA KECAMATAN {{ strtoupper($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? 'SURABAYA')) }}
                        @else
                            PIMPINAN CABANG<br>
                            IKATAN SARJANA NAHDLATUL ULAMA KOTA SURABAYA
                        @endif
                    </div>

                    <div class="row text-center g-4">
                        <!-- Kolom Ketua (Kiri) -->
                        <div class="col-6">
                            <div class="d-flex align-items-center justify-content-center my-2" style="height: 110px;">
                                @if($letter->chairman_qr_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($letter->chairman_qr_path))
                                    <img src="{{ asset('storage/' . $letter->chairman_qr_path) }}" alt="QR Ketua" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: contain;">
                                @else
                                    <div class="p-3 border rounded-3 bg-light text-muted small fst-italic" style="width: 130px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                        Belum TTD Ketua
                                    </div>
                                @endif
                            </div>
                            <div class="fw-bold text-decoration-underline">{{ $letter->chairman_name ?? '....................................' }}</div>
                            <div class="small text-dark fw-semibold">{{ $letter->chairman_position ?? 'Ketua' }}</div>
                        </div>

                        <!-- Kolom Sekretaris (Kanan) -->
                        <div class="col-6">
                            <div class="d-flex align-items-center justify-content-center my-2" style="height: 110px;">
                                @if($letter->secretary_qr_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($letter->secretary_qr_path))
                                    <img src="{{ asset('storage/' . $letter->secretary_qr_path) }}" alt="QR Sekretaris" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: contain;">
                                @else
                                    <div class="p-3 border rounded-3 bg-light text-muted small fst-italic" style="width: 130px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                        Belum TTD Sekretaris
                                    </div>
                                @endif
                            </div>
                            <div class="fw-bold text-decoration-underline">{{ $letter->secretary_name ?? '....................................' }}</div>
                            <div class="small text-dark fw-semibold">{{ $letter->secretary_position ?? 'Sekretaris' }}</div>
                        </div>
                    </div>

                    <!-- Security Note Resmi SIKAP-ISNU -->
                    <div class="text-center text-muted small mt-4 pt-2 border-top border-dashed" style="font-size: 0.75rem;">
                        Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode di atas untuk verifikasi otentisitas dokumen. Kode Surat: {{ $letter->letter_code }}.
                    </div>

                    <!-- Footer Banner Resmi ISNU -->
                    <div class="mt-3 d-flex align-items-center overflow-hidden rounded-3 shadow-none" style="height: 38px;">
                        <div class="flex-shrink-0" style="width: 14.8%; height: 100%; background-color: #00af50;"></div>
                        <div class="flex-grow-1 d-flex align-items-center px-3 text-white small fw-medium" style="height: 100%; background-color: #4f81bc; font-size: 0.8rem; font-family: 'Trebuchet MS', Arial, sans-serif;">
                            Dari Kontemplasi Menjadi Aksi [] Menyapa Semua [] Merangkul Umat [] Menanam untuk Bangsa
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right Action Sidebar (Digital Signatures & Workflow) -->
        <div class="col-lg-4">

            <!-- Panel Pengajuan jika masih Draft atau Ditolak / Revisi -->
            @if(in_array($letter->status, ['draft', 'ditolak']) && auth()->user()->canEditLetter($letter))
                <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-2">
                            {{ $letter->status === 'ditolak' ? 'Ajukan Ulang ke Sekretaris' : 'Ajukan ke Sekretaris' }}
                        </h6>
                        <p class="small text-muted mb-3">
                            {{ $letter->status === 'ditolak' 
                                ? 'Konsep surat memerlukan revisi. Anda dapat menyunting surat ini kemudian mengajukannya kembali ke Sekretaris.' 
                                : 'Surat ini masih berstatus draft. Klik tombol di bawah untuk meneruskan ke antrean penandatanganan Sekretaris.' }}
                        </p>
                        <div class="d-flex flex-column gap-2">
                            <a href="{{ route('admin.letters.edit', $letter->id) }}" class="btn btn-outline-primary rounded-pill fw-semibold">
                                <i class="bi bi-pencil-square me-1"></i> Sunting Konsep Surat
                            </a>
                            <form action="{{ route('admin.letters.submit-secretary', $letter->id) }}" method="POST" @submit="isSubmittingToSecretary = true">
                                @csrf
                                <button type="submit" class="btn btn-warning text-dark w-100 rounded-pill fw-semibold d-inline-flex align-items-center justify-content-center" :disabled="isSubmittingToSecretary">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSubmittingToSecretary" x-cloak></span>
                                    <i class="bi bi-send-fill me-1" x-show="!isSubmittingToSecretary"></i>
                                    <span x-text="isSubmittingToSecretary ? 'Mengajukan...' : '{{ $letter->status === 'ditolak' ? 'Ajukan Ulang ke Sekretaris' : 'Ajukan ke Sekretaris' }}'"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CARD E-TTD TAHAP 1: SEKRETARIS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 @if($letter->secretary_signed_at) border-start border-success border-4 @else border-start border-secondary border-4 @endif">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Tahap 1</span>
                        @if($letter->secretary_signed_at)
                            <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                        @else
                            <span class="badge bg-warning text-dark rounded-pill"><i class="bi bi-clock-history me-1"></i>Menunggu</span>
                        @endif
                    </div>

                    <h6 class="fw-bold text-dark mb-1">E-TTD Sekretaris</h6>
                    <p class="small text-muted mb-3">Penandatanganan digital tahap pertama menggunakan QRCode resmi.</p>

                    @if($letter->secretary_signed_at)
                        <div class="p-3 bg-light rounded-3 small">
                            <div class="fw-bold text-success mb-1"><i class="bi bi-shield-check me-1"></i>Telah Ditandatangani</div>
                            <div><strong>Penandatangan:</strong> {{ $letter->secretary_name }}</div>
                            <div><strong>Jabatan:</strong> {{ $letter->secretary_position }}</div>
                            <div><strong>No. Registrasi:</strong> {{ $letter->secretary_registration_number }}</div>
                            <div><strong>Waktu TTD:</strong> {{ $letter->secretary_signed_at->format('d/m/Y H:i:s') }} WIB</div>
                        </div>
                    @else
                        @if(auth()->user()->canSignAsSecretary($letter) && in_array($letter->status, ['menunggu_sekretaris', 'draft']))
                            <div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-3">
                                Anda memiliki wewenang untuk menandatangani surat ini sebagai Sekretaris.
                            </div>
                            <form id="formSignSecretary" action="{{ route('admin.letters.sign-secretary', $letter->id) }}" method="POST" class="mb-2" @submit="isSigningSec = true">
                                @csrf
                                <button type="button" class="btn btn-success w-100 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#signSecretaryModal" :disabled="isSigningSec">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSigningSec" x-cloak></span>
                                    <i class="bi bi-pen-fill me-1" x-show="!isSigningSec"></i>
                                    <span x-text="isSigningSec ? 'Memproses E-TTD...' : 'Tandatangani Digital (Sekretaris)'">Tandatangani Digital (Sekretaris)</span>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalReject">
                                <i class="bi bi-x-circle me-1"></i> Minta Revisi / Tolak
                            </button>
                        @else
                            <div class="p-3 bg-light rounded-3 text-muted small fst-italic">
                                Menunggu penandatanganan digital oleh Sekretaris atau Wakil Sekretaris.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- CARD E-TTD TAHAP 2: KETUA -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 @if($letter->chairman_signed_at) border-start border-success border-4 @else border-start border-secondary border-4 @endif">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Tahap 2</span>
                        @if($letter->chairman_signed_at)
                            <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                        @else
                            <span class="badge bg-secondary rounded-pill"><i class="bi bi-clock-history me-1"></i>Antre</span>
                        @endif
                    </div>

                    <h6 class="fw-bold text-dark mb-1">E-TTD Ketua</h6>
                    <p class="small text-muted mb-3">Penandatanganan digital tahap kedua setelah Sekretaris menyelesaikan tahap pertama.</p>

                    @if($letter->chairman_signed_at)
                        <div class="p-3 bg-light rounded-3 small">
                            <div class="fw-bold text-success mb-1"><i class="bi bi-shield-check me-1"></i>Telah Ditandatangani</div>
                            <div><strong>Penandatangan:</strong> {{ $letter->chairman_name }}</div>
                            <div><strong>Jabatan:</strong> {{ $letter->chairman_position }}</div>
                            <div><strong>No. Registrasi:</strong> {{ $letter->chairman_registration_number }}</div>
                            <div><strong>Waktu TTD:</strong> {{ $letter->chairman_signed_at->format('d/m/Y H:i:s') }} WIB</div>
                        </div>
                    @else
                        @if(!$letter->secretary_signed_at)
                            <div class="alert alert-secondary py-2 px-3 small border-0 rounded-3 mb-0">
                                <i class="bi bi-lock-fill me-1"></i> Surat harus ditandatangani oleh Sekretaris terlebih dahulu sebelum dapat ditandatangani oleh Ketua.
                            </div>
                        @elseif(auth()->user()->canSignAsChairman($letter) && $letter->status === 'menunggu_ketua')
                            <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-3">
                                Sekretaris telah menandatangani surat ini. Anda dapat melakukan penandatanganan digital final sebagai Ketua.
                            </div>
                            <form id="formSignChairman" action="{{ route('admin.letters.sign-chairman', $letter->id) }}" method="POST" class="mb-2" @submit="isSigningChair = true">
                                @csrf
                                <button type="button" class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#signChairmanModal" :disabled="isSigningChair">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSigningChair" x-cloak></span>
                                    <i class="bi bi-award-fill me-1" x-show="!isSigningChair"></i>
                                    <span x-text="isSigningChair ? 'Memproses E-TTD...' : 'Tandatangani Digital (Ketua)'">Tandatangani Digital (Ketua)</span>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalReject">
                                <i class="bi bi-x-circle me-1"></i> Minta Revisi / Tolak
                            </button>
                        @else
                            <div class="p-3 bg-light rounded-3 text-muted small fst-italic">
                                Menunggu penandatanganan digital oleh Ketua atau Wakil Ketua.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Download & Verifikasi Box -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">Dokumen & Verifikasi</h6>
                    <div class="d-grid gap-2">
                        @if($letter->status === 'selesai')
                            <button type="button" 
                                    class="btn btn-outline-success rounded-3 text-start d-flex align-items-center justify-content-between p-3 w-100"
                                    :disabled="isDownloadingPdf"
                                    @click="downloadPdfWithSpinner('{{ route('admin.letters.download-pdf', $letter->id) }}', 'Surat_{{ \Illuminate\Support\Str::slug($letter->letter_number, '_') }}.pdf', (val) => isDownloadingPdf = val)">
                                <div>
                                    <div class="fw-bold d-flex align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm text-success" role="status" aria-hidden="true" x-show="isDownloadingPdf" x-cloak></span>
                                        <i class="bi bi-file-earmark-pdf" x-show="!isDownloadingPdf"></i>
                                        <span x-text="isDownloadingPdf ? 'Sedang Mengunduh PDF...' : 'File Surat Resmi (.PDF)'">File Surat Resmi (.PDF)</span>
                                    </div>
                                    <div class="small text-muted" x-text="isDownloadingPdf ? 'Menyiapkan berkas dokumen...' : 'Format standar siap edar & print'">Format standar siap edar & print</div>
                                </div>
                                <div>
                                    <span class="spinner-border spinner-border-sm text-success" role="status" aria-hidden="true" x-show="isDownloadingPdf" x-cloak></span>
                                    <i class="bi bi-download" x-show="!isDownloadingPdf"></i>
                                </div>
                            </button>
                        @else
                            <div class="p-3 border rounded-3 bg-light text-muted d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold d-flex align-items-center gap-2 text-secondary">
                                        <i class="bi bi-lock-fill"></i>
                                        <span>File Surat Resmi (.PDF) Terkunci</span>
                                    </div>
                                    <div class="small text-muted">Dapat diunduh setelah surat selesai ditandatangani lengkap.</div>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">Terkunci</span>
                            </div>
                        @endif
                        <a href="{{ route('surat.verifikasi', $letter->letter_code) }}" target="_blank" class="btn btn-light border rounded-3 text-start d-flex align-items-center justify-content-between p-3">
                            <div>
                                <div class="fw-bold"><i class="bi bi-patch-check me-2 text-info"></i>Halaman Verifikasi Publik</div>
                                <div class="small text-muted">Akses publik via scan QRCode</div>
                            </div>
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Reject / Minta Revisi -->
    <div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.letters.reject', $letter->id) }}" method="POST" class="modal-content rounded-4 border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Minta Revisi Konsep Surat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Berikan catatan perbaikan agar pengaju dapat memperbarui konsep surat ini:</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan Revisi / Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="rejection_note" rows="4" class="form-control" required placeholder="Tuliskan catatan perbaikan di sini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger text-white rounded-pill px-4 fw-semibold d-inline-flex align-items-center justify-content-center shadow-sm"
                            onclick="if(this.form.checkValidity()){ this.disabled = true; this.innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\' aria-hidden=\'true\'></span>Mengembalikan...'; this.form.submit(); }">
                        <i class="bi bi-x-circle me-1"></i> Kembalikan untuk Revisi
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(auth()->user()->canSignAsSecretary() && in_array($letter->status, ['menunggu_sekretaris', 'draft']))
    <!-- Modal Konfirmasi E-TTD Sekretaris -->
    <div class="modal fade" id="signSecretaryModal" tabindex="-1" aria-labelledby="signSecretaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-center position-relative">
                    <div class="p-3 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-pen-fill fs-2"></i>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <h5 class="modal-title fw-bold text-dark mb-2" id="signSecretaryModalLabel">Konfirmasi E-TTD Sekretaris</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menandatangani surat resmi ini secara digital sebagai <strong>Sekretaris / Wakil Sekretaris</strong>?
                    </p>
                    <div class="alert alert-light border rounded-3 p-3 mt-3 mb-0 text-start small text-muted">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-info-circle-fill text-success"></i>
                            <span class="fw-semibold text-dark">Informasi Penandatanganan:</span>
                        </div>
                        <div>Nama: <strong>{{ auth()->user()->name }}</strong></div>
                        <div>Jabatan: <strong>{{ auth()->user()->position ?? 'Sekretaris PC ISNU Kota Surabaya' }}</strong></div>
                        <div class="mt-1 text-secondary">Tanda tangan digital (QR Code) akan dibubuhkan dan status surat otomatis diteruskan ke tahap verifikasi Ketua.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-success text-white rounded-pill flex-fill py-2 fw-semibold d-inline-flex align-items-center justify-content-center shadow-sm"
                            id="btnConfirmSignSecretary"
                            onclick="this.disabled = true; this.innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\' aria-hidden=\'true\'></span>Memproses E-TTD...'; document.getElementById('formSignSecretary').submit();">
                        <i class="bi bi-check-circle me-1"></i> Ya, Tandatangani
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(auth()->user()->canSignAsChairman() && $letter->status === 'menunggu_ketua')
    <!-- Modal Konfirmasi E-TTD Ketua -->
    <div class="modal fade" id="signChairmanModal" tabindex="-1" aria-labelledby="signChairmanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-center position-relative">
                    <div class="p-3 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-award-fill fs-2"></i>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <h5 class="modal-title fw-bold text-dark mb-2" id="signChairmanModalLabel">Konfirmasi E-TTD Ketua</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menyelesaikan dan menandatangani surat ini secara digital sebagai <strong>Ketua / Wakil Ketua</strong>?
                    </p>
                    <div class="alert alert-light border rounded-3 p-3 mt-3 mb-0 text-start small text-muted">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-info-circle-fill text-primary"></i>
                            <span class="fw-semibold text-dark">Informasi Penandatanganan Final:</span>
                        </div>
                        <div>Nama: <strong>{{ auth()->user()->name }}</strong></div>
                        <div>Jabatan: <strong>{{ auth()->user()->position ?? 'Ketua PC ISNU Kota Surabaya' }}</strong></div>
                        <div class="mt-1 text-secondary">Status surat akan menjadi <span class="badge bg-success-subtle text-success">Selesai</span> dan berkas surat resmi siap diedarkan serta divalidasi.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary text-white rounded-pill flex-fill py-2 fw-semibold d-inline-flex align-items-center justify-content-center shadow-sm"
                            id="btnConfirmSignChairman"
                            onclick="this.disabled = true; this.innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\' aria-hidden=\'true\'></span>Memproses E-TTD...'; document.getElementById('formSignChairman').submit();">
                        <i class="bi bi-check-circle me-1"></i> Ya, Sahkan dan Terbitkan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
 
    @if(auth()->user()->isSuperAdmin() || (($letter->status === 'draft' || $letter->status === 'ditolak') && auth()->user()->canEditLetter($letter)))
    <!-- Modal Hapus Surat -->
    <div class="modal fade" id="deleteLetterModal" tabindex="-1" aria-labelledby="deleteLetterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="bi bi-trash3-fill fs-2"></i>
                        </div>
                    </div>
                    <h5 class="modal-title fw-bold text-dark mb-2" id="deleteLetterModalLabel">Hapus Surat</h5>
                    <p class="text-muted small mb-0">
                        Apakah Anda yakin ingin menghapus surat nomor:
                    </p>
                    <div class="fw-bold text-dark fs-6 my-2 bg-light p-2 rounded-3 border">
                        {{ $letter->letter_number }}
                    </div>
                    @if($letter->status === 'selesai')
                        <div class="alert alert-warning text-start small mt-3 mb-0 rounded-3">
                            <i class="bi bi-shield-exclamation text-danger me-1"></i>
                            <strong>Peringatan Khusus Super Admin:</strong> Surat ini telah selesai ditandatangani secara digital (E-TTD lengkap). Penghapusan akan menghapus seluruh arsip PDF, DOCX, dan riwayat tanda tangan digital secara permanen.
                        </div>
                    @else
                        <p class="text-muted small mb-0 mt-2">
                            Tindakan ini tidak dapat dibatalkan. Berkas konsep dan pratinjau surat akan dihapus dari sistem.
                        </p>
                    @endif
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <form action="{{ route('admin.letters.destroy', $letter->id) }}" method="POST" class="flex-fill m-0" onsubmit="document.getElementById('btnConfirmDeleteLetter').disabled = true; document.getElementById('btnConfirmDeleteLetter').innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\' role=\'status\' aria-hidden=\'true\'></span>Menghapus...';">
                        @csrf
                        @method('DELETE')
                        <button type="submit" id="btnConfirmDeleteLetter" class="btn btn-danger text-white rounded-pill w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-trash me-1"></i> Ya, Hapus Surat
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
