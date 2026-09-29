<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: 480pt 303pt;
        }
        html {
            margin: 0; padding: 0;
            width: 480pt; height: 303pt;
        }
        body {
            margin: 0; padding: 0;
            width: 480pt; height: 303pt;
            overflow: hidden;
            position: relative;
            background-color: {{ $isOfficer ? '#06402b' : '#006837' }};
        }

        /* === Suroboyo watermark === */
        .bg-watermark {
            position: absolute;
            right: -15pt; bottom: -10pt;
            width: 270pt; height: 270pt;
            opacity: 0.20;
        }

        /* === HEADER === */
        .logo-box {
            position: absolute;
            top: 14pt; left: 16pt;
            width: 44pt; height: 44pt;
            background-color: #ffffff;
            border-radius: 8pt;
        }
        .logo-img {
            position: absolute;
            top: 5pt; left: 5pt;
            width: 34pt; height: 34pt;
            object-fit: contain;
        }
        .org-title {
            position: absolute;
            top: 18pt; left: 68pt;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            color: #f3e5ab;
            letter-spacing: 0.5pt;
        }
        .org-subtitle {
            position: absolute;
            top: 33pt; left: 68pt;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #dddddd;
        }
        .badge-status {
            position: absolute;
            top: 18pt; right: 16pt;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 4pt 10pt;
            border-radius: 20pt;
            background-color: {{ $isOfficer ? '#d4af37' : '#ffffff' }};
            color: {{ $isOfficer ? '#000000' : '#006837' }};
            text-transform: uppercase;
            letter-spacing: 0.8pt;
        }

        /* === HEADING + DIVIDERS === */
        .heading-line-left {
            position: absolute;
            top: 73pt; left: 16pt;
            width: 120pt; height: 0.5pt;
            background-color: rgba(255,255,255,0.4);
        }
        .card-heading {
            position: absolute;
            top: 66pt; left: 136pt; right: 136pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 2pt;
            color: #f0f0f0;
        }
        .heading-line-right {
            position: absolute;
            top: 73pt; right: 16pt;
            width: 120pt; height: 0.5pt;
            background-color: rgba(255,255,255,0.4);
        }

        /* === PHOTO === */
        .photo-img {
            position: absolute;
            top: 90pt; left: 16pt;
            width: 94pt; height: 118pt;
            border-radius: 8pt;
            object-fit: cover;
            border: 2.5pt solid #ffffff;
        }

        /* === MEMBER INFO (center) === */
        .member-name {
            position: absolute;
            top: 90pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            color: #ffffff;
            line-height: 1.3;
        }
        .member-no-lbl {
            position: absolute;
            top: 135pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #bbbbbb;
        }
        .member-no-val {
            position: absolute;
            top: 149pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Courier, monospace;
            font-size: 14pt;
            font-weight: bold;
            color: #f3e5ab;
        }
        .member-extra1 {
            position: absolute;
            top: 180pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #cccccc;
        }
        .member-extra2 {
            position: absolute;
            top: 196pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #cccccc;
        }
        .officer-pos {
            position: absolute;
            top: 170pt; left: 120pt; right: 98pt;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            color: #f3e5ab;
            line-height: 1.25;
        }
        .officer-level {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;
            margin-top: 2pt;
        }
        .officer-period {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            font-weight: normal;
            color: #cccccc;
            margin-top: 2pt;
        }

        /* === QR CODE === */
        .qr-box {
            position: absolute;
            top: 148pt; right: 14pt;
            width: 76pt;
            text-align: center;
            background-color: #ffffff;
            padding: 3pt;
            border-radius: 6pt;
        }
        .qr-img {
            width: 70pt; height: 70pt;
            display: block;
        }
        .qr-lbl {
            display: block;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 5pt;
            font-weight: bold;
            color: #000000;
            margin-top: 2pt;
            letter-spacing: 0.3pt;
        }

        /* === FOOTER === */
        .footer-border {
            position: absolute;
            bottom: 33pt; left: 16pt; right: 16pt;
            height: 0.5pt;
            background-color: rgba(255,255,255,0.3);
        }
        .footer-left {
            position: absolute;
            bottom: 12pt; left: 16pt;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #bbbbbb;
        }
        .footer-right {
            position: absolute;
            bottom: 12pt; right: 16pt;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #bbbbbb;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $awardSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#f3e5ab" viewBox="0 0 16 16"><path d="M8 0l1.669.864 1.858.282.842 1.68 1.337 1.337-.282 1.858.864 1.669-.864 1.669.282 1.858-1.337 1.337-.842 1.68-1.858.282L8 0z"/><path d="M4 11.794V16l4-1 4 1v-4.206l-4-1-4 1z"/></svg>';
        $awardSrc = 'data:image/svg+xml;base64,' . base64_encode($awardSvg);

        $briefcaseSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#cccccc" viewBox="0 0 16 16"><path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5zm1.886 6.914L15 7.151V12.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V7.15l6.614 1.764a1.5 1.5 0 0 0 .772 0zM1.5 4h13a.5.5 0 0 1 .5.5v1.616L8.129 7.948a.5.5 0 0 1-.258 0L1 6.116V4.5a.5.5 0 0 1 .5-.5z"/></svg>';
        $briefcaseSrc = 'data:image/svg+xml;base64,' . base64_encode($briefcaseSvg);

        $locationSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#cccccc" viewBox="0 0 16 16"><path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/><path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>';
        $locationSrc = 'data:image/svg+xml;base64,' . base64_encode($locationSvg);

        $officerLevelText = '';
        if ($isOfficer && $activePosition) {
            if (in_array($activePosition->level, ['PAC', 'PAC ISNU'])) {
                $pacName = $activePosition->pac ? $activePosition->pac->name : ($member->pac ? $member->pac->name : ($member->kecamatan ?? ''));
                $cleanPacName = \Illuminate\Support\Str::replaceFirst('PAC ISNU ', '', \Illuminate\Support\Str::replaceFirst('PAC ', '', $pacName));
                $officerLevelText = 'PAC ISNU ' . $cleanPacName;
            } else {
                $officerLevelText = 'PC ISNU Kota Surabaya';
            }
        }
    @endphp
    <!-- Suroboyo watermark -->
    @if($suroboyoSrc)
        <img src="{{ $suroboyoSrc }}" class="bg-watermark">
    @endif

    <!-- === HEADER === -->
    <div class="logo-box">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" class="logo-img">
        @endif
    </div>
    <div class="org-title">ISNU KOTA SURABAYA</div>
    <div class="org-subtitle">Ikatan Sarjana Nahdlatul Ulama</div>
    <div class="badge-status">{{ $isOfficer ? 'PENGURUS' : 'ANGGOTA' }}</div>

    <!-- === HEADING + DIVIDERS === -->
    <div class="heading-line-left"></div>
    <div class="card-heading">KARTU {{ $isOfficer ? 'PENGURUS' : 'ANGGOTA' }} DIGITAL</div>
    <div class="heading-line-right"></div>

    <!-- === PHOTO === -->
    @if($photoSrc)
        <img src="{{ $photoSrc }}" class="photo-img">
    @endif

    <!-- === MEMBER INFO === -->
    <div class="member-name">{{ $member->full_name }}</div>
    <div class="member-no-lbl">No. Anggota:</div>
    <div class="member-no-val">{{ $member->member_number ?? 'ISNU-SBY-26-PENDING' }}</div>

    @if($isOfficer && $activePosition)
        <div class="officer-pos">
            <img src="{{ $awardSrc }}" style="width:11pt; height:11pt; vertical-align:-1pt; margin-right:4pt;">{{ $activePosition->position_title }}
            <div class="officer-level">{{ $officerLevelText }}</div>
            @if($activePosition->period)
                <div class="officer-period">Periode: {{ $activePosition->period }}</div>
            @endif
        </div>
    @else
        <div class="member-extra1"><img src="{{ $briefcaseSrc }}" style="width:10pt;height:10pt;vertical-align:middle;"> {{ $member->occupation }}</div>
        @if($member->kecamatan)
            <div class="member-extra2"><img src="{{ $locationSrc }}" style="width:10pt;height:10pt;vertical-align:middle;"> Kec. {{ $member->kecamatan }}</div>
        @elseif($member->mwc)
            <div class="member-extra2"><img src="{{ $locationSrc }}" style="width:10pt;height:10pt;vertical-align:middle;"> {{ $member->mwc->name }}</div>
        @endif
    @endif

    <!-- === QR CODE === -->
    <div class="qr-box">
        @if($qrBase64)
            <img src="{{ $qrBase64 }}" class="qr-img">
        @endif
        <span class="qr-lbl">SCAN TO VERIFY</span>
    </div>

    <!-- === FOOTER === -->
    <div class="footer-border"></div>
    <div class="footer-left">Berlaku Selamanya</div>
    <div class="footer-right">Diterbitkan oleh PC ISNU Kota Surabaya</div>
</body>
</html>
