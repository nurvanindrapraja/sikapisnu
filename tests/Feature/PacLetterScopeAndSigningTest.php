<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PacLetterScopeAndSigningTest extends TestCase
{
    use RefreshDatabase;

    private Pac $pacWonokromo;

    private Pac $pacSukolilo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $mwc = Mwc::first() ?? Mwc::create([
            'name' => 'MWC NU Surabaya',
            'code' => 'MWC-SBY',
            'city' => 'Kota Surabaya',
        ]);

        $this->pacWonokromo = Pac::firstOrCreate(
            ['code' => 'PAC-WNK'],
            ['mwc_id' => $mwc->id, 'name' => 'PAC ISNU Wonokromo', 'kecamatan' => 'Wonokromo', 'city' => 'Kota Surabaya', 'province' => 'Jawa Timur']
        );

        $this->pacSukolilo = Pac::firstOrCreate(
            ['code' => 'PAC-SKL'],
            ['mwc_id' => $mwc->id, 'name' => 'PAC ISNU Sukolilo', 'kecamatan' => 'Sukolilo', 'city' => 'Kota Surabaya', 'province' => 'Jawa Timur']
        );
    }

    private function createOfficer(string $name, string $positionTitle, string $level, ?Pac $pac = null): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'member',
            'is_active' => true,
            'phone' => '0813'.fake()->unique()->numerify('########'),
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'member_number' => 'ISNU-'.fake()->unique()->numerify('######'),
            'nik' => fake()->unique()->numerify('3578############'),
            'full_name' => $name,
            'birth_place' => 'Kota Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Bubutan No. 1',
            'kelurahan' => $pac?->kecamatan ?? 'Bubutan',
            'kecamatan' => $pac?->kecamatan ?? 'Bubutan',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
            'occupation' => 'Dosen',
            'phone' => $user->phone,
            'email' => $user->email,
            'membership_status' => 'pengurus',
            'pac_id' => $pac?->id,
        ]);

        Position::create([
            'member_id' => $member->id,
            'position_title' => $positionTitle,
            'level' => $level,
            'pac_id' => $pac?->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        return $user->fresh(['member.activePosition']);
    }

    private function createLetter(User $author, string $level, ?Pac $pac, string $number, string $perihal, string $status = Letter::STATUS_SELESAI): Letter
    {
        return Letter::create([
            'user_id' => $author->id,
            'letter_code' => 'SRT-'.strtoupper(fake()->unique()->bothify('######??')),
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => $level,
            'pac_id' => $level === Letter::LEVEL_PAC ? $pac?->id : null,
            'letter_number' => $number,
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-20',
            'lampiran' => '-',
            'perihal' => $perihal,
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Penerima Surat',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Isi surat pengujian.</p>',
            'status' => $status,
        ]);
    }

    public function test_pac_officer_only_sees_and_accesses_letters_of_their_own_pac(): void
    {
        $adminKota = User::factory()->create(['role' => 'admin_kota', 'phone' => '081100001111']);
        $koordinatorWonokromo = $this->createOfficer('Koordinator PAC Wonokromo', 'Koordinator Seksi', 'PAC ISNU', $this->pacWonokromo);
        $ketuaSukolilo = $this->createOfficer('Ketua PAC Sukolilo', 'Ketua', 'PAC ISNU', $this->pacSukolilo);

        $pcLetter = $this->createLetter($adminKota, Letter::LEVEL_PC, null, '001/PC-VI/ISNU-SBY/XI/2026', 'Surat Resmi PC ISNU');
        $wonokromoLetter = $this->createLetter($koordinatorWonokromo, Letter::LEVEL_PAC, $this->pacWonokromo, '001/PAC/ISNU-WONOKROMO/XI/2026', 'Surat Kegiatan PAC Wonokromo');
        $sukoliloLetter = $this->createLetter($ketuaSukolilo, Letter::LEVEL_PAC, $this->pacSukolilo, '001/PAC/ISNU-SUKOLILO/XI/2026', 'Surat Kegiatan PAC Sukolilo');

        // 1. Index hanya menampilkan surat milik PAC Wonokromo
        $indexRes = $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Surat Kegiatan PAC Wonokromo');
        $indexRes->assertDontSee('Surat Resmi PC ISNU');
        $indexRes->assertDontSee('Surat Kegiatan PAC Sukolilo');

        // 2. Tidak dapat mengakses detail, preview, atau unduh PDF surat PC ISNU maupun PAC lain
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.show', $pcLetter->id))->assertForbidden();
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.preview', $pcLetter->id))->assertForbidden();
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.download-pdf', $pcLetter->id))->assertForbidden();

        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.show', $sukoliloLetter->id))->assertForbidden();
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.preview', $sukoliloLetter->id))->assertForbidden();
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.download-pdf', $sukoliloLetter->id))->assertForbidden();

        // 3. Dapat mengakses surat milik PAC Wonokromo sendiri
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.show', $wonokromoLetter->id))->assertOk();
        $this->actingAs($koordinatorWonokromo)->get(route('admin.letters.preview', $wonokromoLetter->id))->assertOk();

        // 4. Admin Kota tetap melihat seluruh surat
        $adminIndex = $this->actingAs($adminKota)->get(route('admin.letters.index'));
        $adminIndex->assertOk();
        $adminIndex->assertSee('Surat Resmi PC ISNU');
        $adminIndex->assertSee('Surat Kegiatan PAC Wonokromo');
        $adminIndex->assertSee('Surat Kegiatan PAC Sukolilo');
    }

    public function test_pac_officer_letter_creation_and_update_automatically_locks_level_to_their_pac(): void
    {
        Storage::fake('public');

        $anggotaSeksiWonokromo = $this->createOfficer('Anggota Seksi Wonokromo', 'Anggota Seksi', 'PAC ISNU', $this->pacWonokromo);

        // 1. Halaman create mengunci level ke PAC ISNU dan wilayah ke PAC Wonokromo
        $createRes = $this->actingAs($anggotaSeksiWonokromo)->get(route('admin.letters.create'));
        $createRes->assertOk();
        $createRes->assertSee('<input type="hidden" name="level" value="PAC ISNU">', false);
        $createRes->assertSee('<input type="hidden" name="pac_id" value="'.$this->pacWonokromo->id.'">', false);
        $createRes->assertSee('PAC ISNU Wonokromo');
        $createRes->assertDontSee('id="levelPC"', false);
        $createRes->assertDontSee('PAC ISNU Sukolilo');

        // 2. Meski POST dimanipulasi ke PC ISNU / PAC lain, server memaksa level PAC ISNU & pac_id milik user
        $storeRes = $this->actingAs($anggotaSeksiWonokromo)->post(route('admin.letters.store'), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'pac_id' => $this->pacSukolilo->id,
            'letter_number' => '002/PAC/ISNU-WONOKROMO/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-21',
            'lampiran' => '-',
            'perihal' => 'Undangan Konsolidasi PAC Wonokromo',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Pengurus PAC Wonokromo',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Mohon kehadiran rekan-rekan PAC Wonokromo.</p>',
            'action' => 'draft',
        ]);

        $storeRes->assertRedirect();
        $letter = Letter::where('letter_number', '002/PAC/ISNU-WONOKROMO/XI/2026')->firstOrFail();
        $this->assertSame(Letter::LEVEL_PAC, $letter->level);
        $this->assertSame($this->pacWonokromo->id, (int) $letter->pac_id);

        // 3. Halaman edit juga mengunci level & PAC, serta update tidak bisa mengubah level ke PC ISNU / PAC lain
        $editRes = $this->actingAs($anggotaSeksiWonokromo)->get(route('admin.letters.edit', $letter->id));
        $editRes->assertOk();
        $editRes->assertDontSee('id="levelPC"', false);
        $editRes->assertSee('<input type="hidden" name="pac_id" value="'.$this->pacWonokromo->id.'">', false);

        $updateRes = $this->actingAs($anggotaSeksiWonokromo)->put(route('admin.letters.update', $letter->id), [
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'pac_id' => $this->pacSukolilo->id,
            'letter_number' => $letter->letter_number,
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-21',
            'lampiran' => '-',
            'perihal' => 'Undangan Konsolidasi PAC Wonokromo (Revisi)',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Pengurus PAC Wonokromo',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Revisi isi surat.</p>',
            'action' => 'submit_secretary',
        ]);

        $updateRes->assertRedirect(route('admin.letters.show', $letter->id));
        $letter->refresh();
        $this->assertSame(Letter::LEVEL_PAC, $letter->level);
        $this->assertSame($this->pacWonokromo->id, (int) $letter->pac_id);
        $this->assertSame(Letter::STATUS_MENUNGGU_SEKRETARIS, $letter->status);
    }

    public function test_pac_letter_can_only_be_signed_by_secretary_and_chairman_of_that_same_pac(): void
    {
        Storage::fake('public');

        $sekretarisPc = $this->createOfficer('Sekretaris PC', 'Sekretaris', 'PC ISNU', null);
        $ketuaPc = $this->createOfficer('Ketua PC', 'Ketua', 'PC ISNU', null);

        $sekretarisSukolilo = $this->createOfficer('Sekretaris PAC Sukolilo', 'Sekretaris', 'PAC ISNU', $this->pacSukolilo);
        $ketuaSukolilo = $this->createOfficer('Ketua PAC Sukolilo', 'Ketua', 'PAC ISNU', $this->pacSukolilo);

        $sekretarisWonokromo = $this->createOfficer('Sekretaris PAC Wonokromo', 'Sekretaris', 'PAC ISNU', $this->pacWonokromo);
        $ketuaWonokromo = $this->createOfficer('Ketua PAC Wonokromo', 'Ketua', 'PAC ISNU', $this->pacWonokromo);
        $pengajuWonokromo = $this->createOfficer('Koordinator PAC Wonokromo', 'Koordinator Seksi', 'PAC ISNU', $this->pacWonokromo);

        $letter = $this->createLetter(
            $pengajuWonokromo,
            Letter::LEVEL_PAC,
            $this->pacWonokromo,
            '003/PAC/ISNU-WONOKROMO/XI/2026',
            'Permohonan Peminjaman Tempat',
            Letter::STATUS_MENUNGGU_SEKRETARIS
        );

        // Sekretaris PC dan Sekretaris PAC lain DILARANG menandatangani surat PAC Wonokromo
        $this->actingAs($sekretarisPc)->post(route('admin.letters.sign-secretary', $letter->id))->assertForbidden();
        $this->actingAs($sekretarisSukolilo)->post(route('admin.letters.sign-secretary', $letter->id))->assertForbidden();

        // Sekretaris PAC Wonokromo BERHASIL menandatangani tahap 1
        $secRes = $this->actingAs($sekretarisWonokromo)->post(route('admin.letters.sign-secretary', $letter->id), [
            'signature_image' => UploadedFile::fake()->image('ttd_sek_wnk.png', 200, 100),
        ]);
        $secRes->assertRedirect(route('admin.letters.show', $letter->id));
        $letter->refresh();
        $this->assertSame(Letter::STATUS_MENUNGGU_KETUA, $letter->status);
        $this->assertSame($sekretarisWonokromo->id, (int) $letter->secretary_user_id);

        // Ketua PC dan Ketua PAC lain DILARANG menandatangani surat PAC Wonokromo
        $this->actingAs($ketuaPc)->post(route('admin.letters.sign-chairman', $letter->id))->assertForbidden();
        $this->actingAs($ketuaSukolilo)->post(route('admin.letters.sign-chairman', $letter->id))->assertForbidden();

        // Ketua PAC Wonokromo BERHASIL menandatangani tahap 2 (Selesai)
        $chairRes = $this->actingAs($ketuaWonokromo)->post(route('admin.letters.sign-chairman', $letter->id), [
            'signature_image' => UploadedFile::fake()->image('ttd_ketua_wnk.png', 200, 100),
        ]);
        $chairRes->assertRedirect(route('admin.letters.show', $letter->id));
        $letter->refresh();
        $this->assertSame(Letter::STATUS_SELESAI, $letter->status);
        $this->assertSame($ketuaWonokromo->id, (int) $letter->chairman_user_id);
    }
}
