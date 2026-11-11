<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\User;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class LetterService
{
    /**
     * Menghasilkan gambar QR Code format PNG murni menggunakan GD.
     */
    public function generateQrPng(string $content, int $pixelSize = 6, int $margin = 2): string
    {
        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $width = $matrix->getWidth();
        $height = $matrix->getHeight();

        $totalWidth = ($width + $margin * 2) * $pixelSize;
        $totalHeight = ($height + $margin * 2) * $pixelSize;

        $img = imagecreatetruecolor($totalWidth, $totalHeight);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $img,
                        ($x + $margin) * $pixelSize,
                        ($y + $margin) * $pixelSize,
                        ($x + $margin + 1) * $pixelSize - 1,
                        ($y + $margin + 1) * $pixelSize - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();

        return (string) $pngData;
    }

    /**
     * Membangun payload teks QR Code sesuai ketentuan:
     * Berisi nama, no registrasi, jabatan, dan link verifikasi.
     */
    public function buildQrPayload(Letter $letter, string $signerType): string
    {
        $verificationUrl = route('surat.verifikasi', ['code' => $letter->letter_code]);

        if ($signerType === 'secretary') {
            $name = $letter->secretary_name;
            $regNumber = $letter->secretary_registration_number;
            $position = $letter->secretary_position;
            $signedAt = $letter->secretary_signed_at?->format('d/m/Y H:i:s');
        } else {
            $name = $letter->chairman_name;
            $regNumber = $letter->chairman_registration_number;
            $position = $letter->chairman_position;
            $signedAt = $letter->chairman_signed_at?->format('d/m/Y H:i:s');
        }

        $orgName = $letter->isLevelPac() && $letter->pac
            ? 'PAC ISNU KECAMATAN '.strtoupper($letter->pac_kecamatan_name)
            : 'PC ISNU KOTA SURABAYA';

        return "E-TTD RESMI {$orgName}\n".
            "Nama: {$name}\n".
            "No Registrasi: {$regNumber}\n".
            "Jabatan: {$position}\n".
            "No Surat: {$letter->formatted_letter_number}\n".
            "Waktu TTD: {$signedAt}\n".
            "Status: Sah & Terverifikasi\n".
            "Verifikasi Dokumen: {$verificationUrl}";
    }

    /**
     * Proses penandatanganan digital oleh Sekretaris (Tahap 1).
     */
    public function signAsSecretary(Letter $letter, User $user): Letter
    {
        $member = $user->member;
        $name = $member?->full_name ?: $user->name;
        $regNumber = $member?->member_number ?: ('ISNU-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT));
        $rawPosition = $user->getOfficerPositionTitle() ?: 'Sekretaris';
        $position = ($letter->isLevelPac() && $letter->pac)
            ? "{$rawPosition} {$letter->pac_display_name}"
            : $rawPosition;

        $letter->secretary_user_id = $user->id;
        $letter->secretary_name = $name;
        $letter->secretary_registration_number = $regNumber;
        $letter->secretary_position = $position;
        $letter->secretary_signed_at = now();
        $letter->status = Letter::STATUS_MENUNGGU_KETUA;
        $letter->rejection_note = null;

        // Generate QR code Sekretaris
        $qrContent = $this->buildQrPayload($letter, 'secretary');
        $qrPng = $this->generateQrPng($qrContent);

        $qrDir = 'letters/qrcodes';
        Storage::disk('public')->makeDirectory($qrDir);
        $qrPath = "{$qrDir}/qr_sec_{$letter->letter_code}.png";
        Storage::disk('public')->put($qrPath, $qrPng);
        $letter->secretary_qr_path = $qrPath;

        $letter->save();

        // Generate preview DOCX & PDF
        $this->generateDocx($letter);
        $this->generatePdf($letter);

        return $letter;
    }

    /**
     * Proses penandatanganan digital oleh Ketua (Tahap 2).
     */
    public function signAsChairman(Letter $letter, User $user): Letter
    {
        $member = $user->member;
        $name = $member?->full_name ?: $user->name;
        $regNumber = $member?->member_number ?: ('ISNU-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT));
        $rawPosition = $user->getOfficerPositionTitle() ?: 'Ketua';
        $position = ($letter->isLevelPac() && $letter->pac)
            ? "{$rawPosition} {$letter->pac_display_name}"
            : $rawPosition;

        $letter->chairman_user_id = $user->id;
        $letter->chairman_name = $name;
        $letter->chairman_registration_number = $regNumber;
        $letter->chairman_position = $position;
        $letter->chairman_signed_at = now();
        $letter->status = Letter::STATUS_SELESAI;
        $letter->rejection_note = null;

        // Generate QR code Ketua
        $qrContent = $this->buildQrPayload($letter, 'chairman');
        $qrPng = $this->generateQrPng($qrContent);

        $qrDir = 'letters/qrcodes';
        Storage::disk('public')->makeDirectory($qrDir);
        $qrPath = "{$qrDir}/qr_chair_{$letter->letter_code}.png";
        Storage::disk('public')->put($qrPath, $qrPng);
        $letter->chairman_qr_path = $qrPath;

        $letter->save();

        // Generate final DOCX & PDF
        $this->generateDocx($letter);
        $this->generatePdf($letter);

        return $letter;
    }

    /**
     * Generate file DOCX menggunakan template public/template/template_surat.docx
     * dengan menyematkan QRCode tanda tangan dan menggantikan 11 placeholder variabel.
     */
    public function generateDocx(Letter $letter): string
    {
        if ($letter->isMandat()) {
            $templatePath = public_path('template/template_mandat.docx');
        } elseif ($letter->isRekomendasi()) {
            $templatePath = public_path('template/template_rekom.docx');
        } else {
            $templatePath = public_path('template/template_surat.docx');
        }

        if (! file_exists($templatePath)) {
            throw new \RuntimeException("File template surat {$templatePath} tidak ditemukan di direktori public/template.");
        }

        $docxDir = 'letters/docx';
        Storage::disk('public')->makeDirectory($docxDir);

        $targetFileName = "Surat_{$letter->letter_code}.docx";
        $relativePath = "{$docxDir}/{$targetFileName}";

        Storage::disk('public')->put($relativePath, (string) file_get_contents($templatePath));
        $targetFilePath = Storage::disk('public')->path($relativePath);

        $zip = new ZipArchive;
        if ($zip->open($targetFilePath) !== true) {
            throw new \RuntimeException("Gagal membuka file DOCX target: {$targetFilePath}");
        }

        // 1. Pastikan Content_Types memiliki ekstensi PNG
        $contentTypes = (string) $zip->getFromName('[Content_Types].xml');
        if (! str_contains($contentTypes, 'Extension="png"')) {
            $contentTypes = str_replace(
                '</Types>',
                '<Default Extension="png" ContentType="image/png"/></Types>',
                $contentTypes
            );
            $zip->addFromString('[Content_Types].xml', $contentTypes);
        }

        // 2. Siapkan relasi dan file media QR Code
        $relsXml = (string) $zip->getFromName('word/_rels/document.xml.rels');
        $docXml = (string) $zip->getFromName('word/document.xml');

        $secretaryDrawingRun = '';
        if ($letter->secretary_signed_at) {
            $secPngData = null;
            $secDataUrl = $letter->secretary_qr_data_url;
            if ($secDataUrl && str_starts_with($secDataUrl, 'data:image/png;base64,')) {
                $secPngData = base64_decode(substr($secDataUrl, strlen('data:image/png;base64,')));
            } elseif ($letter->secretary_qr_path && Storage::disk('public')->exists($letter->secretary_qr_path)) {
                $secPngData = Storage::disk('public')->get($letter->secretary_qr_path);
            }

            if ($secPngData) {
                $secMediaName = "qr_sec_{$letter->letter_code}.png";
                $zip->addFromString("word/media/{$secMediaName}", $secPngData);

                if (! str_contains($relsXml, 'Id="rIdQrSecretary"')) {
                    $relsXml = str_replace(
                        '</Relationships>',
                        '<Relationship Id="rIdQrSecretary" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$secMediaName.'"/></Relationships>',
                        $relsXml
                    );
                }

                $escapedSecName = htmlspecialchars($letter->secretary_name ?? '', ENT_XML1);
                $escapedSecPos = htmlspecialchars($letter->secretary_position ?? 'Sekretaris', ENT_XML1);
                $escapedSecReg = htmlspecialchars($letter->secretary_registration_number ?? '', ENT_XML1);

                $secretaryDrawingRun = '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"><wp:extent cx="1080000" cy="1080000"/><wp:docPr id="101" name="QrSecretary"/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="101" name="QrSecretary"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdQrSecretary" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1080000" cy="1080000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r><w:r><w:br/><w:b/><w:u w:val="single"/><w:t>'.$escapedSecName.'</w:t></w:r><w:r><w:br/><w:t>NIRA/Reg: '.$escapedSecReg.'</w:t></w:r>';
            } else {
                $secretaryDrawingRun = '<w:r><w:rPr><w:i/><w:color w:val="888888"/></w:rPr><w:t>(Menunggu Tanda Tangan Sekretaris)</w:t></w:r>';
            }
        } else {
            $secretaryDrawingRun = '<w:r><w:rPr><w:i/><w:color w:val="888888"/></w:rPr><w:t>(Menunggu Tanda Tangan Sekretaris)</w:t></w:r>';
        }

        $chairmanDrawingRun = '';
        if ($letter->chairman_signed_at) {
            $chairPngData = null;
            $chairDataUrl = $letter->chairman_qr_data_url;
            if ($chairDataUrl && str_starts_with($chairDataUrl, 'data:image/png;base64,')) {
                $chairPngData = base64_decode(substr($chairDataUrl, strlen('data:image/png;base64,')));
            } elseif ($letter->chairman_qr_path && Storage::disk('public')->exists($letter->chairman_qr_path)) {
                $chairPngData = Storage::disk('public')->get($letter->chairman_qr_path);
            }

            if ($chairPngData) {
                $chairMediaName = "qr_chair_{$letter->letter_code}.png";
                $zip->addFromString("word/media/{$chairMediaName}", $chairPngData);

                if (! str_contains($relsXml, 'Id="rIdQrChairman"')) {
                    $relsXml = str_replace(
                        '</Relationships>',
                        '<Relationship Id="rIdQrChairman" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$chairMediaName.'"/></Relationships>',
                        $relsXml
                    );
                }

                $escapedChairName = htmlspecialchars($letter->chairman_name ?? '', ENT_XML1);
                $escapedChairPos = htmlspecialchars($letter->chairman_position ?? 'Ketua', ENT_XML1);
                $escapedChairReg = htmlspecialchars($letter->chairman_registration_number ?? '', ENT_XML1);

                $chairmanDrawingRun = '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"><wp:extent cx="1080000" cy="1080000"/><wp:docPr id="102" name="QrChairman"/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="102" name="QrChairman"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdQrChairman" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1080000" cy="1080000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r><w:r><w:br/><w:b/><w:u w:val="single"/><w:t>'.$escapedChairName.'</w:t></w:r><w:r><w:br/><w:t>NIRA/Reg: '.$escapedChairReg.'</w:t></w:r>';
            } else {
                $chairmanDrawingRun = '<w:r><w:rPr><w:i/><w:color w:val="888888"/></w:rPr><w:t>(Menunggu Tanda Tangan Ketua)</w:t></w:r>';
            }
        } else {
            $chairmanDrawingRun = '<w:r><w:rPr><w:i/><w:color w:val="888888"/></w:rPr><w:t>(Menunggu Tanda Tangan Ketua)</w:t></w:r>';
        }

        $zip->addFromString('word/_rels/document.xml.rels', $relsXml);

        // 3. Konversi HTML / teks isi surat ke OpenXML runs
        $isiSuratXml = $this->htmlToOpenXmlRuns((string) $letter->isi_surat);

        // 4. Penggantian placeholders dalam document.xml
        $rawNumber = (string) $letter->formatted_letter_number;
        $cleanNumber = explode('/', $rawNumber)[0];

        $replacements = [
            'nomor_surat' => '<w:r><w:t>'.htmlspecialchars($cleanNumber, ENT_XML1).'</w:t></w:r>',
            'no_surat' => '<w:r><w:t>'.htmlspecialchars($cleanNumber, ENT_XML1).'</w:t></w:r>',
            'nomor_surat_lengkap' => '<w:r><w:t>'.htmlspecialchars($rawNumber, ENT_XML1).'</w:t></w:r>',
            'bulan_romawi' => '<w:r><w:t>'.htmlspecialchars((string) $letter->roman_month, ENT_XML1).'</w:t></w:r>',
            'no_romawi_bulan' => '<w:r><w:t>'.htmlspecialchars((string) $letter->roman_month, ENT_XML1).'</w:t></w:r>',
            'tahun' => '<w:r><w:t>'.htmlspecialchars((string) $letter->year, ENT_XML1).'</w:t></w:r>',
            'tanggal_surat' => '<w:r><w:t>'.htmlspecialchars((string) $letter->formatted_date_letter, ENT_XML1).'</w:t></w:r>',
            'tgl_surat' => '<w:r><w:t>'.htmlspecialchars((string) $letter->formatted_date_letter, ENT_XML1).'</w:t></w:r>',
            'lokasi_surat' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->kota_asal ?: 'Surabaya'), ENT_XML1).'</w:t></w:r>',
            'lampiran' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->lampiran ?: '-'), ENT_XML1).'</w:t></w:r>',
            'perihal' => '<w:r><w:t>'.htmlspecialchars((string) $letter->perihal, ENT_XML1).'</w:t></w:r>',
            'kegiatan' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->kegiatan ?: $letter->perihal), ENT_XML1).'</w:t></w:r>',
            'tanggal_pelaksanaan' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->tanggal_pelaksanaan ?: '-'), ENT_XML1).'</w:t></w:r>',
            'lokasi_pelaksanaan' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->lokasi_pelaksanaan ?: '-'), ENT_XML1).'</w:t></w:r>',
            'tujuan_surat' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->tujuan_surat ?: '-'), ENT_XML1).'</w:t></w:r>',
            'kota_lokasi_tujuan_surat' => '<w:r><w:t>'.htmlspecialchars((string) ($letter->kota_tujuan ?: 'Surabaya'), ENT_XML1).'</w:t></w:r>',
            'isi_surat_daftar_nama_anggota_pengurus' => $isiSuratXml,
            'isi_rekomendasi' => $isiSuratXml,
            'isi_surat' => $isiSuratXml,
            'ketua_isnu_surabaya' => $chairmanDrawingRun,
            'sekretaris_isnu_surabaya' => $secretaryDrawingRun,
            'nama_ketua' => $chairmanDrawingRun,
            'nama_sekretaris' => $secretaryDrawingRun,
        ];

        foreach ($replacements as $placeholder => $replacementXml) {
            $escaped = preg_quote($placeholder, '#');
            $pattern = '#(?:<w:r\b[^>]*>(?:(?!</w:r>).)*?<w:t[^>]*>[$]</w:t></w:r>(?:(?!<w:r\b).)*?<w:r\b[^>]*>(?:(?!</w:r>).)*?<w:t[^>]*>'.$escaped.'(?:\s*)</w:t></w:r>|<w:r\b[^>]*>(?:(?!</w:r>).)*?<w:t[^>]*>[$]'.$escaped.'(?:\s*)</w:t></w:r>)#s';
            $docXml = (string) preg_replace($pattern, $replacementXml, $docXml);
        }

        $zip->addFromString('word/document.xml', $docXml);
        $zip->close();

        $relativePath = "letters/docx/{$targetFileName}";
        $letter->docx_path = $relativePath;
        $letter->saveQuietly();

        return $relativePath;
    }

    /**
     * Konversi konten HTML (dari Rich Text Editor) menjadi struktur XML runs Word (<w:r>).
     */
    public function htmlToOpenXmlRuns(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Jika teks biasa tanpa HTML tag
        if (! str_contains($html, '<') && ! str_contains($html, '>')) {
            $lines = preg_split("/\r\n|\n|\r/", $html);
            $runs = [];
            foreach ($lines as $i => $line) {
                $escaped = htmlspecialchars($line, ENT_XML1);
                if ($i > 0) {
                    $runs[] = '<w:r><w:br/><w:t xml:space="preserve">'.$escaped.'</w:t></w:r>';
                } else {
                    $runs[] = '<w:r><w:t xml:space="preserve">'.$escaped.'</w:t></w:r>';
                }
            }

            return implode('', $runs);
        }

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }

        $runs = [];

        $traverse = function ($node, $format = []) use (&$traverse, &$runs) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $text = $child->nodeValue;
                    if ($text !== '') {
                        $rPr = '';
                        if (! empty($format['b'])) {
                            $rPr .= '<w:b/>';
                        }
                        if (! empty($format['i'])) {
                            $rPr .= '<w:i/>';
                        }
                        if (! empty($format['u'])) {
                            $rPr .= '<w:u w:val="single"/>';
                        }
                        if (! empty($format['strike'])) {
                            $rPr .= '<w:strike/>';
                        }

                        $escaped = htmlspecialchars($text, ENT_XML1);
                        $runs[] = '<w:r>'.($rPr ? "<w:rPr>{$rPr}</w:rPr>" : '').'<w:t xml:space="preserve">'.$escaped.'</w:t></w:r>';
                    }
                } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                    $tag = strtolower($child->nodeName);
                    $newFormat = $format;

                    if (in_array($tag, ['b', 'strong'], true)) {
                        $newFormat['b'] = true;
                    }
                    if (in_array($tag, ['i', 'em'], true)) {
                        $newFormat['i'] = true;
                    }
                    if ($tag === 'u') {
                        $newFormat['u'] = true;
                    }
                    if (in_array($tag, ['s', 'strike', 'del'], true)) {
                        $newFormat['strike'] = true;
                    }

                    if ($tag === 'br') {
                        $runs[] = '<w:r><w:br/></w:r>';
                    } elseif (in_array($tag, ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
                        if (! empty($runs)) {
                            $runs[] = '<w:r><w:br/></w:r>';
                        }
                        $traverse($child, $newFormat);
                    } elseif ($tag === 'li') {
                        $runs[] = '<w:r><w:br/><w:t xml:space="preserve">• </w:t></w:r>';
                        $traverse($child, $newFormat);
                    } else {
                        $traverse($child, $newFormat);
                    }
                }
            }
        };

        $traverse($body);

        return implode('', $runs);
    }

    /**
     * Generate versi PDF resmi surat ISNU menggunakan dompdf.
     */
    public function generatePdf(Letter $letter): string
    {
        $secQrBase64 = null;
        if ($letter->secretary_signed_at) {
            $secDataUrl = $letter->secretary_qr_data_url;
            if ($secDataUrl && str_starts_with($secDataUrl, 'data:image/png;base64,')) {
                $secQrBase64 = substr($secDataUrl, strlen('data:image/png;base64,'));
            }
        }

        $chairQrBase64 = null;
        if ($letter->chairman_signed_at) {
            $chairDataUrl = $letter->chairman_qr_data_url;
            if ($chairDataUrl && str_starts_with($chairDataUrl, 'data:image/png;base64,')) {
                $chairQrBase64 = substr($chairDataUrl, strlen('data:image/png;base64,'));
            }
        }

        $logoPath = public_path('images/kop_logo_isnu.jpeg');
        $kopLogoBase64 = file_exists($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('admin.letters.pdf', [
            'letter' => $letter,
            'secQrBase64' => $secQrBase64,
            'chairQrBase64' => $chairQrBase64,
            'kopLogoBase64' => $kopLogoBase64,
        ])->setPaper('a4', 'portrait');

        $pdfDir = 'letters/pdf';
        Storage::disk('public')->makeDirectory($pdfDir);

        $targetFileName = "Surat_{$letter->letter_code}.pdf";
        $relativePath = "{$pdfDir}/{$targetFileName}";

        Storage::disk('public')->put($relativePath, $pdf->output());

        $letter->pdf_path = $relativePath;
        $letter->saveQuietly();

        return $relativePath;
    }
}
