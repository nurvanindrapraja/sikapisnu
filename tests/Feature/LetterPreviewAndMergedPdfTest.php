<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\User;
use App\Services\PdfMergerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterPreviewAndMergedPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin_kota',
            'name' => 'Admin Persuratan Test',
            'phone' => '0812'.rand(10000000, 99999999),
        ]);
    }

    private function createCompletedLetter(User $user, array $overrides = []): Letter
    {
        return Letter::create(array_merge([
            'user_id' => $user->id,
            'letter_code' => 'SRT-20261121-'.strtoupper(substr(md5((string) microtime(true).rand()), 0, 6)),
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '005/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-19',
            'lampiran' => '1 (satu) eksemplar',
            'perihal' => 'Permohonan Surat Rekomendasi Pengurus PC ISNU Surabaya',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Ketua Pimpinan Wilayah ISNU Jawa Timur',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Dengan hormat, bersama ini kami sampaikan permohonan rekomendasi.</p>',
            'status' => Letter::STATUS_SELESAI,
            'secretary_user_id' => $user->id,
            'secretary_name' => 'Dr. Sekretaris',
            'secretary_registration_number' => 'ISNU-00001',
            'secretary_position' => 'Sekretaris',
            'secretary_signed_at' => now(),
            'chairman_user_id' => $user->id,
            'chairman_name' => 'Prof. Ketua',
            'chairman_registration_number' => 'ISNU-00002',
            'chairman_position' => 'Ketua',
            'chairman_signed_at' => now(),
        ], $overrides));
    }

    private function makeSimplePdfBytes(string $title, int $pageCount = 1): string
    {
        $pagesHtml = '';
        for ($i = 1; $i <= $pageCount; $i++) {
            $break = $i < $pageCount ? ' style="page-break-after: always;"' : '';
            $pagesHtml .= "<div{$break}><h1>{$title} - Halaman {$i}</h1></div>";
        }

        return Pdf::loadHTML("<!DOCTYPE html><html><body>{$pagesHtml}</body></html>")
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function countPdfPages(string $pdfBytes): int
    {
        if (preg_match('/\/Type\s*\/Pages\b[^>]*\/Count\s+(\d+)/', $pdfBytes, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\/Count\s+(\d+)[^>]*\/Type\s*\/Pages\b/', $pdfBytes, $m)) {
            return (int) $m[1];
        }

        return preg_match_all('/\/Type\s*\/Page\b(?!s)/', $pdfBytes);
    }

    public function test_preview_modal_hides_attachment_filenames_and_exposes_download_options(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $letter = $this->createCompletedLetter($admin);

        $attachmentBytes = $this->makeSimplePdfBytes('Lampiran Rekomendasi', 1);
        Storage::disk('public')->put('letters/attachments/rekom_pcnu.pdf', $attachmentBytes);

        $letter->attachments()->create([
            'file_path' => 'letters/attachments/rekom_pcnu.pdf',
            'file_name' => 'surat 926 rekomendasi PC ISNU 2026-2030.pdf',
            'file_size' => strlen($attachmentBytes),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.letters.preview', $letter->id));

        $response->assertOk();
        $response->assertSee('1 (satu) eksemplar');
        $response->assertDontSee('surat 926 rekomendasi PC ISNU 2026-2030.pdf');
        $response->assertSee('handleLetterPdfDownload', false);
        $response->assertSee('attachmentsCount: 1', false);
        $response->assertSee('include_attachments=1', false);
    }

    public function test_letters_index_passes_attachment_counts_to_download_handler(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $letterWithAttachment = $this->createCompletedLetter($admin, [
            'letter_number' => '010/PC-VI/ISNU-SBY/XI/2026',
        ]);
        $letterWithoutAttachment = $this->createCompletedLetter($admin, [
            'letter_number' => '011/PC-VI/ISNU-SBY/XI/2026',
            'lampiran' => '-',
        ]);

        $attachmentBytes = $this->makeSimplePdfBytes('Lampiran Index', 1);
        Storage::disk('public')->put('letters/attachments/index_att.pdf', $attachmentBytes);
        $letterWithAttachment->attachments()->create([
            'file_path' => 'letters/attachments/index_att.pdf',
            'file_name' => 'lampiran_index.pdf',
            'file_size' => strlen($attachmentBytes),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.letters.index'));

        $response->assertOk();
        $response->assertSee('id="letterPdfDownloadModal"', false);
        $response->assertSee('attachmentsCount: 1', false);
        $response->assertSee('attachmentsCount: 0', false);
    }

    public function test_admin_can_download_letter_pdf_only_or_merged_with_attachments(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $letter = $this->createCompletedLetter($admin);

        $att1Bytes = $this->makeSimplePdfBytes('Lampiran Pertama', 2);
        $att2Bytes = $this->makeSimplePdfBytes('Lampiran Kedua', 1);
        Storage::disk('public')->put('letters/attachments/att1.pdf', $att1Bytes);
        Storage::disk('public')->put('letters/attachments/att2.pdf', $att2Bytes);

        $letter->attachments()->create([
            'file_path' => 'letters/attachments/att1.pdf',
            'file_name' => 'lampiran_1.pdf',
            'file_size' => strlen($att1Bytes),
        ]);
        $letter->attachments()->create([
            'file_path' => 'letters/attachments/att2.pdf',
            'file_name' => 'lampiran_2.pdf',
            'file_size' => strlen($att2Bytes),
        ]);

        $expectedSlug = Str::slug($letter->letter_number, '_');

        // 1. Unduh surat saja
        $onlyRes = $this->actingAs($admin)->get(route('admin.letters.download-pdf', $letter->id));
        $onlyRes->assertOk();
        $this->assertStringContainsString("Surat_{$expectedSlug}.pdf", (string) $onlyRes->headers->get('Content-Disposition'));
        $onlyPdf = file_get_contents($onlyRes->getFile()->getPathname());
        $this->assertStringStartsWith('%PDF-', (string) $onlyPdf);
        $letterPageCount = $this->countPdfPages((string) $onlyPdf);
        $this->assertGreaterThanOrEqual(1, $letterPageCount);

        // 2. Unduh surat + lampiran dalam satu file PDF
        $mergedRes = $this->actingAs($admin)->get(route('admin.letters.download-pdf', [
            'id' => $letter->id,
            'include_attachments' => 1,
        ]));
        $mergedRes->assertOk();
        $this->assertStringContainsString("Surat_{$expectedSlug}_dan_Lampiran.pdf", (string) $mergedRes->headers->get('Content-Disposition'));
        $mergedPdf = file_get_contents($mergedRes->getFile()->getPathname());
        $this->assertStringStartsWith('%PDF-', (string) $mergedPdf);
        $this->assertSame($letterPageCount + 3, $this->countPdfPages((string) $mergedPdf));
    }

    public function test_public_verification_can_download_merged_pdf_with_attachments(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $letter = $this->createCompletedLetter($admin);

        $attBytes = $this->makeSimplePdfBytes('Lampiran Publik', 2);
        Storage::disk('public')->put('letters/attachments/publik.pdf', $attBytes);
        $letter->attachments()->create([
            'file_path' => 'letters/attachments/publik.pdf',
            'file_name' => 'lampiran_publik.pdf',
            'file_size' => strlen($attBytes),
        ]);

        $verifyPage = $this->get(route('surat.verifikasi', $letter->letter_code));
        $verifyPage->assertOk();
        $verifyPage->assertSee('id="letterPdfDownloadModal"', false);
        $verifyPage->assertSee('attachmentsCount: 1', false);

        $onlyRes = $this->get(route('surat.download-pdf', $letter->letter_code));
        $onlyRes->assertOk();
        $onlyPdf = file_get_contents($onlyRes->getFile()->getPathname());
        $letterPageCount = $this->countPdfPages((string) $onlyPdf);

        $mergedRes = $this->get(route('surat.download-pdf', [
            'code' => $letter->letter_code,
            'include_attachments' => 1,
        ]));
        $mergedRes->assertOk();
        $this->assertStringContainsString('_dan_Lampiran.pdf', (string) $mergedRes->headers->get('Content-Disposition'));
        $mergedPdf = file_get_contents($mergedRes->getFile()->getPathname());
        $this->assertSame($letterPageCount + 2, $this->countPdfPages((string) $mergedPdf));
    }

    public function test_incomplete_letter_cannot_download_merged_pdf(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $draftLetter = $this->createCompletedLetter($admin, [
            'status' => Letter::STATUS_DRAFT,
            'secretary_signed_at' => null,
            'chairman_signed_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.letters.download-pdf', [
            'id' => $draftLetter->id,
            'include_attachments' => 1,
        ]));

        $response->assertForbidden();
    }

    public function test_merged_download_falls_back_gracefully_when_attachment_file_is_missing(): void
    {
        Storage::fake('public');

        $admin = $this->createAdminUser();
        $letter = $this->createCompletedLetter($admin);

        $letter->attachments()->create([
            'file_path' => 'letters/attachments/missing_file.pdf',
            'file_name' => 'missing_file.pdf',
            'file_size' => 1024,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.letters.download-pdf', [
            'id' => $letter->id,
            'include_attachments' => 1,
        ]));

        $expectedSlug = Str::slug($letter->letter_number, '_');
        $response->assertOk();
        $this->assertStringContainsString("Surat_{$expectedSlug}.pdf", (string) $response->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('_dan_Lampiran.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_pure_php_pdf_merger_combines_multiple_pdfs_accurately(): void
    {
        $tmpDir = storage_path('app/private/tmp/test_pdf_merge_'.uniqid());
        mkdir($tmpDir, 0755, true);

        try {
            $pdf1 = $tmpDir.'/doc1.pdf';
            $pdf2 = $tmpDir.'/doc2.pdf';
            $out = $tmpDir.'/merged.pdf';

            file_put_contents($pdf1, $this->makeSimplePdfBytes('Surat Utama', 1));
            file_put_contents($pdf2, $this->makeSimplePdfBytes('Berkas Lampiran', 2));

            $merger = app(PdfMergerService::class);
            $merger->mergeWithPurePhp([$pdf1, $pdf2], $out);

            $this->assertFileExists($out);
            $mergedBytes = (string) file_get_contents($out);
            $this->assertStringStartsWith('%PDF-', $mergedBytes);
            $this->assertSame(3, $this->countPdfPages($mergedBytes));
        } finally {
            foreach (glob($tmpDir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }
}
