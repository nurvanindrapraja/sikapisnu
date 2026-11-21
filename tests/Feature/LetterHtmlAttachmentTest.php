<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterHtmlAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $name, string $role = 'member', ?string $positionTitle = null): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => $role,
            'is_active' => true,
        ]);

        if ($positionTitle !== null) {
            $member = Member::create([
                'user_id' => $user->id,
                'member_number' => 'ISNU-SBY-26-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                'nik' => '357801010190'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'email' => $user->email,
                'phone' => '08123456'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'occupation' => 'Akademisi',
                'birth_place' => 'Surabaya',
                'birth_date' => '1990-01-01',
                'gender' => 'L',
                'address' => 'Jl. Bubutan VI/2',
                'kelurahan' => 'Bubutan',
                'kecamatan' => 'Bubutan',
                'membership_status' => 'pengurus',
            ]);

            Position::create([
                'member_id' => $member->id,
                'position_title' => $positionTitle,
                'level' => 'PC ISNU',
                'period' => '2026-2030',
                'is_active' => true,
            ]);
        }

        return $user->fresh();
    }

    public function test_can_create_letter_with_multiple_html_attachments_and_optional_pdf(): void
    {
        Storage::fake('public');
        $admin = $this->createOfficer('Admin Surat', 'super_admin');
        $this->actingAs($admin);

        // Create page displays HTML attachment section
        $this->get(route('admin.letters.create'))
            ->assertStatus(200)
            ->assertSee('Lampiran Teks HTML')
            ->assertSee('Tambah Lampiran Teks');

        $pdfFile = UploadedFile::fake()->create('lampiran_resmi.pdf', 100, 'application/pdf');

        $response = $this->post(route('admin.letters.store'), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '010/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'lampiran' => '2 (Dua) Lembar',
            'perihal' => 'Undangan Rapat Koordinasi',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Daftar Terlampir',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Dengan hormat, kami mengundang Bapak/Ibu pada rapat koordinasi.</p>',
            'attachments' => [$pdfFile],
            'html_attachments' => [
                [
                    'title' => 'Daftar Tujuan Undangan',
                    'content' => '<ol><li>Ketua PAC ISNU Gayungan</li><li>Ketua PAC ISNU Rungkut</li></ol>',
                ],
                [
                    'title' => '',
                    'content' => '<p><br></p>', // Empty card should be ignored
                ],
                [
                    'title' => 'Susunan Acara',
                    'content' => '<table class="table table-bordered" border="1"><tr><td>08.00</td><td>Pembukaan</td></tr></table>',
                ],
            ],
            'action' => 'draft',
        ]);

        $letter = Letter::with(['attachments', 'htmlAttachments'])->firstOrFail();
        $response->assertRedirect(route('admin.letters.show', $letter->id));

        $this->assertCount(1, $letter->attachments);
        $this->assertCount(2, $letter->htmlAttachments);
        $this->assertEquals('Daftar Tujuan Undangan', $letter->htmlAttachments[0]->title);
        $this->assertStringContainsString('Ketua PAC ISNU Gayungan', $letter->htmlAttachments[0]->content);
        $this->assertEquals(1, $letter->htmlAttachments[0]->sort_order);
        $this->assertEquals('Susunan Acara', $letter->htmlAttachments[1]->title);
        $this->assertEquals(2, $letter->htmlAttachments[1]->sort_order);

        // Generated PDF exists
        $this->assertNotNull($letter->pdf_path);
        Storage::disk('public')->assertExists($letter->pdf_path);

        // Detail page & Preview modal & Public verification show HTML attachments
        $this->get(route('admin.letters.show', $letter->id))
            ->assertStatus(200)
            ->assertSee('Daftar Tujuan Undangan')
            ->assertSee('Ketua PAC ISNU Gayungan')
            ->assertSee('Susunan Acara');

        $this->get(route('admin.letters.preview', $letter->id))
            ->assertStatus(200)
            ->assertSee('Daftar Tujuan Undangan')
            ->assertSee('Susunan Acara');

        $this->get(route('surat.verifikasi', $letter->letter_code))
            ->assertStatus(200)
            ->assertSee('Daftar Tujuan Undangan')
            ->assertSee('Susunan Acara');
    }

    public function test_html_attachments_are_optional_and_can_be_updated_and_removed(): void
    {
        Storage::fake('public');
        $admin = $this->createOfficer('Admin Edit Surat', 'super_admin');
        $this->actingAs($admin);

        // 1. Create without any HTML attachments
        $this->post(route('admin.letters.store'), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '011/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'perihal' => 'Surat Tanpa Lampiran HTML',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Ketua PW ISNU Jatim',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Isi surat biasa.</p>',
            'action' => 'draft',
        ])->assertRedirect();

        $letter = Letter::with('htmlAttachments')->firstOrFail();
        $this->assertCount(0, $letter->htmlAttachments);

        // 2. Edit to add HTML attachment
        $this->get(route('admin.letters.edit', $letter->id))
            ->assertStatus(200)
            ->assertSee('Lampiran Teks HTML');

        $this->put(route('admin.letters.update', $letter->id), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '011/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'perihal' => 'Surat Dengan Lampiran HTML Baru',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Daftar Terlampir',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Isi surat diperbarui.</p>',
            'html_attachments' => [
                [
                    'title' => 'Daftar Penerima Surat',
                    'content' => '<p>1. Ketua PAC Sukolilo<br>2. Ketua PAC Mulyorejo</p>',
                ],
            ],
            'action' => 'draft',
        ])->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh()->load('htmlAttachments');
        $this->assertCount(1, $letter->htmlAttachments);
        $this->assertEquals('Daftar Penerima Surat', $letter->htmlAttachments[0]->title);

        // 3. Edit to remove all HTML attachments
        $this->put(route('admin.letters.update', $letter->id), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '011/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'perihal' => 'Surat Tanpa Lampiran Lagi',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Ketua PW ISNU Jatim',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Isi surat tanpa lampiran.</p>',
            'action' => 'draft',
        ])->assertRedirect(route('admin.letters.show', $letter->id));

        $this->assertCount(0, $letter->fresh()->htmlAttachments);
    }

    public function test_preview_draft_renders_unsaved_html_attachments(): void
    {
        $admin = $this->createOfficer('Admin Preview', 'super_admin');
        $this->actingAs($admin);

        $htmlPreview = $this->post(route('admin.letters.preview-draft'), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '012/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'perihal' => 'Undangan Akbar',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Daftar Terlampir',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Mohon kehadiran Bapak/Ibu.</p>',
            'html_attachments' => [
                [
                    'title' => 'Daftar Undangan PAC',
                    'content' => '<p>PAC ISNU Wonokromo &amp; PAC ISNU Gubeng</p>',
                ],
            ],
        ]);

        $htmlPreview->assertStatus(200);
        $htmlPreview->assertSee('Daftar Undangan PAC');
        $htmlPreview->assertSee('PAC ISNU Wonokromo');

        $pdfPreview = $this->post(route('admin.letters.preview-draft', ['format' => 'pdf']), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '012/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-22',
            'perihal' => 'Undangan Akbar',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Daftar Terlampir',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Mohon kehadiran Bapak/Ibu.</p>',
            'html_attachments' => [
                [
                    'title' => 'Daftar Undangan PAC',
                    'content' => '<p>PAC ISNU Wonokromo</p>',
                ],
            ],
        ]);

        $pdfPreview->assertStatus(200);
        $pdfPreview->assertHeader('Content-Type', 'application/pdf');
    }
}
