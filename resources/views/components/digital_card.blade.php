@props(['member', 'card'])

@php
    $isOfficer = ($card && $card->card_type === 'OFFICER') || $member->membership_status === 'pengurus';
    $activePosition = $member->activePosition;
    $qrToken = $card->qr_token ?? 'preview';
    $verifyUrl = route('verify.card', ['qr_token' => $qrToken]);

    try {
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->format('svg')->margin(1)->generate($verifyUrl);
        $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
    } catch (\Throwable $e) {
        $qrSrc = route('verify.qr', ['qr_token' => $qrToken]);
    }

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

<div class="digital-card-container mx-auto" style="width: 100%; max-width: 480px; aspect-ratio: 1.586 / 1;">
    <div
        class="digital-card {{ $isOfficer ? 'card-officer' : 'card-member' }} p-3 p-sm-4 d-flex flex-column justify-content-between h-100 position-relative overflow-hidden text-white rounded-4 shadow-lg">

        <!-- Watermark / Background Texture -->
        <div class="card-bg-watermark position-absolute"></div>
        <div class="card-bg-gradient position-absolute"></div>

        <!-- Suroboyo Icon Watermark (Diperbesar & Opacity 60% di belakang QR Code) -->
        <div class="card-suroboyo-watermark position-absolute">
            <img src="{{ asset('images/suroboyo_icon.png') }}" alt="Suroboyo Icon Watermark">
        </div>

        <!-- Header Card: Logos & Organization -->
        <div class="d-flex justify-content-between align-items-center position-relative z-1">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white p-1 rounded-3 shadow-sm d-flex align-items-center justify-content-center"
                    style="width: 40px; height: 40px;">
                    <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU"
                        style="max-height: 34px; max-width: 34px; object-fit: contain;">
                </div>
                <div class="lh-1">
                    <div class="fw-extrabold text-uppercase text-gold"
                        style="font-size: 0.85rem; letter-spacing: 0.5px;">ISNU KOTA SURABAYA</div>
                    <small style="font-size: 0.65rem; opacity: 0.85;">Ikatan Sarjana Nahdlatul Ulama</small>
                </div>
            </div>

            <div class="text-end">
                <span
                    class="badge {{ $isOfficer ? 'bg-warning text-dark' : 'bg-light text-success' }} fw-bold px-2 py-1 uppercase"
                    style="font-size: 0.65rem; letter-spacing: 1px;">
                    {{ $isOfficer ? 'PENGURUS' : 'ANGGOTA' }}
                </span>
            </div>
        </div>

        <!-- Card Title Header -->
        <div class="text-center position-relative z-1 my-1">
            <span class="text-uppercase fw-bold border-bottom border-light border-opacity-25 pb-1 px-3"
                style="font-size: 0.75rem; letter-spacing: 2px; color: rgba(255,255,255,0.9);">
                KARTU {{ $isOfficer ? 'PENGURUS' : 'ANGGOTA' }} DIGITAL
            </span>
        </div>

        <!-- Body Card: Photo & Detail Info -->
        <div class="d-flex align-items-center gap-3 position-relative z-1 my-auto">
            <!-- Member Photo -->
            <div class="flex-shrink-0 position-relative">
                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}"
                    class="rounded-3 border border-2 border-white shadow-sm object-fit-cover"
                    style="width: 80px; height: 95px;">
            </div>

            <!-- Member Details -->
            <div class="flex-grow-1 overflow-hidden">
                <h6 class="fw-bold text-white mb-1" style="font-size: clamp(0.75rem, 2.5vw, 0.95rem); line-height: 1.25; word-break: break-word;"
                    title="{{ $member->full_name }}">
                    {{ $member->full_name }}
                </h6>

                <div class="mb-1" style="font-size: 0.78rem;">
                    <span class="text-white-50">No. Anggota:</span><br>
                    <strong class="font-monospace text-gold fw-bold" style="font-size: 0.85rem;">
                        {{ $member->member_number ?? 'ISNU-SBY-26-PENDING' }}
                    </strong>
                </div>

                @if($isOfficer && $activePosition)
                    <div style="font-size: 0.75rem;" class="text-warning">
                        <i class="bi bi-award-fill me-1"></i> <strong>{{ $activePosition->position_title }}</strong>
                        <div class="text-white fw-semibold" style="font-size: 0.7rem;">{{ $officerLevelText }}</div>
                        @if($activePosition->period)
                            <div class="text-white-50" style="font-size: 0.68rem;">Periode: {{ $activePosition->period }}</div>
                        @endif
                    </div>
                @else
                    <div style="font-size: 0.72rem;" class="text-white-50 text-truncate">
                        <i class="bi bi-briefcase me-1"></i> {{ $member->occupation }}
                    </div>
                    @if($member->kecamatan)
                        <div style="font-size: 0.68rem;" class="text-white-50 text-truncate">
                            <i class="bi bi-geo-alt me-1"></i> Kec. {{ $member->kecamatan }}
                        </div>
                    @elseif($member->mwc)
                        <div style="font-size: 0.68rem;" class="text-white-50 text-truncate">
                            <i class="bi bi-geo-alt me-1"></i> {{ $member->mwc->name }}
                        </div>
                    @endif
                @endif
            </div>

            <!-- QR Code Section -->
            <div class="flex-shrink-0 text-center bg-white p-1 rounded-3 shadow-sm position-relative z-2">
                <img src="{{ $qrSrc }}" alt="QR Code" style="width: 65px; height: 65px; display: block;">
                <span class="text-dark fw-bold d-block mt-1" style="font-size: 0.55rem; letter-spacing: -0.2px;">SCAN TO
                    VERIFY</span>
            </div>
        </div>

        <!-- Footer Card -->
        <div class="d-flex justify-content-between align-items-center position-relative z-1 pt-2 border-top border-white border-opacity-10"
            style="font-size: 0.65rem; color: rgba(255,255,255,0.75);">
            <span>Berlaku Selamanya</span>
            <span>Diterbitkan oleh PC ISNU Kota Surabaya</span>
        </div>
    </div>
</div>

<style>
    .digital-card {
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 10px 30px rgba(0, 77, 40, 0.3) !important;
    }

    .card-member {
        background: linear-gradient(135deg, #004d28 0%, #006837 60%, #0d8a4d 100%);
    }

    .card-officer {
        background: linear-gradient(135deg, #092015 0%, #06402b 50%, #1a3c26 100%);
        border: 2px solid #d4af37;
    }

    .text-gold {
        color: #f3e5ab !important;
    }

    .card-bg-watermark {
        top: -20px;
        right: -20px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .card-officer .card-bg-watermark {
        background: radial-gradient(circle, rgba(212, 175, 55, 0.18) 0%, transparent 70%);
    }

    .card-suroboyo-watermark {
        right: -10px;
        bottom: 5px;
        width: 270px;
        height: 270px;
        opacity: 0.2;
        /* Opacity 20% sesuai instruksi */
        pointer-events: none;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-suroboyo-watermark img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
</style>