<!-- Kop Surat Resmi ISNU -->
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
    <!-- Layout Khusus SURAT MANDAT -->
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
    <!-- Layout Khusus SURAT REKOMENDASI -->
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
