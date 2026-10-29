<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Potensi Kader & Keahlian - PC ISNU Kota Surabaya</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 1.6cm 1.2cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #222222;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        /* Fixed Footer at bottom of every page */
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

        /* Header / Kop Surat */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2.5px solid #006837;
            padding-bottom: 8px;
        }

        .kop-logo {
            width: 58px;
            vertical-align: middle;
            text-align: center;
        }

        .kop-logo img {
            width: 52px;
            height: auto;
        }

        .kop-text {
            vertical-align: middle;
            padding-left: 10px;
        }

        .kop-title {
            font-size: 13pt;
            font-weight: bold;
            color: #006837;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }

        .kop-subtitle {
            font-size: 9.5pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 2px;
            margin-bottom: 0;
        }

        .kop-info {
            font-size: 7.5pt;
            color: #555555;
            margin-top: 2px;
        }

        /* Document Title Banner */
        .doc-title-box {
            text-align: center;
            background-color: #f0f7f3;
            border: 1px solid #cce5d6;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 12px;
        }

        .doc-main-title {
            font-size: 11pt;
            font-weight: bold;
            color: #006837;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .doc-sub-title {
            font-size: 8pt;
            color: #444444;
            margin-top: 3px;
            margin-bottom: 0;
        }

        /* Section Headings */
        .section-header {
            font-size: 9.5pt;
            font-weight: bold;
            color: #006837;
            text-transform: uppercase;
            background-color: #eaf4ee;
            border-left: 4px solid #006837;
            padding: 4px 8px;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        /* Metric Grid Cards (Table format for DomPDF) */
        .metric-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .metric-cell {
            padding: 6px 8px;
            border: 1px solid #d2e4d9;
            background-color: #ffffff;
            text-align: center;
            vertical-align: middle;
            width: 16.66%;
        }

        .metric-val {
            font-size: 12pt;
            font-weight: bold;
            color: #006837;
            display: block;
            margin-bottom: 2px;
        }

        .metric-lbl {
            font-size: 7pt;
            color: #555555;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background-color: #006837;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #00522c;
            text-align: left;
            font-size: 7.5pt;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 4px 6px;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f9fbf9;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #666666; }
        .text-success { color: #006837; }

        .badge-pill {
            display: inline-block;
            padding: 1.5px 5px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 10px;
            background-color: #eaf4ee;
            color: #006837;
            border: 0.5px solid #cce5d6;
        }

        .badge-officer {
            background-color: #fff3cd;
            color: #856404;
            border: 0.5px solid #ffeeba;
        }

        .page-break {
            page-break-before: always;
        }

        /* Two columns layout using tables */
        .columns-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .columns-table td {
            vertical-align: top;
            padding: 0 4px;
        }

        /* Signature Section */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .sign-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 8.5pt;
        }

        .sign-space {
            height: 55px;
        }
    </style>
</head>
<body>
    <!-- Running Footer -->
    <footer>
        <table class="footer-table">
            <tr>
                <td style="text-align: left; width: 45%;">
                    SIKAP-ISNU Kota Surabaya &bull; Waktu Cetak: <strong>{{ $downloadTimestamp }}</strong>
                </td>
                <td style="text-align: center; width: 25%;">
                    Dokumen Resmi Rekapitulasi Potensi
                </td>
                <td style="text-align: right; width: 30%;">
                    Diunduh: <strong>{{ $downloadedBy }}</strong>
                </td>
            </tr>
        </table>
    </footer>

    <!-- Header / Kop Surat -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="Logo ISNU">
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-title">Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama</div>
                <div class="kop-subtitle">PC ISNU KOTA SURABAYA &bull; JAWA TIMUR</div>
                <div class="kop-info">
                    Sistem Informasi Keanggotaan & Potensi Sarjana NU (SIKAP-ISNU) &bull; 
                    Sekretariat: Kota Surabaya, Jawa Timur &bull; Website: sikapisnu.or.id
                </div>
            </td>
        </tr>
    </table>

    <!-- Title Box -->
    <div class="doc-title-box">
        <h1 class="doc-main-title">REKAPITULASI POTENSI KADER, KEAHLIAN, DAN DAFTAR ANGGOTA</h1>
        <p class="doc-sub-title">
            Wilayah: {{ $isPacAdmin ? 'Pimpinan Anak Cabang (PAC)' : 'Pimpinan Cabang (PC) Kota Surabaya' }} &bull; 
            Per Tanggal: <strong>{{ $downloadTimestamp }}</strong>
        </p>
    </div>

    <!-- Ringkasan Statistik Status Keanggotaan -->
    <table class="metric-table">
        <tr>
            <td class="metric-cell">
                <span class="metric-val">{{ number_format($stats['total']) }}</span>
                <span class="metric-lbl">Total Kader Terdaftar</span>
            </td>
            <td class="metric-cell">
                <span class="metric-val" style="color: #198754;">{{ number_format($stats['terverifikasi']) }}</span>
                <span class="metric-lbl">Anggota Aktif (Terverifikasi)</span>
            </td>
            <td class="metric-cell">
                <span class="metric-val" style="color: #d39e00;">{{ number_format($stats['pengurus']) }}</span>
                <span class="metric-lbl">Fungsionaris Pengurus</span>
            </td>
            <td class="metric-cell">
                <span class="metric-val" style="color: #dc3545;">{{ number_format($stats['pending']) }}</span>
                <span class="metric-lbl">Pending Verifikasi</span>
            </td>
            <td class="metric-cell">
                <span class="metric-val" style="color: #0d6efd;">{{ number_format($stats['male']) }}</span>
                <span class="metric-lbl">Laki-Laki (Aktif)</span>
            </td>
            <td class="metric-cell">
                <span class="metric-val" style="color: #d63384;">{{ number_format($stats['female']) }}</span>
                <span class="metric-lbl">Perempuan (Aktif)</span>
            </td>
        </tr>
    </table>

    <!-- BAGIAN 1: REKAPITULASI POTENSI KADER -->
    <div class="section-header">1. Rekapitulasi Potensi Kader (Sebaran Wilayah, Pendidikan, Profesi, & Kaderisasi)</div>

    <table class="columns-table">
        <tr>
            <!-- Kolom Kiri: Sebaran Wilayah / Kecamatan & Pendidikan -->
            <td style="width: 50%; padding-right: 4px;">
                <div style="font-weight: bold; color: #006837; font-size: 8pt; margin-bottom: 3px;">
                    A. Sebaran Wilayah Domisili / PAC
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 25px;">No</th>
                            <th>Kecamatan / Wilayah PAC</th>
                            <th class="text-center" style="width: 50px;">Kader</th>
                            <th class="text-center" style="width: 45px;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $activeTotal = max(1, $stats['terverifikasi'] + $stats['pengurus']); @endphp
                        @forelse($districtStats as $idx => $d)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $d->kecamatan }}</td>
                                <td class="text-center">{{ $d->total }}</td>
                                <td class="text-center">{{ round(($d->total / $activeTotal) * 100, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data wilayah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="font-weight: bold; color: #006837; font-size: 8pt; margin-bottom: 3px; margin-top: 6px;">
                    B. Jenjang Pendidikan Terakhir
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 25px;">No</th>
                            <th>Jenjang Pendidikan</th>
                            <th class="text-center" style="width: 50px;">Kader</th>
                            <th class="text-center" style="width: 45px;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($educationStats as $idx => $edu)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $edu->level }}</td>
                                <td class="text-center">{{ $edu->total }}</td>
                                <td class="text-center">{{ round(($edu->total / $activeTotal) * 100, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data pendidikan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <!-- Kolom Kanan: Profesi & Kaderisasi NU -->
            <td style="width: 50%; padding-left: 4px;">
                <div style="font-weight: bold; color: #006837; font-size: 8pt; margin-bottom: 3px;">
                    C. Persebaran Profesi & Pekerjaan Utama
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 25px;">No</th>
                            <th>Profesi / Pekerjaan</th>
                            <th class="text-center" style="width: 50px;">Kader</th>
                            <th class="text-center" style="width: 45px;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($occupationStats as $idx => $occ)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $occ->occupation }}</td>
                                <td class="text-center">{{ $occ->total }}</td>
                                <td class="text-center">{{ round(($occ->total / $activeTotal) * 100, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada data profesi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="font-weight: bold; color: #006837; font-size: 8pt; margin-bottom: 3px; margin-top: 6px;">
                    D. Riwayat Pendidikan Kaderisasi NU
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 25px;">No</th>
                            <th>Jenjang Kaderisasi NU</th>
                            <th class="text-center" style="width: 60px;">Kader</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nuTrainingStats as $idx => $nu)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $nu->training_type }}</td>
                                <td class="text-center">{{ $nu->total }} Kader</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data kaderisasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- BAGIAN 2: REKAPITULASI BIDANG KEAHLIAN & SERTIFIKASI -->
    <div class="section-header">2. Rekapitulasi Bidang Keahlian & Sertifikasi Kompetensi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 25%;">Bidang Keahlian / Kompetensi</th>
                <th class="text-center" style="width: 60px;">Jml Kader</th>
                <th class="text-center" style="width: 60px;">Total Sertifikat</th>
                <th>Rincian Contoh Sertifikasi & Lembaga Penerbit Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($skillsStats as $idx => $skill)
                @php
                    $examples = $fieldCertExamples->get($skill->field, collect());
                    $sampleList = $examples->take(3)->map(function($c) {
                        return $c->certification_name . ($c->issuing_organization ? ' (' . $c->issuing_organization . ')' : '');
                    })->implode('; ');
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold text-success">{{ $skill->field }}</td>
                    <td class="text-center fw-bold">{{ $skill->total_kader }} Orang</td>
                    <td class="text-center">{{ $skill->total_sertifikat }}</td>
                    <td style="font-size: 7.5pt; color: #444444;">
                        {{ $sampleList ?: 'Sertifikasi kompetensi bidang ' . $skill->field }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada data sertifikasi keahlian terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Page Break before Member & Officer Lists for neat layout -->
    <div class="page-break"></div>

    <!-- BAGIAN 3: DAFTAR PENGURUS ISNU -->
    <div class="section-header">3. Daftar Fungsionaris Pengurus ISNU</div>
    <p style="font-size: 7.5pt; color: #555555; margin-top: -2px; margin-bottom: 6px;">
        Berikut adalah daftar susunan fungsionaris pengurus aktif yang tercatat dalam sistem kepengurusan ISNU:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 28%;">Nama Lengkap & Gelar</th>
                <th style="width: 18%;">Jabatan / Posisi</th>
                <th style="width: 10%;" class="text-center">Tingkat</th>
                <th style="width: 24%;">Seksi / Bidang / PAC</th>
                <th style="width: 16%;">No. Anggota / Kontak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($officers as $idx => $pos)
                @php
                    $m = $pos->member;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong class="text-dark">{{ $m?->full_name ?? '-' }}</strong>
                        @if($m && $m->educations->isNotEmpty())
                            <div style="font-size: 7pt; color: #666666;">
                                Pend: {{ $m->educations->sortByDesc('end_year')->first()?->level }} {{ $m->educations->sortByDesc('end_year')->first()?->major }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge-pill badge-officer">{{ $pos->position_title }}</span>
                    </td>
                    <td class="text-center fw-bold">
                        {{ $pos->level ?? ($pos->pac ? 'PAC' : 'PC') }}
                    </td>
                    <td>
                        @if($pos->section)
                            <strong>{{ $pos->section->name }}</strong>
                        @elseif($pos->pac)
                            <strong>{{ $pos->pac->name }}</strong>
                        @else
                            <span class="text-muted">Pengurus Harian Cabang</span>
                        @endif
                    </td>
                    <td style="font-size: 7pt;">
                        <div><code>{{ $m?->member_number ?? '-' }}</code></div>
                        <div class="text-muted">{{ $m?->phone ?? '-' }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada data pengurus aktif.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- BAGIAN 4: DAFTAR ANGGOTA ISNU -->
    <div class="section-header" style="margin-top: 14px;">4. Daftar Anggota Terdaftar & Terverifikasi ISNU</div>
    <p style="font-size: 7.5pt; color: #555555; margin-top: -2px; margin-bottom: 6px;">
        Berikut adalah daftar kader/anggota ISNU yang telah terdaftar dan terverifikasi secara resmi:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 22px;">No</th>
                <th style="width: 24%;">Nama Lengkap & Gelar</th>
                <th style="width: 14%;">No. Anggota</th>
                <th style="width: 13%;">Kecamatan / PAC</th>
                <th style="width: 14%;">Pendidikan Terakhir</th>
                <th style="width: 15%;">Profesi / Pekerjaan</th>
                <th style="width: 12%;">Bidang Keahlian</th>
                <th style="width: 8%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $idx => $m)
                @php
                    $lastEdu = $m->educations->sortByDesc('end_year')->first();
                    $certFields = $m->certifications->pluck('field')->unique()->filter()->take(2)->implode(', ');
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong class="text-dark">{{ $m->full_name }}</strong>
                        @if($m->nik)
                            <div style="font-size: 6.8pt; color: #666666;">
                                NIK: {{ substr($m->nik, 0, 6) }}******
                            </div>
                        @endif
                    </td>
                    <td>
                        <code>{{ $m->member_number ?? '-' }}</code>
                    </td>
                    <td>
                        {{ $m->pac?->name ?? ($m->kecamatan ?? '-') }}
                    </td>
                    <td style="font-size: 7.2pt;">
                        @if($lastEdu)
                            <strong>{{ $lastEdu->level }}</strong> {{ $lastEdu->major ? '- ' . $lastEdu->major : '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size: 7.2pt;">
                        {{ $m->occupation ?: '-' }}
                    </td>
                    <td style="font-size: 7.2pt;">
                        {{ $certFields ?: ($m->occupation ?: '-') }}
                    </td>
                    <td class="text-center">
                        @if($m->membership_status === 'pengurus')
                            <span class="badge-pill badge-officer">Pengurus</span>
                        @else
                            <span class="badge-pill">Anggota</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">Belum ada data anggota aktif terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN -->
    <table class="sign-table">
        <tr>
            <td colspan="2" style="text-align: right; padding-bottom: 8px;">
                Diterbitkan di: <strong>Surabaya</strong> &bull; Pada: <strong>{{ now()->translatedFormat('d F Y') }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama</strong><br>
                <strong>Kota Surabaya</strong><br>
                Ketua PC ISNU,
                <div class="sign-space"></div>
                <strong><u>Nurvan Indra Praja, S.Kom., M.MT.</u></strong>
            </td>
            <td>
                Mengetahui / Memverifikasi,<br>
                <strong>Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama</strong><br>
                <strong>Kota Surabaya</strong><br>
                Sekretaris PC ISNU,
                <div class="sign-space"></div>
                <strong><u>Dr. Moh Farih Fahmi, S.Pd., M.Pd</u></strong>
            </td>
        </tr>
    </table>
</body>
</html>
