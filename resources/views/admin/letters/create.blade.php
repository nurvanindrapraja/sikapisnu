@extends('layouts.admin')

@section('title', 'Buat Konsep Surat Baru')
@section('header_title', 'Pengajuan Konsep Surat Resmi')

@section('styles')
<link href="{{ asset('vendor/quill/quill.snow.css') }}" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-color: #dee2e6;
        background-color: #f8f9fa;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        border-color: #dee2e6;
        font-family: inherit;
        font-size: 0.95rem;
        min-height: 240px;
    }
    .ql-editor {
        min-height: 240px;
        line-height: 1.6;
    }
    .ql-editor.ql-blank::before {
        color: #adb5bd;
        font-style: normal;
    }
    /* Styling Tabel di dalam Rich Text Editor */
    .ql-editor table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0;
    }
    .ql-editor table.table-bordered,
    .ql-editor table[border="1"] {
        border: 1px solid #333333 !important;
    }
    .ql-editor table.table-bordered th,
    .ql-editor table.table-bordered td,
    .ql-editor table[border="1"] th,
    .ql-editor table[border="1"] td {
        border: 1px solid #333333 !important;
        padding: 6px 10px;
        vertical-align: top;
    }
    .ql-editor table.table-bordered th,
    .ql-editor table[border="1"] th {
        background-color: #f1f5f9;
        font-weight: 600;
    }
    .ql-editor table.table-borderless,
    .ql-editor table[border="0"],
    .ql-editor table:not(.table-bordered):not([border="1"]) {
        border: none !important;
    }
    .ql-editor table.table-borderless th,
    .ql-editor table.table-borderless td,
    .ql-editor table[border="0"] th,
    .ql-editor table[border="0"] td,
    .ql-editor table:not(.table-bordered):not([border="1"]) th,
    .ql-editor table:not(.table-bordered):not([border="1"]) td {
        border: none !important;
        padding: 4px 8px;
        vertical-align: top;
        outline: 1px dashed #cbd5e1;
    }
    .ql-editor table td:focus,
    .ql-editor table th:focus {
        background-color: #f0fdf4;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0" x-data="{ isSubmitting: false, activeBtn: '', letterType: '{{ old('letter_type', $type ?? 'surat_biasa') }}', letterLevel: '{{ old('level', $defaultLevel ?? 'PC ISNU') }}' }">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Form Pengajuan Konsep Surat</h4>
            <p class="text-muted small mb-0">Surat resmi diterbitkan dalam format PDF ber-QRCode dan ditandatangani digital bertahap (Sekretaris lalu Ketua).</p>
        </div>
        <a href="{{ route('admin.letters.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan input:</h6>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ route('admin.letters.store') }}" method="POST" id="letterForm">
                        @csrf

                        <!-- Pilihan Jenis Surat (Tab / Selector) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">
                                <i class="bi bi-ui-checks me-1 text-success"></i>Pilih Format / Jenis Surat Resmi <span class="text-danger">*</span>
                            </label>
                            <div class="row g-3">
                                <!-- Surat Biasa / Dinas -->
                                <div class="col-md-4">
                                    <div class="card h-100 border p-3 rounded-4 transition-all"
                                         style="cursor: pointer;"
                                         :class="letterType === 'surat_biasa' ? 'border-success bg-success-subtle shadow-sm' : 'bg-white shadow-none'"
                                         @click="letterType = 'surat_biasa'">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="p-2 rounded-3" :class="letterType === 'surat_biasa' ? 'bg-success text-white' : 'bg-light text-success'">
                                                <i class="bi bi-file-earmark-text-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">Surat Dinas / Biasa</div>
                                                <span class="badge bg-success-subtle text-success small">Format Baku Umum</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                            Undangan, permohonan narasumber, audiensi, atau surat keluar berkop dan perihal.
                                        </p>
                                    </div>
                                </div>

                                <!-- Surat Mandat -->
                                <div class="col-md-4">
                                    <div class="card h-100 border p-3 rounded-4 transition-all"
                                         style="cursor: pointer;"
                                         :class="letterType === 'surat_mandat' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-white shadow-none'"
                                         @click="letterType = 'surat_mandat'">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="p-2 rounded-3" :class="letterType === 'surat_mandat' ? 'bg-primary text-white' : 'bg-light text-primary'">
                                                <i class="bi bi-award-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">Surat Mandat</div>
                                                <span class="badge bg-primary-subtle text-primary small">Delegasi & Penugasan</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                            Mandat delegasi resmi PC ISNU kepada anggota/pengurus untuk mengikuti kegiatan.
                                        </p>
                                    </div>
                                </div>

                                <!-- Surat Rekomendasi -->
                                <div class="col-md-4">
                                    <div class="card h-100 border p-3 rounded-4 transition-all"
                                         style="cursor: pointer;"
                                         :class="letterType === 'surat_rekomendasi' ? 'border-warning bg-warning-subtle shadow-sm' : 'bg-white shadow-none'"
                                         @click="letterType = 'surat_rekomendasi'">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="p-2 rounded-3" :class="letterType === 'surat_rekomendasi' ? 'bg-warning text-dark' : 'bg-light text-warning'">
                                                <i class="bi bi-patch-check-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">Surat Rekomendasi</div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis small">Rekomendasi Resmi</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-0" style="font-size: 0.78rem;">
                                            Rekomendasi resmi organisasi untuk beasiswa, pelantikan, kerja, atau studi lanjut.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="letter_type" :value="letterType">
                        </div>

                        <h5 class="fw-bold text-success border-bottom pb-2 mb-3">
                            <i class="bi bi-file-earmark-ruled me-2"></i>Identitas & Tingkat Surat
                        </h5>

                        <!-- Tingkat Surat & Wilayah PAC -->
                        <div class="row g-3 mb-3 p-3 bg-light rounded-4 border">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Tingkat / Level Surat <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="level" id="levelPC" value="PC ISNU" x-model="letterLevel" {{ old('level', $defaultLevel ?? 'PC ISNU') === 'PC ISNU' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="levelPC">
                                            PC ISNU (Tingkat Kota)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="level" id="levelPAC" value="PAC ISNU" x-model="letterLevel" {{ old('level', $defaultLevel ?? 'PC ISNU') === 'PAC ISNU' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="levelPAC">
                                            PAC ISNU (Tingkat Kecamatan)
                                        </label>
                                    </div>
                                </div>
                                <div class="form-text small">
                                    Penandatangan otomatis disesuaikan: Sekretaris & Ketua PC (level PC) atau Sekretaris & Ketua PAC (level PAC).
                                </div>
                            </div>
                            <div class="col-md-6" x-show="letterLevel === 'PAC ISNU'" x-cloak>
                                <label class="form-label fw-semibold small">Pilih Wilayah PAC ISNU <span class="text-danger">*</span></label>
                                <select name="pac_id" class="form-select @error('pac_id') is-invalid @enderror" :required="letterLevel === 'PAC ISNU'">
                                    <option value="">-- Pilih PAC ISNU Kecamatan --</option>
                                    @foreach($pacs as $pac)
                                        <option value="{{ $pac->id }}" {{ (string) old('pac_id', $defaultPacId ?? '') === (string) $pac->id ? 'selected' : '' }}>
                                            PAC ISNU {{ $pac->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">Hanya akun Sekretaris PAC dan Ketua PAC wilayah ini yang berhak menandatangani surat.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text" name="letter_number" class="form-control @error('letter_number') is-invalid @enderror" 
                                       value="{{ old('letter_number', $suggestedNumber) }}" required placeholder="Contoh: 001/PC-VI/ISNU-SBY/XI/2026">
                                <div class="form-text small">Nomor surat resmi yang akan tertera pada kop dokumen.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Bulan Romawi <span class="text-danger">*</span></label>
                                <input type="text" name="roman_month" class="form-control @error('roman_month') is-invalid @enderror" 
                                       value="{{ old('roman_month', $romanMonth) }}" required placeholder="XI">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Tahun <span class="text-danger">*</span></label>
                                <input type="text" name="year" class="form-control @error('year') is-invalid @enderror" 
                                       value="{{ old('year', $year) }}" required placeholder="2026">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div :class="letterType === 'surat_biasa' ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label fw-semibold small">Kota Asal Surat <span class="text-danger">*</span></label>
                                <input type="text" name="kota_asal" class="form-control @error('kota_asal') is-invalid @enderror" 
                                       value="{{ old('kota_asal', 'Surabaya') }}" required placeholder="Contoh: Surabaya">
                                <div class="form-text small">Tempat surat ditetapkan (tertera di tanggal surat).</div>
                            </div>
                            <div :class="letterType === 'surat_biasa' ? 'col-md-4' : 'col-md-6'">
                                <label class="form-label fw-semibold small">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" name="date_letter" class="form-control @error('date_letter') is-invalid @enderror" 
                                       value="{{ old('date_letter', $todayDate) }}" required>
                            </div>
                            <div class="col-md-4" x-show="letterType === 'surat_biasa'">
                                <label class="form-label fw-semibold small">Lampiran</label>
                                <input type="text" name="lampiran" class="form-control @error('lampiran') is-invalid @enderror" 
                                       value="{{ old('lampiran', '-') }}" placeholder="Contoh: - atau 1 (Satu) Berkas">
                            </div>
                        </div>

                        <!-- Perihal (Untuk Surat Biasa dan Surat Rekomendasi) -->
                        <div class="mb-3" x-show="letterType !== 'surat_mandat'">
                            <label class="form-label fw-semibold small">
                                <span x-show="letterType === 'surat_biasa'">Perihal / Hal Surat <span class="text-danger">*</span></span>
                                <span x-show="letterType === 'surat_rekomendasi'">Perihal / Keperluan Rekomendasi <span class="text-danger">*</span></span>
                            </label>
                            <input type="text" name="perihal" class="form-control @error('perihal') is-invalid @enderror" 
                                   value="{{ old('perihal') }}" 
                                   :placeholder="letterType === 'surat_rekomendasi' ? 'Contoh: Rekomendasi Beasiswa Studi Lanjut S3 Universitas Airlangga' : 'Contoh: Undangan Rapat Kerja Cabang II PC ISNU Surabaya'">
                        </div>

                        <!-- Form Khusus: Informasi Kegiatan Surat Mandat -->
                        <div x-show="letterType === 'surat_mandat'" class="mt-4">
                            <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-calendar-event me-2"></i>Informasi Kegiatan yang Dimandatkan
                            </h5>
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold small">Nama Kegiatan <span class="text-danger">*</span></label>
                                    <input type="text" name="kegiatan" class="form-control @error('kegiatan') is-invalid @enderror" 
                                           value="{{ old('kegiatan') }}" placeholder="Contoh: Konferensi Wilayah ISNU Jawa Timur Tahun 2026">
                                    <div class="form-text small">Nama kegiatan yang akan dihadiri oleh penerima mandat.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Tanggal Pelaksanaan Kegiatan <span class="text-danger">*</span></label>
                                    <input type="text" name="tanggal_pelaksanaan" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" 
                                           value="{{ old('tanggal_pelaksanaan') }}" placeholder="Contoh: 14 - 16 November 2026">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Lokasi / Tempat Pelaksanaan <span class="text-danger">*</span></label>
                                    <input type="text" name="lokasi_pelaksanaan" class="form-control @error('lokasi_pelaksanaan') is-invalid @enderror" 
                                           value="{{ old('lokasi_pelaksanaan') }}" placeholder="Contoh: Asrama Haji Sukolilo Surabaya">
                                </div>
                            </div>
                        </div>

                        <!-- Form Khusus: Penerima / Tujuan Surat (Hanya untuk Surat Biasa) -->
                        <div x-show="letterType === 'surat_biasa'">
                            <h5 class="fw-bold text-success border-bottom pb-2 mt-4 mb-3">
                                <i class="bi bi-geo-alt me-2"></i>Penerima / Tujuan Surat
                            </h5>

                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold small">Tujuan Surat (Kepada Yth.) <span class="text-danger">*</span></label>
                                    <input type="text" name="tujuan_surat" class="form-control @error('tujuan_surat') is-invalid @enderror" 
                                           value="{{ old('tujuan_surat') }}" placeholder="Contoh: Ketua Pimpinan Wilayah ISNU Jawa Timur">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold small">Kota / Lokasi Tujuan <span class="text-danger">*</span></label>
                                    <input type="text" name="kota_tujuan" class="form-control @error('kota_tujuan') is-invalid @enderror" 
                                           value="{{ old('kota_tujuan', 'Surabaya') }}" placeholder="Contoh: Surabaya">
                                    <div class="form-text small">Kota pihak penerima surat (tertera di "di - ...").</div>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold border-bottom pb-2 mt-4 mb-3"
                            :class="letterType === 'surat_mandat' ? 'text-primary' : (letterType === 'surat_rekomendasi' ? 'text-warning-emphasis' : 'text-success')">
                            <span x-show="letterType === 'surat_biasa'"><i class="bi bi-body-text me-2"></i>Isi Surat</span>
                            <span x-show="letterType === 'surat_mandat'"><i class="bi bi-people me-2"></i>Daftar Penerima Mandat (Anggota / Pengurus)</span>
                            <span x-show="letterType === 'surat_rekomendasi'"><i class="bi bi-person-lines-fill me-2"></i>Identitas yang Direkomendasikan &amp; Keterangan Rekomendasi</span>
                        </h5>

                        <div class="mb-4">
                            <!-- Table Toolbar Controls Header -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                <label class="form-label fw-semibold small mb-0">
                                    <span x-show="letterType === 'surat_biasa'">Teks Isi Surat (Editor HTML) <span class="text-danger">*</span></span>
                                    <span x-show="letterType === 'surat_mandat'">Daftar Nama yang Dimandatkan (Tabel/Teks) <span class="text-danger">*</span></span>
                                    <span x-show="letterType === 'surat_rekomendasi'">Teks Isi Rekomendasi &amp; Biodata <span class="text-danger">*</span></span>
                                </label>
                                
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <!-- Dropdown Sisipkan Tabel -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-success dropdown-toggle rounded-pill px-3 shadow-none fw-medium" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-table me-1"></i> Sisipkan Tabel / Format
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2" style="min-width: 280px;">
                                            <li>
                                                <h6 class="dropdown-header text-uppercase small fw-bold text-muted px-2 py-1">Pilihan Format Baku</h6>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" onclick="insertPredefinedTable('mandat')">
                                                    <i class="bi bi-award text-primary fs-5"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">Tabel Penerima Mandat</div>
                                                        <small class="text-muted d-block">Tabel 4 Kolom: No, Nama, NIK, Jabatan</small>
                                                    </div>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" onclick="insertPredefinedTable('rekomendasi')">
                                                    <i class="bi bi-person-badge text-warning fs-5"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">Format Biodata Rekomendasi</div>
                                                        <small class="text-muted d-block">Nama, NIRA, TTL, Jabatan, Keterangan</small>
                                                    </div>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-2"></li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" onclick="insertPredefinedTable('borderless')">
                                                    <i class="bi bi-calendar-event text-success fs-5"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">Tabel Rincian (Tanpa Border)</div>
                                                        <small class="text-muted d-block">Untuk Hari/Tgl, Waktu, Tempat, Acara</small>
                                                    </div>
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" onclick="insertPredefinedTable('bordered')">
                                                    <i class="bi bi-grid-3x3 text-secondary fs-5"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">Tabel Agenda/Data (Dengan Border)</div>
                                                        <small class="text-muted d-block">Tabel bergaris + Header No, Waktu, Acara, PJ</small>
                                                    </div>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-2"></li>
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#customTableModal">
                                                    <i class="bi bi-sliders text-secondary fs-5"></i>
                                                    <div>
                                                        <div class="fw-semibold text-dark">Kustom Tabel...</div>
                                                        <small class="text-muted d-block">Tentukan baris, kolom &amp; jenis border</small>
                                                    </div>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Table Active Toolbar (muncul saat kursor berada di dalam tabel) -->
                                    <div id="tableActionsGroup" class="d-none d-inline-flex align-items-center gap-1 bg-light p-1 rounded-pill border">
                                        <button type="button" id="btnTableBorderless" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" onclick="toggleActiveTableBorder('borderless')" title="Ubah menjadi Tanpa Border">
                                            <i class="bi bi-border-none me-1"></i>Tanpa Border
                                        </button>
                                        <button type="button" id="btnTableBordered" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" onclick="toggleActiveTableBorder('bordered')" title="Ubah menjadi Bergaris">
                                            <i class="bi bi-border-all me-1"></i>Bergaris
                                        </button>

                                        <!-- Dropdown Lebar Kolom -->
                                        <div class="dropdown d-inline-block">
                                            <button type="button" class="btn btn-xs btn-light border rounded-pill px-2 py-1 fw-medium dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Atur Lebar Kolom">
                                                <i class="bi bi-arrows-expand me-1"></i>Lebar Kolom
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2 small" style="min-width: 230px;">
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" onclick="openColumnWidthModal()">
                                                        <i class="bi bi-sliders text-primary fs-6"></i>
                                                        <div>
                                                            <div class="fw-semibold">Atur Semua Kolom...</div>
                                                            <small class="text-muted">Tentukan % lebar tiap kolom</small>
                                                        </div>
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 py-1 d-flex align-items-center gap-2" onclick="stepActiveColumnWidth(5)">
                                                        <i class="bi bi-arrows-angle-expand text-success"></i> Lebarkan Kolom Ini (+5%)
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 py-1 d-flex align-items-center gap-2" onclick="stepActiveColumnWidth(-5)">
                                                        <i class="bi bi-arrows-angle-contract text-warning"></i> Sempitkan Kolom Ini (-5%)
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 py-1 d-flex align-items-center gap-2" onclick="distributeColumnsEvenly()">
                                                        <i class="bi bi-distribute-horizontal text-secondary"></i> Bagi Rata Semua Kolom
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item rounded-2 py-1 d-flex align-items-center gap-2" onclick="applyPresetRincianWidth()">
                                                        <i class="bi bi-layout-three-columns text-info"></i> Format Rincian (25% / 5% / 70%)
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>

                                        <button type="button" class="btn btn-xs btn-light border rounded-pill px-2 py-1" onclick="tableAddRow()" title="Tambah Baris Baru">
                                            <i class="bi bi-plus-lg me-1"></i>+ Baris
                                        </button>
                                        <button type="button" class="btn btn-xs btn-light border rounded-pill px-2 py-1 text-danger" onclick="tableDeleteRow()" title="Hapus Baris Ini">
                                            <i class="bi bi-dash-lg me-1"></i>- Baris
                                        </button>
                                        <button type="button" class="btn btn-xs btn-light border rounded-circle text-danger" onclick="tableDelete()" title="Hapus Seluruh Tabel">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="quill-wrapper" x-ignore>
                                <div id="quill-editor" class="bg-white">{!! old('isi_surat') !!}</div>
                            </div>
                            <textarea name="isi_surat" id="isi_surat_input" class="d-none">{{ old('isi_surat') }}</textarea>
                            @error('isi_surat')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="isi_surat_error" class="text-danger small mt-1 d-none">
                                <i class="bi bi-exclamation-circle me-1"></i>Teks isi surat tidak boleh kosong.
                            </div>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i> Editor mendukung teks tebal, miring, garis bawah, coret, perataan, penomoran/bullet, serta tabel (baik bergaris maupun tanpa garis). Salam pembuka (Assalamu'alaikum...) dan salam penutup (Wassalamu'alaikum...) otomatis disertakan oleh sistem.
                            </div>
                        </div>

                        <input type="hidden" name="action" id="formAction" value="draft">

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top">
                            <a href="{{ route('admin.letters.index') }}" class="btn btn-outline-secondary rounded-pill px-4" :class="{ 'disabled': isSubmitting }">
                                Batal
                            </a>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light border rounded-pill px-4 d-inline-flex align-items-center justify-content-center"
                                        :disabled="isSubmitting"
                                        @click="if (submitWithAction('draft')) { isSubmitting = true; activeBtn = 'draft'; }">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSubmitting && activeBtn === 'draft'" x-cloak></span>
                                    <i class="bi bi-save me-1" x-show="!(isSubmitting && activeBtn === 'draft')"></i>
                                    <span x-text="isSubmitting && activeBtn === 'draft' ? 'Menyimpan...' : 'Simpan Draft'">Simpan Draft</span>
                                </button>
                                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm d-inline-flex align-items-center justify-content-center"
                                        :disabled="isSubmitting"
                                        @click="if (submitWithAction('submit_secretary')) { isSubmitting = true; activeBtn = 'submit_secretary'; }">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSubmitting && activeBtn === 'submit_secretary'" x-cloak></span>
                                    <i class="bi bi-send-check-fill me-1" x-show="!(isSubmitting && activeBtn === 'submit_secretary')"></i>
                                    <span x-text="isSubmitting && activeBtn === 'submit_secretary' ? 'Memproses...' : 'Simpan & Ajukan ke Sekretaris'">Simpan & Ajukan ke Sekretaris</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Informasi Penerbitan & Alur TTD -->
        <div class="col-lg-4">
            <!-- Informasi Dokumen Resmi PDF -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Penerbitan Surat Resmi PDF
                    </h6>
                    <p class="small text-muted mb-3">
                        Surat diterbitkan secara resmi dalam format PDF berstandar A4 dengan kop resmi PC ISNU Kota Surabaya dan penandatanganan digital ber-QRCode.
                    </p>
                    <div class="alert alert-success py-2 px-3 small border-0 rounded-3 mb-0">
                        <i class="bi bi-shield-check me-1"></i> Keabsahan dan integritas dokumen dapat diverifikasi secara publik melalui scan QRCode E-TTD.
                    </div>
                </div>
            </div>

            <!-- Alur Penandatanganan Digital -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-shield-check text-success me-2"></i>Alur Penandatanganan Digital
                    </h6>

                    <div class="timeline-stepper small">
                        <div class="d-flex gap-3 mb-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">1</div>
                            <div>
                                <div class="fw-bold text-dark">Pengajuan Konsep</div>
                                <div class="text-muted">Admin / Pengurus mengajukan konsep surat lengkap dengan teks/tabel.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">2</div>
                            <div>
                                <div class="fw-bold text-dark">TTD Digital Sekretaris (Tahap 1)</div>
                                <div class="text-muted">Sekretaris menempelkan QRCode tanda tangan yang memuat Nama, No Registrasi, &amp; Jabatan.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 mb-3">
                            <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">3</div>
                            <div>
                                <div class="fw-bold text-dark">TTD Digital Ketua (Tahap 2)</div>
                                <div class="text-muted">Ketua menandatangani setelah Sekretaris dengan menempelkan QRCode resmi.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">4</div>
                            <div>
                                <div class="fw-bold text-dark">Surat Selesai &amp; Terverifikasi</div>
                                <div class="text-muted">Surat resmi siap diunduh (PDF) dan dapat diverifikasi melalui pemindaian QR Code.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Kustom Tabel -->
<div class="modal fade" id="customTableModal" tabindex="-1" aria-labelledby="customTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom pb-3">
                <h6 class="modal-title fw-bold text-dark" id="customTableModalLabel">
                    <i class="bi bi-table text-success me-2"></i>Kustom Sisipkan Tabel
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Jumlah Baris</label>
                        <input type="number" id="customTableRows" class="form-control" value="3" min="1" max="25">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Jumlah Kolom</label>
                        <input type="number" id="customTableCols" class="form-control" value="3" min="1" max="10">
                    </div>

                    <div class="col-12 mt-3">
                        <label class="form-label small fw-semibold mb-2 d-block">Jenis Garis (Border Tabel)</label>
                        <div class="form-check form-check-inline me-4">
                            <input class="form-check-input" type="radio" name="customTableBorder" id="borderChoiceNo" value="borderless">
                            <label class="form-check-label small" for="borderChoiceNo">
                                <strong>Tanpa Border</strong> (Bersih / Polos)
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="customTableBorder" id="borderChoiceYes" value="bordered" checked>
                            <label class="form-check-label small" for="borderChoiceYes">
                                <strong>Dengan Border</strong> (Garis Tepi 1px)
                            </label>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="customTableHeader" checked>
                            <label class="form-check-label small" for="customTableHeader">
                                Jadikan baris pertama sebagai Header judul kolom
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="insertCustomTableFromModal()">
                    <i class="bi bi-plus-lg me-1"></i>Sisipkan ke Editor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Atur Lebar Kolom Tabel -->
<div class="modal fade" id="columnWidthModal" tabindex="-1" aria-labelledby="columnWidthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom pb-3">
                <h6 class="modal-title fw-bold text-dark" id="columnWidthModalLabel">
                    <i class="bi bi-arrows-expand text-success me-2"></i>Atur Lebar Kolom Tabel
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Sesuaikan persentase atau ukuran lebar untuk setiap kolom pada tabel aktif. Total persentase ideal adalah 100%.
                </p>

                <!-- Pilihan Cepat Preset -->
                <div class="mb-3 p-2 bg-light rounded-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="small fw-semibold text-muted me-1">Preset:</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2" onclick="presetModalWidths('equal')">
                        <i class="bi bi-distribute-horizontal me-1"></i>Bagi Rata
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2" onclick="presetModalWidths('rincian')" id="btnPresetRincianModal">
                        <i class="bi bi-layout-three-columns me-1"></i>Rincian (25%/5%/70%)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2" onclick="presetModalWidths('agenda')" id="btnPresetAgendaModal">
                        <i class="bi bi-grid-3x3 me-1"></i>Agenda (8%/22%/45%/25%)
                    </button>
                </div>

                <div id="columnWidthInputsContainer" class="row g-3">
                    <!-- Dinamis di-generate oleh JS sesuai jumlah kolom tabel aktif -->
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="applyColumnWidthsFromModal()">
                    <i class="bi bi-check-lg me-1"></i>Terapkan Lebar Kolom
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Tabel -->
<div class="modal fade" id="deleteTableConfirmModal" tabindex="-1" aria-labelledby="deleteTableConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-center position-relative">
                <div class="p-3 bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-trash3-fill fs-3"></i>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <h5 class="modal-title fw-bold text-dark mb-2" id="deleteTableConfirmModalLabel">Hapus Seluruh Tabel?</h5>
                <p class="text-muted small mb-0">
                    Apakah Anda yakin ingin menghapus seluruh tabel ini dari isi surat? Seluruh baris, kolom, dan data di dalam tabel ini akan dibersihkan.
                </p>
                <p class="text-danger small mt-2 mb-0 fw-semibold">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-semibold" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-danger rounded-pill flex-fill py-2 fw-semibold" onclick="confirmDeleteTable()">
                    <i class="bi bi-trash me-1"></i> Ya, Hapus Tabel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/quill/quill.js') }}"></script>
<script>
    let quillInstance = null;

    function initQuill() {
        if (typeof Quill === 'undefined') {
            setTimeout(initQuill, 100);
            return;
        }

        const editorElem = document.getElementById('quill-editor');
        if (!editorElem || editorElem.__quillInitialized) {
            return;
        }
        if (editorElem.innerHTML.includes('<th') || editorElem.innerHTML.includes('<TH')) {
            editorElem.innerHTML = editorElem.innerHTML
                .replace(/<th\b/gi, '<td')
                .replace(/<\/th>/gi, '</td>')
                .replace(/<\/?thead\b[^>]*>/gi, '');
        }
        editorElem.__quillInitialized = true;

        quillInstance = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Tuliskan isi surat lengkap di sini...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        const syncToInput = function() {
            ensureEditorTableClasses();
            const text = quillInstance.getText().trim();
            const html = quillInstance.root.innerHTML;
            const input = document.getElementById('isi_surat_input');
            const err = document.getElementById('isi_surat_error');

            if (!text && (!html || html === '<p><br></p>')) {
                input.value = '';
                if (err) err.classList.remove('d-none');
            } else {
                input.value = html;
                if (err) err.classList.add('d-none');
            }
        };

        quillInstance.on('text-change', syncToInput);
        quillInstance.root.addEventListener('click', updateTableActionUi);
        quillInstance.root.addEventListener('keyup', updateTableActionUi);
        quillInstance.on('selection-change', updateTableActionUi);

        // Pastikan kelas tabel awal terpasang
        ensureEditorTableClasses();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuill);
    } else {
        initQuill();
    }

    function ensureEditorTableClasses() {
        if (!quillInstance) return;
        const tables = quillInstance.root.querySelectorAll('table');
        tables.forEach(table => {
            const isBordered = table.classList.contains('table-bordered') || 
                               table.getAttribute('border') === '1' || 
                               (table.style.border && table.style.border !== 'none');
            if (isBordered) {
                table.classList.add('table-bordered');
                table.classList.remove('table-borderless');
                table.setAttribute('border', '1');
            } else {
                table.classList.add('table-borderless');
                table.classList.remove('table-bordered');
                table.setAttribute('border', '0');
            }
        });
    }

    function insertHtmlAtCursor(html) {
        if (!quillInstance) return;
        quillInstance.focus();
        const selection = quillInstance.getSelection();
        const index = selection ? selection.index : quillInstance.getLength();

        // Quill lacks native <th>/<thead> blots which causes header cells to collapse into a single cell.
        // Normalize any <th> to <td> and strip <thead> to preserve columns distinctly.
        const safeHtml = html
            .replace(/<th\b/gi, '<td')
            .replace(/<\/th>/gi, '</td>')
            .replace(/<\/?thead\b[^>]*>/gi, '');

        quillInstance.clipboard.dangerouslyPasteHTML(index, safeHtml, 'user');
        ensureEditorTableClasses();
        syncEditorContent();
        updateTableActionUi();
    }

    function insertPredefinedTable(type) {
        if (!quillInstance) return;

        let html = '';
        if (type === 'mandat') {
            html = '<table class="table-bordered" border="1" style="width: 100%; border-collapse: collapse; border: 1px solid #333333; margin: 12px 0;">' +
                '<tbody>' +
                '<tr style="background-color: #f1f5f9; font-weight: bold;">' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 8%; text-align: center; font-weight: bold;"><strong>No</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 35%; text-align: left; font-weight: bold;"><strong>Nama</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 25%; text-align: left; font-weight: bold;"><strong>NIK</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 32%; text-align: left; font-weight: bold;"><strong>Jabatan</strong></td>' +
                '</tr>' +
                '<tr><td style="border: 1px solid #333333; padding: 6px 10px; text-align: center;">1</td><td style="border: 1px solid #333333; padding: 6px 10px;">Ahmad Fauzi, M.Pd.</td><td style="border: 1px solid #333333; padding: 6px 10px;">3578012345670001</td><td style="border: 1px solid #333333; padding: 6px 10px;">Wakil Sekretaris PC ISNU</td></tr>' +
                '<tr><td style="border: 1px solid #333333; padding: 6px 10px; text-align: center;">2</td><td style="border: 1px solid #333333; padding: 6px 10px;">Dr. Siti Aminah, S.Ag., M.Ag.</td><td style="border: 1px solid #333333; padding: 6px 10px;">3578012345670002</td><td style="border: 1px solid #333333; padding: 6px 10px;">Ketua Departemen Pendidikan</td></tr>' +
                '</tbody></table><p><br></p>';
        } else if (type === 'rekomendasi') {
            html = '<table class="table-borderless" border="0" style="width: 100%; border-collapse: collapse; border: none; margin: 12px 0;">' +
                '<tbody>' +
                '<tr><td style="width: 220px; padding: 4px 8px; border: none; vertical-align: top;">Nama Lengkap</td><td style="width: 20px; padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top; font-weight: bold;">Dr. H. Muhammad Ilyas, M.Si.</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">NIRA / NIA ISNU</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">ISNU-3578-2024-0012</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Tempat, Tanggal Lahir</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Surabaya, 12 Agustus 1985</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Jabatan / Instansi</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Dosen UIN Sunan Ampel Surabaya</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Alamat Domisili</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Jl. Jemursari No. 45, Wonocolo, Surabaya</td></tr>' +
                '</tbody></table><p><br></p>';
        } else if (type === 'borderless') {
            html = '<table class="table-borderless" border="0" style="width: 100%; border-collapse: collapse; border: none; margin: 12px 0;">' +
                '<tbody>' +
                '<tr><td style="width: 130px; padding: 4px 8px; border: none; vertical-align: top;">Hari / Tanggal</td><td style="width: 20px; padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Sabtu, 14 November 2026</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Waktu</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">08.30 WIB - Selesai</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Tempat</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Aula PCNU Kota Surabaya, Jl. Bubutan VI/2</td></tr>' +
                '<tr><td style="padding: 4px 8px; border: none; vertical-align: top;">Acara</td><td style="padding: 4px 8px; border: none; vertical-align: top;">:</td><td style="padding: 4px 8px; border: none; vertical-align: top;">Rapat Koordinasi &amp; Penguatan Kaderisasi</td></tr>' +
                '</tbody></table><p><br></p>';
        } else if (type === 'bordered') {
            html = '<table class="table-bordered" border="1" style="width: 100%; border-collapse: collapse; border: 1px solid #333333; margin: 12px 0;">' +
                '<tbody>' +
                '<tr style="background-color: #f1f5f9; font-weight: bold;">' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 45px; text-align: center; font-weight: bold;"><strong>No</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 140px; text-align: left; font-weight: bold;"><strong>Waktu</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; text-align: left; font-weight: bold;"><strong>Agenda / Kegiatan</strong></td>' +
                '<td style="border: 1px solid #333333; padding: 6px 10px; width: 160px; text-align: left; font-weight: bold;"><strong>Keterangan / PJ</strong></td>' +
                '</tr>' +
                '<tr><td style="border: 1px solid #333333; padding: 6px 10px; text-align: center;">1</td><td style="border: 1px solid #333333; padding: 6px 10px;">08.00 - 08.30</td><td style="border: 1px solid #333333; padding: 6px 10px;">Registrasi &amp; Pembukaan</td><td style="border: 1px solid #333333; padding: 6px 10px;">Panitia Pelaksana</td></tr>' +
                '<tr><td style="border: 1px solid #333333; padding: 6px 10px; text-align: center;">2</td><td style="border: 1px solid #333333; padding: 6px 10px;">08.30 - 10.00</td><td style="border: 1px solid #333333; padding: 6px 10px;">Sosialisasi Program Kerja</td><td style="border: 1px solid #333333; padding: 6px 10px;">Sekretaris PC ISNU</td></tr>' +
                '<tr><td style="border: 1px solid #333333; padding: 6px 10px; text-align: center;">3</td><td style="border: 1px solid #333333; padding: 6px 10px;">10.00 - 11.30</td><td style="border: 1px solid #333333; padding: 6px 10px;">Pengarahan &amp; Tindak Lanjut</td><td style="border: 1px solid #333333; padding: 6px 10px;">Ketua PC ISNU</td></tr>' +
                '</tbody></table><p><br></p>';
        }

        insertHtmlAtCursor(html);
    }

    function insertCustomTableFromModal() {
        const cols = parseInt(document.getElementById('customTableCols').value, 10) || 3;
        const rows = parseInt(document.getElementById('customTableRows').value, 10) || 3;
        const borderType = document.querySelector('input[name="customTableBorder"]:checked')?.value || 'bordered';
        const hasHeader = document.getElementById('customTableHeader').checked;

        const isBordered = (borderType === 'bordered');
        const tableClass = isBordered ? 'table-bordered' : 'table-borderless';
        const borderAttr = isBordered ? '1' : '0';
        const tableStyle = isBordered 
            ? 'width: 100%; border-collapse: collapse; border: 1px solid #333333; margin: 12px 0;'
            : 'width: 100%; border-collapse: collapse; border: none; margin: 12px 0;';
        const cellStyle = isBordered
            ? 'border: 1px solid #333333; padding: 6px 10px; vertical-align: top;'
            : 'border: none; padding: 4px 8px; vertical-align: top;';
        const headerStyle = isBordered
            ? 'border: 1px solid #333333; padding: 6px 10px; vertical-align: top; background-color: #f1f5f9; font-weight: bold;'
            : 'border: none; padding: 4px 8px; vertical-align: top; font-weight: bold;';

        let html = `<table class="${tableClass}" border="${borderAttr}" style="${tableStyle}"><tbody>`;

        if (hasHeader) {
            html += `<tr style="background-color: #f1f5f9; font-weight: bold;">`;
            for (let c = 1; c <= cols; c++) {
                const title = (c === 1 && cols > 2) ? 'No' : `Kolom ${c}`;
                html += `<td style="${headerStyle}"><strong>${title}</strong></td>`;
            }
            html += '</tr>';
        }

        const startRow = hasHeader ? 2 : 1;
        for (let r = startRow; r <= rows; r++) {
            html += '<tr>';
            for (let c = 1; c <= cols; c++) {
                const sampleText = (hasHeader && c === 1 && cols > 2) ? `${r - 1}` : `Teks ${r}.${c}`;
                html += `<td style="${cellStyle}">${sampleText}</td>`;
            }
            html += '</tr>';
        }
        const modalEl = document.getElementById('customTableModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }

        insertHtmlAtCursor(html);
    }

    let targetTableForWidth = null;

    function getActiveCell() {
        if (!quillInstance) return null;
        const sel = window.getSelection();
        if (!sel || !sel.anchorNode) return null;
        let node = sel.anchorNode;
        while (node && node !== quillInstance.root) {
            if (node.nodeName === 'TD' || node.nodeName === 'TH') return node;
            node = node.parentNode;
        }
        return null;
    }

    function getActiveColumnIndex() {
        const cell = getActiveCell();
        if (!cell) return -1;
        const tr = cell.closest('tr');
        if (!tr) return -1;
        const cells = Array.from(tr.querySelectorAll('th, td'));
        return cells.indexOf(cell);
    }

    function setTableColumnWidth(table, colIndex, widthStr) {
        if (!table || colIndex < 0) return;
        const rows = table.querySelectorAll('tr');
        rows.forEach(tr => {
            const cells = tr.querySelectorAll('th, td');
            if (cells[colIndex]) {
                cells[colIndex].style.width = widthStr;
            }
        });
        table.style.width = '100%';
        syncEditorContent();
    }

    function stepActiveColumnWidth(deltaPercent) {
        const table = getActiveTable();
        const colIndex = getActiveColumnIndex();
        if (!table || colIndex < 0) {
            alert('Silakan klik di dalam sel kolom yang ingin Anda ubah lebarnya terlebih dahulu.');
            return;
        }

        const firstRow = table.querySelector('tr');
        if (!firstRow) return;
        const cells = Array.from(firstRow.querySelectorAll('th, td'));
        const totalCols = cells.length;
        if (totalCols === 0) return;

        const activeCell = cells[colIndex];
        let currentPercent = 0;
        if (activeCell.style.width && activeCell.style.width.endsWith('%')) {
            currentPercent = parseFloat(activeCell.style.width);
        } else {
            const tableWidth = table.offsetWidth || 600;
            const cellWidth = activeCell.offsetWidth || (tableWidth / totalCols);
            currentPercent = Math.round((cellWidth / tableWidth) * 100);
        }

        let newPercent = Math.max(4, Math.min(90, Math.round(currentPercent + deltaPercent)));
        setTableColumnWidth(table, colIndex, newPercent + '%');
        updateTableActionUi();
    }

    function distributeColumnsEvenly() {
        const table = getActiveTable();
        if (!table) return;
        const firstRow = table.querySelector('tr');
        if (!firstRow) return;
        const cells = Array.from(firstRow.querySelectorAll('th, td'));
        const totalCols = cells.length;
        if (totalCols === 0) return;

        const percentPerCol = (100 / totalCols).toFixed(1) + '%';
        for (let c = 0; c < totalCols; c++) {
            setTableColumnWidth(table, c, percentPerCol);
        }
        updateTableActionUi();
    }

    function applyPresetRincianWidth() {
        const table = getActiveTable();
        if (!table) return;
        const firstRow = table.querySelector('tr');
        if (!firstRow) return;
        const cells = Array.from(firstRow.querySelectorAll('th, td'));
        if (cells.length >= 3) {
            setTableColumnWidth(table, 0, '25%');
            setTableColumnWidth(table, 1, '5%');
            setTableColumnWidth(table, 2, '70%');
            for (let c = 3; c < cells.length; c++) {
                setTableColumnWidth(table, c, 'auto');
            }
            updateTableActionUi();
        } else {
            distributeColumnsEvenly();
        }
    }

    function openColumnWidthModal() {
        targetTableForWidth = getActiveTable();
        if (!targetTableForWidth) {
            const allTables = quillInstance ? quillInstance.root.querySelectorAll('table') : [];
            if (allTables.length > 0) {
                targetTableForWidth = allTables[0];
            } else {
                alert('Tidak ditemukan tabel di dalam editor. Silakan sisipkan tabel terlebih dahulu.');
                return;
            }
        }

        const firstRow = targetTableForWidth.querySelector('tr');
        if (!firstRow) return;
        const cells = Array.from(firstRow.querySelectorAll('th, td'));
        const totalCols = cells.length;
        const container = document.getElementById('columnWidthInputsContainer');
        container.innerHTML = '';

        const btnRincian = document.getElementById('btnPresetRincianModal');
        const btnAgenda = document.getElementById('btnPresetAgendaModal');
        if (btnRincian) btnRincian.style.display = (totalCols === 3) ? 'inline-block' : 'none';
        if (btnAgenda) btnAgenda.style.display = (totalCols === 4) ? 'inline-block' : 'none';

        cells.forEach((cell, idx) => {
            let currentWidth = cell.style.width || '';
            if (!currentWidth) {
                currentWidth = Math.round(100 / totalCols) + '%';
            }
            const textSample = cell.innerText.trim().slice(0, 14);
            const colTitle = textSample ? `Kolom ${idx + 1} (${textSample})` : `Kolom ${idx + 1}`;

            const colDiv = document.createElement('div');
            colDiv.className = (totalCols <= 2) ? 'col-6' : (totalCols <= 4 ? 'col-6' : 'col-4');
            colDiv.innerHTML = `
                <label class="form-label small fw-semibold text-truncate d-block mb-1">${colTitle}</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control modal-col-width-input" data-col="${idx}" value="${currentWidth}" placeholder="25% atau 140px">
                </div>
            `;
            container.appendChild(colDiv);
        });

        const modalEl = document.getElementById('columnWidthModal');
        const modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }

    function presetModalWidths(type) {
        const inputs = document.querySelectorAll('.modal-col-width-input');
        const totalCols = inputs.length;
        if (totalCols === 0) return;

        if (type === 'equal') {
            const val = (100 / totalCols).toFixed(1) + '%';
            inputs.forEach(input => input.value = val);
        } else if (type === 'rincian' && totalCols === 3) {
            if (inputs[0]) inputs[0].value = '25%';
            if (inputs[1]) inputs[1].value = '5%';
            if (inputs[2]) inputs[2].value = '70%';
        } else if (type === 'agenda' && totalCols === 4) {
            if (inputs[0]) inputs[0].value = '8%';
            if (inputs[1]) inputs[1].value = '22%';
            if (inputs[2]) inputs[2].value = '45%';
            if (inputs[3]) inputs[3].value = '25%';
        }
    }

    function applyColumnWidthsFromModal() {
        if (!targetTableForWidth) return;
        const inputs = document.querySelectorAll('.modal-col-width-input');
        inputs.forEach(input => {
            const colIdx = parseInt(input.getAttribute('data-col'), 10);
            let val = input.value.trim();
            if (val) {
                if (!val.endsWith('%') && !val.endsWith('px') && !isNaN(val)) {
                    val = val + '%';
                }
                setTableColumnWidth(targetTableForWidth, colIdx, val);
            }
        });

        const modalEl = document.getElementById('columnWidthModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }
        updateTableActionUi();
    }

    function getActiveTable() {
        if (!quillInstance) return null;
        const sel = window.getSelection();
        if (!sel || !sel.anchorNode) return null;
        let node = sel.anchorNode;
        while (node && node !== quillInstance.root) {
            if (node.nodeName === 'TABLE') return node;
            node = node.parentNode;
        }
        return null;
    }

    function updateTableActionUi() {
        const table = getActiveTable();
        const group = document.getElementById('tableActionsGroup');
        const btnBordered = document.getElementById('btnTableBordered');
        const btnBorderless = document.getElementById('btnTableBorderless');
        if (!group) return;
        
        if (table) {
            group.classList.remove('d-none');
            const isBordered = table.classList.contains('table-bordered') || table.getAttribute('border') === '1';
            if (btnBordered && btnBorderless) {
                if (isBordered) {
                    btnBordered.classList.add('btn-dark', 'text-white');
                    btnBordered.classList.remove('btn-outline-secondary');
                    btnBorderless.classList.remove('btn-dark', 'text-white');
                    btnBorderless.classList.add('btn-outline-secondary');
                } else {
                    btnBorderless.classList.add('btn-dark', 'text-white');
                    btnBorderless.classList.remove('btn-dark', 'text-white');
                    btnBordered.classList.remove('btn-dark', 'text-white');
                    btnBordered.classList.add('btn-outline-secondary');
                }
            }
        } else {
            group.classList.add('d-none');
        }
    }

    function toggleActiveTableBorder(type) {
        const table = getActiveTable();
        if (!table) return;

        if (type === 'bordered') {
            table.classList.remove('table-borderless');
            table.classList.add('table-bordered');
            table.setAttribute('border', '1');
            table.style.border = '1px solid #333333';
            table.querySelectorAll('th, td').forEach(cell => {
                cell.style.border = '1px solid #333333';
            });
        } else {
            table.classList.remove('table-bordered');
            table.classList.add('table-borderless');
            table.setAttribute('border', '0');
            table.style.border = 'none';
            table.querySelectorAll('th, td').forEach(cell => {
                cell.style.border = 'none';
            });
        }

        syncEditorContent();
        updateTableActionUi();
    }

    function tableAddRow() {
        const table = getActiveTable();
        if (!table) return;
        
        const tbody = table.querySelector('tbody') || table;
        const rows = tbody.querySelectorAll('tr');
        if (rows.length === 0) return;
        
        const lastRow = rows[rows.length - 1];
        const colCount = lastRow.querySelectorAll('th, td').length;
        const isBordered = table.classList.contains('table-bordered');
        const cellStyle = isBordered ? 'border: 1px solid #333333; padding: 6px 10px;' : 'border: none; padding: 4px 8px;';
        
        const newTr = document.createElement('tr');
        for (let i = 0; i < colCount; i++) {
            const td = document.createElement('td');
            td.setAttribute('style', cellStyle);
            td.innerHTML = '<br>';
            newTr.appendChild(td);
        }
        tbody.appendChild(newTr);
        syncEditorContent();
    }

    function tableDeleteRow() {
        const table = getActiveTable();
        if (!table) return;
        
        const sel = window.getSelection();
        let cell = sel ? sel.anchorNode : null;
        while (cell && cell.nodeName !== 'TD' && cell.nodeName !== 'TH' && cell !== table) {
            cell = cell.parentNode;
        }
        const tr = cell && (cell.nodeName === 'TD' || cell.nodeName === 'TH') ? cell.closest('tr') : null;
        if (tr) {
            tr.remove();
            syncEditorContent();
        }
    }

    let pendingTableToDelete = null;

    function tableDelete() {
        const table = getActiveTable();
        if (!table) return;
        pendingTableToDelete = table;
        const modalEl = document.getElementById('deleteTableConfirmModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function confirmDeleteTable() {
        if (pendingTableToDelete) {
            pendingTableToDelete.remove();
            pendingTableToDelete = null;
            syncEditorContent();
            updateTableActionUi();
        }
        const modalEl = document.getElementById('deleteTableConfirmModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }

    function syncEditorContent() {
        if (!quillInstance) {
            const input = document.getElementById('isi_surat_input');
            return input && input.value.trim() !== '';
        }
        ensureEditorTableClasses();
        const text = quillInstance.getText().trim();
        const html = quillInstance.root.innerHTML;
        const input = document.getElementById('isi_surat_input');
        const err = document.getElementById('isi_surat_error');

        if (!text && (!html || html === '<p><br></p>')) {
            input.value = '';
            if (err) err.classList.remove('d-none');
            quillInstance.focus();
            return false;
        } else {
            input.value = html;
            if (err) err.classList.add('d-none');
            return true;
        }
    }

    function submitWithAction(actionType) {
        const form = document.getElementById('letterForm');
        if (!form.reportValidity()) {
            return false;
        }
        if (!syncEditorContent()) {
            return false;
        }
        document.getElementById('formAction').value = actionType;
        form.submit();
        return true;
    }
</script>
@endsection
