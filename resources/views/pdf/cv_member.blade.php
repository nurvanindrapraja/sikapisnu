<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Curriculum Vitae - {{ $member->full_name }}</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 1.6cm 1.2cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #222222;
            font-size: 9.5pt;
            line-height: 1.3;
        }

        /* Fixed Footer Watermark at top of body for DomPDF */
        footer {
            position: fixed;
            bottom: -1.2cm;
            left: 0px;
            right: 0px;
            height: 20px;
            border-top: 1px solid #cccccc;
            padding-top: 4px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            padding: 0;
            font-size: 7.5pt;
            color: #666666;
            vertical-align: middle;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-bottom: 2px solid #006837;
            padding-bottom: 8px;
        }

        .header-logo {
            width: 55px;
            vertical-align: middle;
        }

        .header-logo img {
            width: 50px;
            height: auto;
        }

        .header-text {
            vertical-align: middle;
            padding-left: 8px;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
            color: #006837;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .header-subtitle {
            font-size: 9.5pt;
            font-weight: bold;
            color: #333333;
            margin-top: 1px;
            margin-bottom: 0;
        }

        .header-org {
            font-size: 8pt;
            color: #666666;
            margin-top: 1px;
        }

        /* Section Title */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #006837;
            text-transform: uppercase;
            background-color: #f0f7f3;
            border-left: 4px solid #006837;
            padding: 4px 8px;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* Flat Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 9pt;
        }

        .info-label {
            width: 24%;
            color: #555555;
            font-weight: bold;
        }

        .info-colon {
            width: 2%;
            text-align: center;
            color: #555555;
        }

        .info-value {
            width: 54%;
            color: #111111;
        }

        .photo-td {
            width: 20%;
            text-align: right;
            vertical-align: top;
            padding-left: 8px;
        }

        .photo-img {
            width: 85px;
            height: 110px;
            border: 1px solid #006837;
            padding: 2px;
            background: #ffffff;
        }

        .photo-placeholder {
            width: 85px;
            height: 110px;
            border: 1px dashed #aaaaaa;
            background: #f9f9f9;
            text-align: center;
            line-height: 110px;
            font-size: 8pt;
            color: #888888;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background-color: #eaf3ed;
            color: #006837;
            border: 1px solid #c2decb;
            padding: 5px 6px;
            font-size: 8.5pt;
            font-weight: bold;
            text-align: left;
        }

        .data-table td {
            border: 1px solid #dddddd;
            padding: 4px 6px;
            font-size: 8.5pt;
        }

        .data-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .text-center {
            text-align: center;
        }

        .empty-row {
            color: #888888;
            font-style: italic;
            font-size: 8pt;
        }

        .badge-status {
            background-color: #e6f4ea;
            color: #006837;
            padding: 1px 5px;
            border-radius: 2px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Fixed Footer Watermark (MUST be at top of body in DomPDF) -->
    <footer>
        <table class="footer-table">
            <tr>
                <td style="text-align: left; width: 50%;">
                    Waktu Unduh: <strong>{{ $downloadTimestamp }}</strong>
                </td>
                <td style="text-align: right; width: 50%;">
                    Diunduh Oleh: <strong>{{ $downloadedBy }}</strong>
                </td>
            </tr>
        </table>
    </footer>

    @php
        $logoPath = public_path('images/logo_isnu.png');
        $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $photoSrc = null;
        if ($member->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($member->photo)) {
            $photoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($member->photo);
            if (file_exists($photoPath)) {
                $mime = mime_content_type($photoPath);
                $photoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($photoPath));
            }
        }
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo ISNU">
                @endif
            </td>
            <td class="header-text">
                <div class="header-title">CURRICULUM VITAE KADER ISNU</div>
                <div class="header-subtitle">PENGURUS CABANG IKATAN SARJANA NU KOTA SURABAYA</div>
                <div class="header-org">Sistem Informasi Keanggotaan & Potensi Kader (SIKAP ISNU)</div>
            </td>
        </tr>
    </table>

    <!-- Section 1: Biodata Utama -->
    <div class="section-title">I. BIODATA PRIBADI & KEANGGOTAAN</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Nama Lengkap & Gelar</td>
            <td class="info-colon">:</td>
            <td class="info-value"><strong>{{ $member->full_name }}</strong></td>
            <td class="photo-td" rowspan="6">
                @if($photoSrc)
                    <img src="{{ $photoSrc }}" class="photo-img" alt="Foto Profil">
                @else
                    <div class="photo-placeholder">PAS FOTO</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="info-label">NIK (No. KTP)</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $member->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Nomor Anggota ISNU</td>
            <td class="info-colon">:</td>
            <td class="info-value"><strong>{{ $member->member_number ?? 'Belum Diterbitkan' }}</strong></td>
        </tr>
        <tr>
            <td class="info-label">Tempat, Tgl Lahir</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $member->birth_place }}, {{ $member->birth_date ? $member->birth_date->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Jenis Kelamin</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $member->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
        </tr>
        <tr>
            <td class="info-label">Pekerjaan / Profesi</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $member->occupation ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Status Organisasi</td>
            <td class="info-colon">:</td>
            <td class="info-value" colspan="2">
                <span class="badge-status">{{ strtoupper($member->membership_status) }}</span>
                @if($member->activePosition)
                    &nbsp;({{ $member->activePosition->position_title }} - Periode {{ $member->activePosition->period }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="info-label">No. Telepon / HP</td>
            <td class="info-colon">:</td>
            <td class="info-value" colspan="2">{{ $member->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Email</td>
            <td class="info-colon">:</td>
            <td class="info-value" colspan="2">{{ $member->email ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Alamat Rumah</td>
            <td class="info-colon">:</td>
            <td class="info-value" colspan="2">
                {{ $member->address }}, Kel. {{ $member->kelurahan }}, Kec. {{ $member->kecamatan }}
                {{ $member->city ? ', ' . $member->city : '' }}
                {{ ($member->province && $member->province !== 'JAWA TIMUR') ? ', ' . $member->province : '' }}
            </td>
        </tr>
    </table>

    <!-- Section 2: Riwayat Pendidikan -->
    <div class="section-title">II. RIWAYAT PENDIDIKAN</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 15%;">Jenjang</th>
                <th style="width: 40%;">Institusi / Kampus / Sekolah</th>
                <th style="width: 25%;">Prodi / Jurusan (Gelar)</th>
                <th style="width: 15%;" class="text-center">Tahun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->educations as $index => $edu)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $edu->level }}</strong></td>
                    <td>{{ $edu->institution_name }}</td>
                    <td>
                        {{ $edu->major }}
                        @if($edu->degree) <strong>({{ $edu->degree }})</strong> @endif
                    </td>
                    <td class="text-center">
                        {{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center empty-row">Belum ada riwayat pendidikan yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Section 3: Riwayat Organisasi -->
    <div class="section-title">III. RIWAYAT ORGANISASI</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 45%;">Nama Organisasi</th>
                <th style="width: 35%;">Peran / Jabatan</th>
                <th style="width: 15%;" class="text-center">Periode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->organizations as $index => $org)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $org->organization_name }}</strong></td>
                    <td>{{ $org->position ?? '-' }}</td>
                    <td class="text-center">{{ $org->period ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center empty-row">Belum ada riwayat organisasi yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Section 4: Riwayat Pekerjaan / Profesi -->
    <div class="section-title">IV. RIWAYAT PEKERJAAN & PROFESI</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 45%;">Nama Pekerjaan / Instansi / Perusahaan</th>
                <th style="width: 35%;">Peran / Jabatan</th>
                <th style="width: 15%;" class="text-center">Tahun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->employments as $index => $emp)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $emp->company_name }}</strong></td>
                    <td>{{ $emp->position }}</td>
                    <td class="text-center">
                        {{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center empty-row">Belum ada riwayat pekerjaan yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Section 5: Kaderisasi NU -->
    <div class="section-title">V. RIWAYAT KADERISASI NU</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Nama Kaderisasi</th>
                <th style="width: 35%;">Penyelenggara</th>
                <th style="width: 25%;">No. Sertifikat</th>
                <th style="width: 10%;" class="text-center">Tahun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->nuTrainings as $index => $nu)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $nu->training_type }}</strong></td>
                    <td>{{ $nu->organizer ?? '-' }}</td>
                    <td>{{ $nu->certificate_number ?? '-' }}</td>
                    <td class="text-center">{{ $nu->year ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center empty-row">Belum ada kaderisasi NU yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Section 6: Sertifikasi Keahlian -->
    <div class="section-title">VI. SERTIFIKASI KEAHLIAN</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 35%;">Nama Sertifikasi</th>
                <th style="width: 25%;">Bidang Keahlian</th>
                <th style="width: 25%;">No. Sertifikat</th>
                <th style="width: 10%;" class="text-center">Tahun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->certifications as $index => $cert)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $cert->certification_name }}</strong></td>
                    <td>{{ $cert->field ?? '-' }}</td>
                    <td>{{ $cert->certificate_number ?? '-' }}</td>
                    <td class="text-center">{{ $cert->issue_year ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center empty-row">Belum ada sertifikasi keahlian yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
