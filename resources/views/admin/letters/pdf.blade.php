<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Resmi - {{ $letter->formatted_letter_number }}</title>
    <style>
        @page {
            margin-top: 135px;
            margin-bottom: 45px;
            margin-left: 2.2cm;
            margin-right: 2.2cm;
            size: a4 portrait;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .header-bar {
            position: fixed;
            top: -135px;
            left: -2.2cm;
            right: -2.2cm;
            height: 125px;
            width: 210mm;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
        }
        .footer-bar {
            position: fixed;
            bottom: -45px;
            left: -2.2cm;
            right: -2.2cm;
            height: 35px;
            width: 210mm;
            margin: 0;
            padding: 0;
            border-collapse: collapse;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 18px;
        }
        .meta-table td {
            vertical-align: top;
            font-size: 11pt;
        }
        .recipient-block {
            margin-bottom: 18px;
            line-height: 1.4;
        }
        .salutation {
            font-style: italic;
            font-weight: bold;
            margin-bottom: 12px;
        }
        .body-content {
            text-align: justify;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .body-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .body-content table.table-bordered,
        .body-content table[border="1"] {
            border: 1px solid #222222;
        }
        .body-content table.table-bordered th,
        .body-content table.table-bordered td,
        .body-content table[border="1"] th,
        .body-content table[border="1"] td {
            border: 1px solid #222222;
            padding: 5px 8px;
            vertical-align: top;
        }
        .body-content table.table-bordered th,
        .body-content table[border="1"] th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .body-content table.table-borderless,
        .body-content table[border="0"],
        .body-content table:not(.table-bordered):not([border="1"]) {
            border: none;
        }
        .body-content table.table-borderless th,
        .body-content table.table-borderless td,
        .body-content table[border="0"] th,
        .body-content table[border="0"] td,
        .body-content table:not(.table-bordered):not([border="1"]) th,
        .body-content table:not(.table-bordered):not([border="1"]) td {
            border: none;
            padding: 3px 6px;
            vertical-align: top;
        }
        .title-block {
            text-align: center;
            margin-bottom: 18px;
        }
        .title-block h2 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0 0 4px 0;
            letter-spacing: 1px;
        }
        .title-block .doc-number {
            font-size: 11pt;
            font-family: monospace;
            font-weight: bold;
        }
        .closing {
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .signature-table {
            width: 100%;
            margin-top: 15px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signer-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .qr-box {
            height: 95px;
            margin: 5px auto;
        }
        .qr-box img {
            width: 85px;
            height: 85px;
        }
        .signer-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 6px;
        }
        .signer-position {
            font-size: 10pt;
            font-weight: normal;
            color: #111;
            margin-top: 2px;
        }
        .waiting-sign {
            color: #888;
            font-style: italic;
            height: 85px;
            line-height: 85px;
        }
        .security-footer {
            margin-top: 25px;
            padding-top: 6px;
            border-top: 1px dashed #aaa;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI (MARGIN TOP, LEFT, RIGHT = 0) -->
    <header class="header-bar">
        <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
            <tr>
                <td style="width: 125px; vertical-align: middle; text-align: center; padding: 8px 10px 8px 16px; background-color: #ffffff;">
                    @if($kopLogoBase64)
                        <img src="data:image/jpeg;base64,{{ $kopLogoBase64 }}" style="width: 82px; height: auto;" alt="Logo ISNU">
                    @else
                        <img src="{{ public_path('images/kop_logo_isnu.jpeg') }}" style="width: 82px; height: auto;" alt="Logo ISNU">
                    @endif
                </td>
                <td style="background-color: #94b3d6; border-bottom-left-radius: 20px; padding: 8px 16px 8px 18px; vertical-align: middle; font-family: 'Trebuchet MS', Arial, sans-serif;">
                    @if($letter->isLevelPac())
                        <div style="font-size: 10pt; color: #000000; letter-spacing: 0.5px; line-height: 1.1; margin-bottom: 2px;">
                            PIMPINAN ANAK CABANG
                        </div>
                        <div style="font-size: 13pt; font-weight: bold; color: #000000; line-height: 1.1; letter-spacing: -0.2px;">
                            IKATAN SARJANA NAHDLATUL ULAMA
                        </div>
                        <div style="font-size: 13pt; font-weight: bold; color: #000000; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 4px;">
                            KECAMATAN {{ strtoupper($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? 'SURABAYA')) }}
                        </div>
                    @else
                        <div style="font-size: 11pt; color: #000000; letter-spacing: 0.5px; line-height: 1.1; margin-bottom: 2px;">
                            PIMPINAN CABANG
                        </div>
                        <div style="font-size: 15pt; font-weight: bold; color: #000000; line-height: 1.1; letter-spacing: -0.2px;">
                            IKATAN SARJANA NAHDLATUL
                        </div>
                        <div style="font-size: 15pt; font-weight: bold; color: #000000; line-height: 1.1; letter-spacing: -0.2px; margin-bottom: 4px;">
                            ULAMA KOTA SURABAYA
                        </div>
                    @endif
                    <div style="font-size: 8.5pt; color: #111111; line-height: 1.25;">
                        Jalan Bubutan VI/2 Surabaya, HP: +6281330278721, +6281334300341
                    </div>
                    <div style="font-size: 8.5pt; color: #111111; line-height: 1.25;">
                        Email: <span style="color: #0000ee; text-decoration: underline;">isnukotasurabaya@gmail.com</span>, Website: <span style="color: #0000ee; text-decoration: underline;">https://www.isnusurabaya.or.id</span>
                    </div>
                </td>
                <td style="width: 16px; background-color: #006837; vertical-align: top;">
                    &nbsp;
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER RESMI (MARGIN BOTTOM, LEFT, RIGHT = 0) -->
    <footer class="footer-bar">
        <table style="width: 100%; height: 35px; border-collapse: collapse; margin: 0; padding: 0;">
            <tr>
                <td style="width: 14.8%; background-color: #00af50; height: 35px;">
                    &nbsp;
                </td>
                <td style="width: 85.2%; background-color: #4f81bc; color: #ffffff; height: 35px; padding-left: 16px; font-family: Arial, sans-serif; font-size: 9pt; font-weight: normal; vertical-align: middle; white-space: nowrap;">
                    Dari Kontemplasi Menjadi Aksi [] Menyapa Semua [] Merangkul Umat [] Menanam untuk Bangsa
                </td>
            </tr>
        </table>
    </footer>

    @if($letter->isMandat())
        <!-- JUDUL & NOMOR SURAT MANDAT -->
        <div class="title-block">
            <h2>SURAT MANDAT</h2>
            <div class="doc-number">Nomor : {{ $letter->formatted_letter_number }}</div>
        </div>

        <!-- TEKS PEMBUKA MANDAT -->
        <div style="text-align: justify; line-height: 1.5; margin-bottom: 12px;">
            Yang bertandatangan dibawah ini adalah Pengurus {{ $letter->isLevelPac() ? 'Pimpinan Anak Cabang Ikatan Sarjana Nahdlatul Ulama (PAC ISNU) Kecamatan ' . ($letter->pac?->name ?? '') : 'Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya' }} merekomendasikan kepada:
        </div>

        <!-- ISI / DAFTAR NAMA MANDAT -->
        <div class="body-content">
            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                {!! $letter->isi_surat !!}
            @else
                {!! nl2br(e($letter->isi_surat)) !!}
            @endif
        </div>

        <!-- PENUGASAN KEGIATAN & PENUTUP MANDAT -->
        <div style="text-align: justify; line-height: 1.5; margin-bottom: 12px;">
            Untuk mendaftar dan mengikuti kegiatan <strong>{{ $letter->kegiatan }}</strong> pada tanggal <strong>{{ $letter->tanggal_pelaksanaan }}</strong> di <strong>{{ $letter->lokasi_pelaksanaan }}</strong>.
        </div>

        <div style="text-align: justify; line-height: 1.5; margin-bottom: 15px;">
            Demikian surat mandat ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
        </div>

        <div style="text-align: right; margin-bottom: 15px; font-size: 11pt;">
            {{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}
        </div>
    @elseif($letter->isRekomendasi())
        <!-- JUDUL & NOMOR SURAT REKOMENDASI -->
        <div class="title-block">
            <h2>SURAT REKOMENDASI</h2>
            <div class="doc-number">Nomor : {{ $letter->formatted_letter_number }}</div>
        </div>

        <!-- TEKS PEMBUKA REKOMENDASI -->
        <div style="text-align: justify; line-height: 1.5; margin-bottom: 12px;">
            Yang bertandatangan dibawah ini adalah Pengurus Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya merekomendasikan kepada:
        </div>

        <!-- ISI / BIODATA REKOMENDASI -->
        <div class="body-content">
            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                {!! $letter->isi_surat !!}
            @else
                {!! nl2br(e($letter->isi_surat)) !!}
            @endif
        </div>

        <!-- PENUTUP REKOMENDASI -->
        <div style="text-align: justify; line-height: 1.5; margin-bottom: 15px;">
            Demikian surat rekomendasi ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
        </div>

        <div style="text-align: right; margin-bottom: 15px; font-size: 11pt;">
            {{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}
        </div>
    @else
        <!-- META DATA SURAT DINAS -->
        <table class="meta-table">
            <tr>
                <td style="width: 15%;">Nomor</td>
                <td style="width: 2%;">:</td>
                <td style="width: 45%;"><strong>{{ $letter->formatted_letter_number }}</strong></td>
                <td style="width: 38%; text-align: right;">{{ $letter->kota_asal ?? 'Surabaya' }}, {{ $letter->formatted_date_letter }}</td>
            </tr>
            <tr>
                <td>Lampiran</td>
                <td>:</td>
                <td>{{ $letter->lampiran ?: '-' }}</td>
                <td></td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td><strong>{{ $letter->perihal }}</strong></td>
                <td></td>
            </tr>
        </table>

        <!-- TUJUAN SURAT -->
        <div class="recipient-block">
            Kepada Yang Terhormat,<br>
            <strong>{{ $letter->tujuan_surat }}</strong><br>
            di -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<u>{{ $letter->kota_tujuan ?? 'Surabaya' }}</u>
        </div>

        <!-- SALAM PEMBUKA -->
        <div class="salutation">
            Assalamu'alaikum Warahmatullahi Wabarakatuh
        </div>

        <!-- ISI SURAT -->
        <div class="body-content">
            @if(str_contains($letter->isi_surat, '<') && str_contains($letter->isi_surat, '>'))
                {!! $letter->isi_surat !!}
            @else
                {!! nl2br(e($letter->isi_surat)) !!}
            @endif
        </div>

        <!-- SALAM PENUTUP -->
        <div class="closing">
            <div style="font-style: italic; font-weight: bold; margin-bottom: 5px;">
                Wallahul Muwaffiq Ila Aqwamith Thariq<br>
                Wassalamu'alaikum Warahmatullahi Wabarakatuh
            </div>
        </div>
    @endif

    <!-- PENANDATANGANAN DIGITAL 2 KOLOM -->
    <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">
        @if($letter->isLevelPac())
            PIMPINAN ANAK CABANG<br>
            IKATAN SARJANA NAHDLATUL ULAMA KECAMATAN {{ strtoupper($letter->pac_kecamatan_name ?: ($letter->pac?->name ?? 'SURABAYA')) }}
        @else
            PIMPINAN CABANG<br>
            IKATAN SARJANA NAHDLATUL ULAMA KOTA SURABAYA
        @endif
    </div>

    <table class="signature-table">
        <tr>
            <!-- KOLOM KETUA (KIRI) -->
            <td>
                <div class="qr-box">
                    @if($chairQrBase64)
                        <img src="data:image/png;base64,{{ $chairQrBase64 }}" alt="QR Ketua">
                    @else
                        <div class="waiting-sign">(Menunggu TTD Digital Ketua)</div>
                    @endif
                </div>
                <div class="signer-name">{{ $letter->chairman_name ?? '....................................' }}</div>
                <div class="signer-position">{{ $letter->chairman_position ?? 'Ketua' }}</div>
            </td>

            <!-- KOLOM SEKRETARIS (KANAN) -->
            <td>
                <div class="qr-box">
                    @if($secQrBase64)
                        <img src="data:image/png;base64,{{ $secQrBase64 }}" alt="QR Sekretaris">
                    @else
                        <div class="waiting-sign">(Menunggu TTD Digital Sekretaris)</div>
                    @endif
                </div>
                <div class="signer-name">{{ $letter->secretary_name ?? '....................................' }}</div>
                <div class="signer-position">{{ $letter->secretary_position ?? 'Sekretaris' }}</div>
            </td>
        </tr>
    </table>

    <!-- FOOTER KEAMANAN & INTEGRITAS ELEKTRONIK -->
    <div class="security-footer">
        Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode di atas untuk verifikasi otentisitas dokumen. Kode Surat: {{ $letter->letter_code }}.
    </div>

</body>
</html>
