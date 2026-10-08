<?php

namespace App\Services;

use App\Models\Member;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Str;

class CardImageService
{
    public static function generatePng(Member $member): string
    {
        $card = $member->activeCard;
        $isOfficer = ($card && $card->card_type === 'OFFICER') || $member->membership_status === 'pengurus';
        $activePosition = $member->activePosition;

        $width = 960;
        $height = 606;

        $img = imagecreatetruecolor($width, $height);

        // Background Color (#06402b for officer, #006837 for member)
        $bgColor = $isOfficer ? imagecolorallocate($img, 6, 64, 43) : imagecolorallocate($img, 0, 104, 55);
        imagefill($img, 0, 0, $bgColor);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $gold = imagecolorallocate($img, 243, 229, 171);
        $darkGold = imagecolorallocate($img, 212, 175, 55);
        $lightGray = imagecolorallocate($img, 221, 221, 221);
        $mutedGray = imagecolorallocate($img, 187, 187, 187);
        $darkGreen = imagecolorallocate($img, 0, 104, 55);
        $black = imagecolorallocate($img, 0, 0, 0);
        $dividerColor = imagecolorallocatealpha($img, 255, 255, 255, 90);

        // 1. Watermark Suroboyo Icon
        $suroboyoPath = public_path('images/suroboyo_icon.png');
        if (file_exists($suroboyoPath)) {
            $suroboyo = @imagecreatefrompng($suroboyoPath);
            if ($suroboyo) {
                $sw = imagesx($suroboyo);
                $sh = imagesy($suroboyo);
                imagecopyresampled($img, $suroboyo, $width - 520, $height - 520, 0, 0, 540, 540, $sw, $sh);

            }
        }

        // 2. Header Logo Box & Logo
        imagefilledrectangle($img, 32, 28, 120, 116, $white);
        $logoPath = public_path('images/logo_isnu.png');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                imagecopyresampled($img, $logo, 42, 38, 0, 0, 68, 68, $lw, $lh);

            }
        }

        // Header Text
        $ttfBold = public_path('fonts/PlusJakartaSans-Bold.ttf');
        $ttfRegular = public_path('fonts/PlusJakartaSans-Regular.ttf');
        $hasTtf = file_exists($ttfBold);

        if ($hasTtf) {
            imagettftext($img, 22, 0, 136, 64, $gold, $ttfBold, 'ISNU KOTA SURABAYA');
            imagettftext($img, 15, 0, 136, 96, $lightGray, $ttfRegular, 'Ikatan Sarjana Nahdlatul Ulama');
        } else {
            imagestring($img, 5, 136, 40, 'ISNU KOTA SURABAYA', $gold);
            imagestring($img, 4, 136, 70, 'Ikatan Sarjana Nahdlatul Ulama', $lightGray);
        }

        // Status Badge (Top Right)
        $badgeText = $isOfficer ? 'PENGURUS' : 'ANGGOTA';
        $badgeBg = $isOfficer ? $darkGold : $white;
        $badgeFg = $isOfficer ? $black : $darkGreen;
        imagefilledrectangle($img, $width - 210, 36, $width - 32, 78, $badgeBg);
        if ($hasTtf) {
            $bbox = imagettfbbox(14, 0, $ttfBold, $badgeText);
            $tw = $bbox[2] - $bbox[0];
            $tx = ($width - 121) - ($tw / 2);
            imagettftext($img, 14, 0, (int) $tx, 64, $badgeFg, $ttfBold, $badgeText);
        } else {
            imagestring($img, 4, $width - 170, 48, $badgeText, $badgeFg);
        }

        // 3. Heading + Dividers
        imageline($img, 32, 146, 272, 146, $dividerColor);
        $headingText = 'KARTU '.($isOfficer ? 'PENGURUS' : 'ANGGOTA').' DIGITAL';
        if ($hasTtf) {
            imagettftext($img, 15, 0, 300, 152, $white, $ttfBold, $headingText);
        } else {
            imagestring($img, 5, 340, 138, $headingText, $white);
        }
        imageline($img, $width - 272, 146, $width - 32, 146, $dividerColor);

        // 4. Member Photo
        $photoSrc = null;
        if ($member->photo && file_exists(storage_path('app/public/'.$member->photo))) {
            $path = storage_path('app/public/'.$member->photo);
            $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($type, ['jpg', 'jpeg'])) {
                $photoSrc = @imagecreatefromjpeg($path);
            } elseif ($type === 'png') {
                $photoSrc = @imagecreatefrompng($path);
            }
        }
        imagefilledrectangle($img, 32, 180, 220, 416, $white);
        if ($photoSrc) {
            $pw = imagesx($photoSrc);
            $ph = imagesy($photoSrc);
            imagecopyresampled($img, $photoSrc, 36, 184, 0, 0, 180, 228, $pw, $ph);
        }

        // 5. Member Info
        $nameText = $member->full_name;
        $numberText = $member->member_number ?? 'ISNU-SBY-26-PENDING';

        if ($hasTtf) {
            imagettftext($img, 22, 0, 240, 215, $white, $ttfBold, $nameText);
            imagettftext($img, 14, 0, 240, 260, $mutedGray, $ttfRegular, 'No. Anggota:');
            imagettftext($img, 22, 0, 240, 300, $gold, $ttfBold, $numberText);

            if ($isOfficer && $activePosition) {
                $posTitle = $activePosition->position_title;
                $posLevel = 'PC ISNU Kota Surabaya';
                if (in_array($activePosition->level, ['PAC', 'PAC ISNU'])) {
                    $pacName = $activePosition->pac ? $activePosition->pac->name : ($member->pac ? $member->pac->name : ($member->kecamatan ?? ''));
                    $cleanPacName = Str::replaceFirst('PAC ISNU ', '', Str::replaceFirst('PAC ', '', $pacName));
                    $posLevel = 'PAC ISNU '.$cleanPacName;
                }
                imagettftext($img, 18, 0, 240, 350, $gold, $ttfBold, $posTitle);
                imagettftext($img, 16, 0, 240, 385, $white, $ttfBold, $posLevel);
                if ($activePosition->period) {
                    imagettftext($img, 14, 0, 240, 415, $lightGray, $ttfRegular, 'Periode: '.$activePosition->period);
                }
            } else {
                if ($member->occupation) {
                    imagettftext($img, 16, 0, 240, 350, $lightGray, $ttfRegular, $member->occupation);
                }
                $locText = $member->kecamatan ? 'Kec. '.$member->kecamatan : ($member->mwc ? $member->mwc->name : '');
                if ($locText) {
                    imagettftext($img, 16, 0, 240, 385, $lightGray, $ttfRegular, $locText);
                }
            }
        } else {
            imagestring($img, 5, 240, 190, $nameText, $white);
            imagestring($img, 4, 240, 230, 'No. Anggota: '.$numberText, $gold);
        }

        // 6. QR Code
        $qrToken = $card->qr_token ?? 'preview';
        $verifyUrl = route('verify.card', ['qr_token' => $qrToken]);
        try {
            $matrix = Encoder::encode($verifyUrl, ErrorCorrectionLevel::L())->getMatrix();
            $mw = $matrix->getWidth();
            $mh = $matrix->getHeight();
            $boxSize = 152;
            $qrX = $width - 184;
            $qrY = 296;

            imagefilledrectangle($img, $qrX - 8, $qrY - 8, $qrX + $boxSize + 8, $qrY + $boxSize + 24, $white);

            $scale = floor($boxSize / $mw);
            $offsetX = $qrX + (int) (($boxSize - ($mw * $scale)) / 2);
            $offsetY = $qrY + (int) (($boxSize - ($mh * $scale)) / 2);

            for ($y = 0; $y < $mh; $y++) {
                for ($x = 0; $x < $mw; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        imagefilledrectangle(
                            $img,
                            $offsetX + ($x * $scale),
                            $offsetY + ($y * $scale),
                            $offsetX + (($x + 1) * $scale) - 1,
                            $offsetY + (($y + 1) * $scale) - 1,
                            $black
                        );
                    }
                }
            }

            if ($hasTtf) {
                imagettftext($img, 9, 0, $qrX + 16, $qrY + $boxSize + 16, $black, $ttfBold, 'SCAN TO VERIFY');
            } else {
                imagestring($img, 2, $qrX + 16, $qrY + $boxSize + 4, 'SCAN TO VERIFY', $black);
            }
        } catch (\Throwable $e) {
            // fallback if QR generation fails
        }

        // 7. Footer
        imageline($img, 32, $height - 66, $width - 32, $height - 66, $dividerColor);
        if ($hasTtf) {
            imagettftext($img, 14, 0, 32, $height - 28, $mutedGray, $ttfRegular, 'Berlaku Selamanya');
            imagettftext($img, 14, 0, $width - 340, $height - 28, $mutedGray, $ttfRegular, 'Diterbitkan oleh PC ISNU Kota Surabaya');
        } else {
            imagestring($img, 3, 32, $height - 40, 'Berlaku Selamanya', $mutedGray);
            imagestring($img, 3, $width - 320, $height - 40, 'Diterbitkan oleh PC ISNU Kota Surabaya', $mutedGray);
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();

        return $pngData;
    }
}
