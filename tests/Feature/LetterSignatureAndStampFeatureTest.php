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
}
