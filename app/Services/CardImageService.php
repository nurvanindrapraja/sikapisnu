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

        // Fonts
        $ttfBold = public_path('fonts/PlusJakartaSans-Bold.ttf');
        $ttfRegular = public_path('fonts/PlusJakartaSans-Regular.ttf');
        $hasTtf = file_exists($ttfBold);

        // 1. Watermark Suroboyo Icon (Right Side Background)
        $suroboyoPath = public_path('images/suroboyo_icon.png');
        if (file_exists($suroboyoPath)) {
            $suroboyo = @imagecreatefrompng($suroboyoPath);
            if ($suroboyo) {
                $sw = imagesx($suroboyo);
                $sh = imagesy($suroboyo);

                // Create temporary transparent image for watermark blending (~20% opacity)
                $tempWatermark = imagecreatetruecolor(420, 420);
                imagealphablending($tempWatermark, false);
                imagesavealpha($tempWatermark, true);
                $trans = imagecolorallocatealpha($tempWatermark, 0, 0, 0, 127);
                imagefill($tempWatermark, 0, 0, $trans);

                imagecopyresampled($tempWatermark, $suroboyo, 0, 0, 0, 0, 420, 420, $sw, $sh);

                // Copy with opacity
                imagecopymerge($img, $tempWatermark, $width - 380, 120, 0, 0, 420, 420, 22);
            }
        }

        // 2. Header: Logo ISNU Box
        self::drawFilledRoundedRectangle($img, 36, 26, 126, 116, 16, $white);
        $logoPath = public_path('images/logo_isnu.png');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                imagecopyresampled($img, $logo, 46, 36, 0, 0, 70, 70, $lw, $lh);
            }
        }

        // Header Text
        if ($hasTtf) {
            imagettftext($img, 21, 0, 142, 62, $gold, $ttfBold, 'ISNU KOTA SURABAYA');
            imagettftext($img, 15, 0, 142, 94, $lightGray, $ttfRegular, 'Ikatan Sarjana Nahdlatul Ulama');
        } else {
            imagestring($img, 5, 142, 40, 'ISNU KOTA SURABAYA', $gold);
            imagestring($img, 4, 142, 70, 'Ikatan Sarjana Nahdlatul Ulama', $lightGray);
        }

        // Status Badge (Top Right)
        $badgeText = $isOfficer ? 'PENGURUS' : 'ANGGOTA';
        $badgeBg = $isOfficer ? $darkGold : $white;
        $badgeFg = $isOfficer ? $black : $darkGreen;
        self::drawFilledRoundedRectangle($img, $width - 180, 34, $width - 36, 78, 22, $badgeBg);
        if ($hasTtf) {
            self::drawCenteredText($img, 14, 0, $width - 108, 62, $badgeFg, $ttfBold, $badgeText);
        } else {
            imagestring($img, 4, $width - 150, 48, $badgeText, $badgeFg);
        }

        // 3. Heading + Dividers
        imageline($img, 36, 146, 260, 146, $dividerColor);
        $headingText = 'KARTU '.($isOfficer ? 'PENGURUS' : 'ANGGOTA').' DIGITAL';
        if ($hasTtf) {
            self::drawCenteredText($img, 15, 0, 480, 152, $white, $ttfBold, $headingText);
        } else {
            imagestring($img, 5, 340, 138, $headingText, $white);
        }
        imageline($img, 700, 146, $width - 36, 146, $dividerColor);

        // 4. Member Photo (Left Column)
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

        // Outer White Box for Photo
        self::drawFilledRoundedRectangle($img, 36, 176, 236, 426, 16, $white);
        if ($photoSrc) {
            $pw = imagesx($photoSrc);
            $ph = imagesy($photoSrc);
            imagecopyresampled($img, $photoSrc, 40, 180, 0, 0, 192, 242, $pw, $ph);
        }

        // 5. Middle Details (CENTERED Column between X = 250 and X = 740, Center X = 495)
        $centerX = 495;
        $nameText = $member->full_name;
        $numberText = $member->member_number ?? 'ISNU-SBY-26-PENDING';

        if ($hasTtf) {
            // Dynamic Font Size for Name to ensure fit
            $nameFontSize = 22;
            $bboxName = imagettfbbox($nameFontSize, 0, $ttfBold, $nameText);
            $nameWidth = $bboxName[2] - $bboxName[0];
            if ($nameWidth > 470) {
                $nameFontSize = (int) floor($nameFontSize * (470 / $nameWidth));
                $nameFontSize = max(13, $nameFontSize);
            }

            // Name
            self::drawCenteredText($img, $nameFontSize, 0, $centerX, 216, $white, $ttfBold, $nameText);

            // No. Anggota Label
            self::drawCenteredText($img, 14, 0, $centerX, 264, $mutedGray, $ttfRegular, 'No. Anggota:');

            // No. Anggota Value
            self::drawCenteredText($img, 22, 0, $centerX, 304, $gold, $ttfBold, $numberText);

            if ($isOfficer && $activePosition) {
                $posTitle = $activePosition->position_title;
                $posLevel = 'PC ISNU Kota Surabaya';
                if (in_array($activePosition->level, ['PAC', 'PAC ISNU'])) {
                    $pacName = $activePosition->pac ? $activePosition->pac->name : ($member->pac ? $member->pac->name : ($member->kecamatan ?? ''));
                    $cleanPacName = Str::replaceFirst('PAC ISNU ', '', Str::replaceFirst('PAC ', '', $pacName));
                    $posLevel = 'PAC ISNU '.$cleanPacName;
                }

                // Position Title
                self::drawCenteredText($img, 18, 0, $centerX, 354, $gold, $ttfBold, $posTitle);

                // Level
                self::drawCenteredText($img, 16, 0, $centerX, 390, $white, $ttfBold, $posLevel);

                // Period
                if ($activePosition->period) {
                    self::drawCenteredText($img, 14, 0, $centerX, 420, $lightGray, $ttfRegular, 'Periode: '.$activePosition->period);
                }
            } else {
                if ($member->occupation) {
                    self::drawCenteredText($img, 16, 0, $centerX, 354, $white, $ttfBold, $member->occupation);
                }
                $locText = $member->kecamatan ? 'Kec. '.$member->kecamatan : ($member->mwc ? $member->mwc->name : '');
                if ($locText) {
                    self::drawCenteredText($img, 15, 0, $centerX, 390, $lightGray, $ttfRegular, $locText);
                }
            }
        } else {
            imagestring($img, 5, 340, 190, $nameText, $white);
            imagestring($img, 4, 340, 230, 'No. Anggota: '.$numberText, $gold);
        }

        // 6. QR Code (Right Side White Rounded Box)
        $qrToken = $card->qr_token ?? 'preview';
        $verifyUrl = route('verify.card', ['qr_token' => $qrToken]);
        try {
            $matrix = Encoder::encode($verifyUrl, ErrorCorrectionLevel::L())->getMatrix();
            $mw = $matrix->getWidth();
            $mh = $matrix->getHeight();
            $boxWidth = 164;
            $boxHeight = 168;
            $qrX = 760;
            $qrY = 276;

            // White Box for QR Code
            self::drawFilledRoundedRectangle($img, $qrX, $qrY, $qrX + $boxWidth, $qrY + $boxHeight, 16, $white);

            $scale = floor(128 / $mw);
            $offsetX = $qrX + (int) (($boxWidth - ($mw * $scale)) / 2);
            $offsetY = $qrY + 12;

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
                self::drawCenteredText($img, 9, 0, $qrX + ($boxWidth / 2), $qrY + 154, $black, $ttfBold, 'SCAN TO VERIFY');
            } else {
                imagestring($img, 2, $qrX + 24, $qrY + 144, 'SCAN TO VERIFY', $black);
            }
        } catch (\Throwable $e) {
            // fallback if QR generation fails
        }

        // 7. Footer
        imageline($img, 36, $height - 66, $width - 36, $height - 66, $dividerColor);
        if ($hasTtf) {
            imagettftext($img, 13, 0, 36, $height - 28, $lightGray, $ttfRegular, 'Berlaku Selamanya');
            self::drawRightText($img, 13, 0, $width - 36, $height - 28, $lightGray, $ttfRegular, 'Diterbitkan oleh PC ISNU Kota Surabaya');
        } else {
            imagestring($img, 3, 36, $height - 40, 'Berlaku Selamanya', $lightGray);
            imagestring($img, 3, $width - 320, $height - 40, 'Diterbitkan oleh PC ISNU Kota Surabaya', $lightGray);
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();

        return $pngData;
    }

    private static function drawFilledRoundedRectangle($img, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
    {
        $radius = min($radius, (int) (abs($x2 - $x1) / 2), (int) (abs($y2 - $y1) / 2));
        if ($radius <= 0) {
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $color);

            return;
        }

        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    private static function drawCenteredText($img, float $size, float $angle, float $centerX, float $y, $color, string $font, string $text): void
    {
        $bbox = imagettfbbox($size, $angle, $font, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $x = $centerX - ($textWidth / 2) - $bbox[0];
        imagettftext($img, $size, $angle, (int) $x, (int) $y, $color, $font, $text);
    }

    private static function drawRightText($img, float $size, float $angle, float $rightX, float $y, $color, string $font, string $text): void
    {
        $bbox = imagettfbbox($size, $angle, $font, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $x = $rightX - $textWidth - $bbox[0];
        imagettftext($img, $size, $angle, (int) $x, (int) $y, $color, $font, $text);
    }
}
