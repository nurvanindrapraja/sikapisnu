<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterSignatureAndStampFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createMember(User $user, string $name, string $status = 'terverifikasi'): Member
    {
        return Member::create([
            'user_id' => $user->id,
            'member_number' => 'ISNU-SBY-26-'.rand(100000, 999999),
            'nik' => '3578'.rand(100000000000, 999999999999),
            'full_name' => $name,
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'phone' => $user->phone ?: ('08'.rand(1000000000, 9999999999)),
            'email' => $user->email,
            'address' => 'Jl. Darmo No. 10',
            'kelurahan' => 'Darmo',
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
            'occupation' => 'Dosen',
            'membership_status' => $status,
        ]);
    }

    public function test_user_can_upload_signature_in_admin_profile(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'admin_kota',
            'name' => 'Admin Pengurus Khusus',
            'phone' => '0898'.rand(10000000, 99999999),
        ]);

        $signatureFile = UploadedFile::fake()->image('my_signature.png', 300, 150);
        $stampFile = UploadedFile::fake()->image('official_stamp_isnu.png', 200, 200);

        $response = $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'Admin Pengurus Khusus Updated',
            'email' => $user->email,
            'phone' => $user->phone,
            'signature' => $signatureFile,
            'stamp' => $stampFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->signature_path);
        $this->assertTrue(Storage::disk('public')->exists($user->signature_path));
        $this->assertTrue($user->hasSignature());
        $this->assertNotNull($user->signature_url);

        $this->assertNotNull($user->stamp_path);
        $this->assertTrue(Storage::disk('public')->exists($user->stamp_path));
        $this->assertTrue(Storage::disk('public')->exists('stamps/official_stamp.png'));
        $this->assertTrue($user->hasStamp());
        $this->assertNotNull($user->stamp_url);
    }

    public function test_member_can_upload_signature_in_member_profile(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Anggota ISNU Berbakat',
            'phone' => '0897'.rand(10000000, 99999999),
        ]);
        $member = $this->createMember($user, 'Anggota ISNU Berbakat', 'terverifikasi');

        $signatureFile = UploadedFile::fake()->image('member_signature.png', 300, 150);

        $response = $this->actingAs($user)->post(route('member.profile.update'), [
            'full_name' => 'Anggota ISNU Berbakat Updated',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Darmo No. 10',
            'kelurahan' => 'Darmo',
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'occupation' => 'Dosen',
            'signature' => $signatureFile,
        ]);

        $response->assertRedirect();
        $user->refresh();
        $member->refresh();

        $this->assertNotNull($user->signature_path);
        $this->assertNotNull($member->signature_path);
        $this->assertTrue(Storage::disk('public')->exists($user->signature_path));
    }

    public function test_secretary_and_chairman_can_sign_with_uploaded_signature_and_stamped_preview(): void
    {
        Storage::fake('public');

        // Setup Sekretaris PC
        $secUser = User::factory()->create([
            'name' => 'Dr. Sekretaris PC',
            'role' => 'anggota',
            'phone' => '0896'.rand(10000000, 99999999),
        ]);
        $secMember = $this->createMember($secUser, 'Dr. Sekretaris PC', 'pengurus');
        Position::create([
            'member_id' => $secMember->id,
            'position_title' => 'Sekretaris',
            'start_period' => 2024,
            'end_period' => 2028,
            'is_active' => true,
        ]);

        // Setup Ketua PC
        $chairUser = User::factory()->create([
            'name' => 'Prof. Ketua PC',
            'role' => 'anggota',
            'phone' => '0895'.rand(10000000, 99999999),
        ]);
        $chairMember = $this->createMember($chairUser, 'Prof. Ketua PC', 'pengurus');
        Position::create([
            'member_id' => $chairMember->id,
            'position_title' => 'Ketua',
            'start_period' => 2024,
            'end_period' => 2028,
            'is_active' => true,
        ]);

        // Buat surat
        $letter = Letter::create([
            'user_id' => $secUser->id,
            'letter_code' => 'SRT-TEST-SIGN01',
            'letter_number' => '001/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'perihal' => 'Undangan Rapat Pleno',
            'tujuan_surat' => 'Pengurus PC ISNU',
            'isi_surat' => '<p>Mohon kehadiran pengurus sekalian.</p>',
            'status' => Letter::STATUS_MENUNGGU_SEKRETARIS,
        ]);

        // 1. Sekretaris menandatangani dengan mengunggah gambar TTD
        $secSigFile = UploadedFile::fake()->image('ttd_sekretaris.png', 320, 140);
        $resSec = $this->actingAs($secUser)->post(route('admin.letters.sign-secretary', $letter->id), [
            'signature_image' => $secSigFile,
        ]);
        $resSec->assertRedirect();
        $letter->refresh();

        $this->assertEquals(Letter::STATUS_MENUNGGU_KETUA, $letter->status);
        $this->assertNotNull($letter->secretary_signature_path);
        $this->assertTrue(Storage::disk('public')->exists($letter->secretary_signature_path));
        $this->assertNotNull($letter->stamp_path);
        $this->assertNotNull($letter->verification_qr_path);

        // 2. Ketua menandatangani dengan mengunggah gambar TTD
        $chairSigFile = UploadedFile::fake()->image('ttd_ketua.png', 320, 140);
        $resChair = $this->actingAs($chairUser)->post(route('admin.letters.sign-chairman', $letter->id), [
            'signature_image' => $chairSigFile,
        ]);
        $resChair->assertRedirect();
        $letter->refresh();

        $this->assertEquals(Letter::STATUS_SELESAI, $letter->status);
        $this->assertNotNull($letter->chairman_signature_path);
        $this->assertTrue(Storage::disk('public')->exists($letter->chairman_signature_path));

        // 3. Verifikasi halaman show menampilkan gambar TTD, stempel di atas TTD Sekretaris, dan QR verifikasi di atas footer
        $showRes = $this->actingAs($chairUser)->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);

        // Menampilkan gambar TTD Ketua & Sekretaris (bukan sekedar QR code)
        $showRes->assertSee('alt="TTD Ketua"', false);
        $showRes->assertSee('alt="TTD Sekretaris"', false);

        // Menampilkan stempel resmi yang diletakkan di atas TTD Sekretaris
        $showRes->assertSee('alt="Stempel Resmi"', false);

        // Menampilkan QRCode verifikasi di bawah halaman, di atas footer
        $showRes->assertSee('alt="QR Verifikasi Dokumen"', false);
        $showRes->assertSee('Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode ini untuk verifikasi otentisitas dokumen. Kode Surat: '.$letter->letter_code);

        // 4. Verifikasi preview modal AJAX
        $previewRes = $this->actingAs($secUser)->get(route('admin.letters.preview', $letter->id), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $previewRes->assertStatus(200);
        $previewRes->assertSee('alt="TTD Ketua"', false);
        $previewRes->assertSee('alt="TTD Sekretaris"', false);
        $previewRes->assertSee('alt="Stempel Resmi"', false);
        $previewRes->assertSee('alt="QR Verifikasi Dokumen"', false);
        $previewRes->assertSee('Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode ini untuk verifikasi otentisitas dokumen. Kode Surat: '.$letter->letter_code);

        // 5. Verifikasi PDF memuat gambar TTD, stempel, dan QR verifikasi
        $letterService = app(LetterService::class);
        $pdfPath = $letterService->generatePdf($letter);
        $this->assertTrue(Storage::disk('public')->exists($pdfPath));

        $renderedPdf = view('admin.letters.pdf', [
            'letter' => $letter,
            'secSigBase64' => base64_encode(Storage::disk('public')->get($letter->secretary_signature_path)),
            'chairSigBase64' => base64_encode(Storage::disk('public')->get($letter->chairman_signature_path)),
            'stampBase64' => base64_encode(Storage::disk('public')->get($letter->stamp_path)),
            'verificationQrBase64' => base64_encode(Storage::disk('public')->get($letter->verification_qr_path)),
            'secQrBase64' => null,
            'chairQrBase64' => null,
            'kopLogoBase64' => null,
        ])->render();

        $this->assertStringContainsString('class="sig-img" alt="TTD Ketua"', $renderedPdf);
        $this->assertStringContainsString('class="sig-img" alt="TTD Sekretaris"', $renderedPdf);
        $this->assertStringContainsString('class="stamp-img" alt="Stempel Resmi"', $renderedPdf);
        $this->assertStringContainsString('alt="QR Verifikasi"', $renderedPdf);
        $this->assertStringContainsString('Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode ini untuk verifikasi otentisitas dokumen. Kode Surat: '.$letter->letter_code, $renderedPdf);
    }

    public function test_user_can_preview_draft_letter_modal(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0894'.rand(10000000, 99999999),
        ]);

        $postData = [
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '009/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'kota_asal' => 'Surabaya',
            'date_letter' => '2026-11-15',
            'lampiran' => '1 Berkas',
            'perihal' => 'Pratinjau Undangan Rapat Evaluasi',
            'tujuan_surat' => 'Seluruh Pengurus Harian',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Ini adalah isi surat untuk pengujian pratinjau utuh.</p>',
        ];

        $response = $this->actingAs($user)->post(route('admin.letters.preview-draft'), $postData, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Pratinjau Dokumen Utuh');
        $response->assertSee('009/PC-VI/ISNU-SBY/XI/2026');
        $response->assertSee('Pratinjau Undangan Rapat Evaluasi');
        $response->assertSee('Ini adalah isi surat untuk pengujian pratinjau utuh.');
        $response->assertSee('IKATAN SARJANA NAHDLATUL');
        $response->assertSee('ULAMA KOTA SURABAYA');
        $response->assertSee('Belum TTD Ketua');
        $response->assertSee('Belum TTD Sekretaris');
        $response->assertSee('Tutup & Lanjutkan Menyunting', false);
    }

    public function test_verification_page_does_not_have_header_with_isnu_logo(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0893'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-'.date('Ymd').'-TESTVERIF',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '010/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'lampiran' => '1 Berkas',
            'perihal' => 'Pengujian Halaman Verifikasi Tanpa Header Logo ISNU',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Pihak Terkait',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Uji verifikasi keaslian surat tanpa kop logo di verifikasi publik.</p>',
            'status' => 'draft',
        ]);

        $response = $this->get(route('surat.verifikasi', $letter->letter_code));
        $response->assertStatus(200);
        $response->assertSee('Informasi Dokumen Surat');
        $response->assertSee('010/PC-VI/ISNU-SBY/XI/2026');
        $response->assertDontSee('images/kop_logo_isnu.jpeg');
    }

    public function test_user_can_upload_and_manage_multiple_pdf_attachments_in_edit_letter(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0892'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-'.date('Ymd').'-TESTATT',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '011/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'lampiran' => '2 Berkas',
            'perihal' => 'Pengujian Upload Lampiran PDF Lebih Dari Satu',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Pengurus dan Anggota',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Uji multi-upload lampiran file PDF.</p>',
            'status' => 'draft',
        ]);

        $pdf1 = UploadedFile::fake()->create('Susunan_Acara.pdf', 250, 'application/pdf');
        $pdf2 = UploadedFile::fake()->create('RAB_Kegiatan.pdf', 350, 'application/pdf');

        $updateData = [
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => $letter->letter_number,
            'roman_month' => $letter->roman_month,
            'year' => $letter->year,
            'date_letter' => $letter->date_letter->format('Y-m-d'),
            'lampiran' => '2 Berkas',
            'perihal' => $letter->perihal,
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => $letter->tujuan_surat,
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => $letter->isi_surat,
            'action' => 'draft',
            'attachments' => [$pdf1, $pdf2],
        ];

        $response = $this->actingAs($user)->put(route('admin.letters.update', $letter->id), $updateData);
        $response->assertRedirect(route('admin.letters.show', $letter->id));
        $response->assertSessionHas('success');

        $letter->refresh();
        $this->assertCount(2, $letter->attachments);

        $attachment1 = $letter->attachments->firstWhere('file_name', 'Susunan_Acara.pdf');
        $attachment2 = $letter->attachments->firstWhere('file_name', 'RAB_Kegiatan.pdf');

        $this->assertNotNull($attachment1);
        $this->assertNotNull($attachment2);
        Storage::disk('public')->assertExists($attachment1->file_path);
        Storage::disk('public')->assertExists($attachment2->file_path);

        // Halaman Detail Surat menampilkan daftar lampiran dan modal pratinjau
        $showRes = $this->actingAs($user)->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Susunan_Acara.pdf');
        $showRes->assertSee('RAB_Kegiatan.pdf');
        $showRes->assertSee('attachmentPreviewModal');
        $showRes->assertSee('openAttachmentPreviewModal');

        // Halaman Verifikasi Publik menampilkan lampiran dan modal pratinjau
        $verifRes = $this->get(route('surat.verifikasi', $letter->letter_code));
        $verifRes->assertStatus(200);
        $verifRes->assertSee('Susunan_Acara.pdf');
        $verifRes->assertSee('RAB_Kegiatan.pdf');
        $verifRes->assertSee('attachmentPreviewModal');
        $verifRes->assertSee('openAttachmentPreviewModal');

        // Pratinjau lampiran PDF secara inline di browser
        $adminPrevRes = $this->actingAs($user)->get(route('admin.letters.attachments.preview', $attachment1->id));
        $adminPrevRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $adminPrevRes->headers->get('content-type'));

        $publicPrevRes = $this->get(route('surat.preview-attachment', ['code' => $letter->letter_code, 'attachment_id' => $attachment2->id]));
        $publicPrevRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $publicPrevRes->headers->get('content-type'));

        // Unduh lampiran dari rute admin dan rute verifikasi publik
        $this->actingAs($user)->get(route('admin.letters.attachments.download', $attachment1->id))->assertStatus(200);
        $this->get(route('surat.download-attachment', ['code' => $letter->letter_code, 'attachment_id' => $attachment2->id]))->assertStatus(200);

        // Hapus salah satu lampiran saat edit berikutnya
        $updateData2 = [
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => $letter->letter_number,
            'roman_month' => $letter->roman_month,
            'year' => $letter->year,
            'date_letter' => $letter->date_letter->format('Y-m-d'),
            'lampiran' => '1 Berkas',
            'perihal' => $letter->perihal,
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => $letter->tujuan_surat,
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => $letter->isi_surat,
            'action' => 'draft',
            'delete_attachment_ids' => [$attachment1->id],
        ];

        $response2 = $this->actingAs($user)->put(route('admin.letters.update', $letter->id), $updateData2);
        $response2->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh();
        $this->assertCount(1, $letter->attachments);
        Storage::disk('public')->assertMissing($attachment1->file_path);
        Storage::disk('public')->assertExists($attachment2->file_path);

        // Hapus lampiran kedua via endpoint destroyAttachment
        $delRes = $this->actingAs($user)->delete(route('admin.letters.attachments.destroy', $attachment2->id));
        $delRes->assertRedirect();
        Storage::disk('public')->assertMissing($attachment2->file_path);
        $this->assertCount(0, $letter->fresh()->attachments);
    }

    public function test_attachment_upload_rejects_non_pdf_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0891'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-'.date('Ymd').'-TESTTXT',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '012/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'lampiran' => '-',
            'perihal' => 'Pengujian Validasi Format PDF',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Target',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Uji coba validasi file non-pdf.</p>',
            'status' => 'draft',
        ]);

        $nonPdf = UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain');

        $response = $this->actingAs($user)->put(route('admin.letters.update', $letter->id), [
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => $letter->letter_number,
            'roman_month' => $letter->roman_month,
            'year' => $letter->year,
            'date_letter' => $letter->date_letter->format('Y-m-d'),
            'lampiran' => '-',
            'perihal' => $letter->perihal,
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => $letter->tujuan_surat,
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => $letter->isi_surat,
            'action' => 'draft',
            'attachments' => [$nonPdf],
        ]);

        $response->assertSessionHasErrors(['attachments.0']);
    }

    public function test_edit_letter_renders_editor_and_desktop_sidebar_correctly(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0890'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-'.date('Ymd').'-TESTEDITVIEW',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '013/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'lampiran' => '1 Berkas',
            'perihal' => 'Uji Editor dan Sidebar Desktop',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Penerima Surat',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Paragraf isi surat yang harus tampil di editor.</p>',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('admin.letters.edit', $letter->id));
        $response->assertStatus(200);
        $response->assertSee('id="quill-editor"', false);
        $response->assertSee('Teks Isi Surat (Editor HTML)');
        $response->assertSee('Berkas Lampiran Surat (PDF)');
        $response->assertSee('Informasi Status Surat');
        $response->assertSee('col-lg-8');
        $response->assertSee('col-lg-4');
        $response->assertSee('sticky-lg-top');
        $response->assertSee('attachmentPreviewModal');
        $response->assertSee('openAttachmentPreviewModal');
    }

    public function test_user_can_preview_draft_letter_modal_with_put_method_and_empty_body(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0889'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-'.date('Ymd').'-TESTPREVPUT',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => '014/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'perihal' => 'Pengujian Pratinjau Edit Draft',
            'isi_surat' => '<p>Draf awal</p>',
            'status' => 'draft',
        ]);

        $postDataWithPut = [
            '_method' => 'PUT',
            'letter_id' => $letter->id,
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'letter_number' => $letter->letter_number,
            'roman_month' => $letter->roman_month,
            'year' => $letter->year,
            'date_letter' => '2026-11-15',
            'perihal' => '',
            'tujuan_surat' => '',
            'isi_surat' => null,
        ];

        // Test with POST containing _method = PUT (method spoofing)
        $response = $this->actingAs($user)->post(route('admin.letters.preview-draft'), $postDataWithPut, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Pratinjau Dokumen Utuh');
        $response->assertSee('014/PC-VI/ISNU-SBY/XI/2026');

        // Test directly with PUT method
        $putResponse = $this->actingAs($user)->put(route('admin.letters.preview-draft'), $postDataWithPut, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $putResponse->assertStatus(200);
        $putResponse->assertSee('Pratinjau Dokumen Utuh');
    }

    public function test_preview_draft_returns_pdf_stream_when_format_is_pdf(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0895'.rand(10000000, 99999999),
        ]);

        $letter = Letter::create([
            'user_id' => $user->id,
            'letter_code' => 'SRT-20261117-TEST01',
            'letter_number' => '015/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => 2026,
            'date_letter' => '2026-11-17',
            'letter_type' => 'surat_biasa',
            'level' => 'PC ISNU',
            'perihal' => 'Pengujian Pratinjau PDF',
            'tujuan_surat' => 'Penerima Uji Coba',
            'isi_surat' => '<p>Paragraf uji coba PDF preview.</p>',
            'status' => 'draft',
        ]);

        $postData = [
            'id' => $letter->id,
            'letter_type' => 'surat_biasa',
            'letter_number' => $letter->letter_number,
            'perihal' => 'Pengujian Pratinjau PDF',
            'isi_surat' => '<p>Paragraf uji coba PDF preview.</p>',
            'format' => 'pdf',
        ];

        $response = $this->actingAs($user)->post(route('admin.letters.preview-draft'), $postData, [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/pdf',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_preview_draft_generates_multi_page_pdf_for_long_content(): void
    {
        $user = User::factory()->create([
            'role' => 'admin_kota',
            'phone' => '0896'.rand(10000000, 99999999),
        ]);

        $longContent = str_repeat('<p>Ini adalah teks panjang isi surat resmi Ikatan Sarjana Nahdlatul Ulama Kota Surabaya untuk memastikan surat dapat ter-render rapi lebih dari satu halaman.</p>', 25);

        $postData = [
            'letter_type' => 'surat_biasa',
            'letter_number' => '016/PC-VI/ISNU-SBY/XI/2026',
            'perihal' => 'Surat Resmi Multi Halaman',
            'isi_surat' => $longContent,
            'format' => 'pdf',
        ];

        $response = $this->actingAs($user)->post(route('admin.letters.preview-draft').'?format=pdf', $postData, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);

        // Verify PDF has multiple pages
        preg_match_all('/\/Page\b/', $content, $matches);
        $this->assertGreaterThan(1, count($matches[0]));
    }
}
