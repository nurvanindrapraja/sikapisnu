<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use App\Models\Workgroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PacOfficerPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $ketuaPac;

    protected User $sekretarisPac;

    protected Pac $pacWonokromo;

    protected Pac $pacSukolilo;

    protected Member $memberWonokromo;

    protected Member $memberSukolilo;

    protected Section $sectionWonokromo;

    protected Section $sectionSukolilo;

    protected Workgroup $pokjaWonokromo;

    protected Workgroup $pokjaSukolilo;

    protected Program $programWonokromo;

    protected Program $programSukolilo;

    protected Event $eventWonokromo;

    protected Event $eventSukolilo;

    protected function createTestMember(array $attributes = []): Member
    {
        $userId = $attributes['user_id'] ?? null;
        if (! $userId) {
            $user = User::factory()->create([
                'role' => 'member',
                'is_active' => true,
            ]);
            $userId = $user->id;
        }

        return Member::create(array_merge([
            'user_id' => $userId,
            'member_number' => 'ISNU-'.fake()->unique()->numerify('######'),
            'nik' => fake()->unique()->numerify('3578############'),
            'full_name' => fake()->name(),
            'birth_place' => 'Kota Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Jagir Wonokromo No. 1',
            'kelurahan' => 'Wonokromo',
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
            'occupation' => 'Akademisi / Dosen',
            'phone' => '0812'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'membership_status' => 'pengurus',
        ], $attributes, ['user_id' => $userId]));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // 1. Ambil atau Buat MWC & PAC
        $mwc = Mwc::first() ?? Mwc::create(['name' => 'MWC NU Surabaya', 'code' => 'MWC-SBY', 'city' => 'Kota Surabaya']);

        $this->pacWonokromo = Pac::firstOrCreate(
            ['code' => 'PAC-WNK'],
            ['mwc_id' => $mwc->id, 'name' => 'PAC ISNU Wonokromo', 'kecamatan' => 'Wonokromo', 'city' => 'Kota Surabaya', 'province' => 'Jawa Timur']
        );

        $this->pacSukolilo = Pac::firstOrCreate(
            ['code' => 'PAC-SKL'],
            ['mwc_id' => $mwc->id, 'name' => 'PAC ISNU Sukolilo', 'kecamatan' => 'Sukolilo', 'city' => 'Kota Surabaya', 'province' => 'Jawa Timur']
        );

        // 2. Akun Ketua PAC Wonokromo
        $userKetua = User::factory()->create([
            'name' => 'Ketua PAC Wonokromo',
            'email' => 'ketua.pac.wonokromo@test.com',
            'role' => 'member',
            'is_active' => true,
        ]);
        $memberKetua = $this->createTestMember([
            'user_id' => $userKetua->id,
            'member_number' => 'ISNU-SBY-26-WNK001',
            'nik' => '3578010101010001',
            'full_name' => 'Ketua PAC Wonokromo',
            'email' => 'ketua.pac.wonokromo@test.com',
            'phone' => '081234560001',
            'membership_status' => 'pengurus',
            'pac_id' => $this->pacWonokromo->id,
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
        ]);
        Position::create([
            'member_id' => $memberKetua->id,
            'position_title' => 'Ketua',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);
        $this->ketuaPac = $userKetua;

        // 3. Akun Sekretaris PAC Wonokromo
        $userSekretaris = User::factory()->create([
            'name' => 'Sekretaris PAC Wonokromo',
            'email' => 'sekretaris.pac.wonokromo@test.com',
            'role' => 'member',
            'is_active' => true,
        ]);
        $memberSekretaris = $this->createTestMember([
            'user_id' => $userSekretaris->id,
            'member_number' => 'ISNU-SBY-26-WNK002',
            'nik' => '3578010101010002',
            'full_name' => 'Sekretaris PAC Wonokromo',
            'email' => 'sekretaris.pac.wonokromo@test.com',
            'phone' => '081234560002',
            'membership_status' => 'pengurus',
            'pac_id' => $this->pacWonokromo->id,
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
        ]);
        Position::create([
            'member_id' => $memberSekretaris->id,
            'position_title' => 'Sekretaris',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);
        $this->sekretarisPac = $userSekretaris;

        // 4. Data Kader Biasa (Wonokromo vs Sukolilo)
        $this->memberWonokromo = $this->createTestMember([
            'member_number' => 'ISNU-SBY-26-WNK099',
            'nik' => '3578010101010099',
            'full_name' => 'Kader Warga Wonokromo',
            'email' => 'kader.wonokromo@test.com',
            'phone' => '081234560099',
            'membership_status' => 'terverifikasi',
            'pac_id' => $this->pacWonokromo->id,
            'kecamatan' => 'Wonokromo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
        ]);

        $this->memberSukolilo = $this->createTestMember([
            'member_number' => 'ISNU-SBY-26-SKL099',
            'nik' => '3578020202020099',
            'full_name' => 'Kader Warga Sukolilo',
            'email' => 'kader.sukolilo@test.com',
            'phone' => '081234560199',
            'membership_status' => 'terverifikasi',
            'pac_id' => $this->pacSukolilo->id,
            'kecamatan' => 'Sukolilo',
            'city' => 'Kota Surabaya',
            'province' => 'Jawa Timur',
        ]);

        // 5. Data Seksi
        $this->sectionWonokromo = Section::create([
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
            'name' => 'Seksi Kaderisasi Wonokromo',
            'code' => 'SKS-WNK-KAD',
        ]);

        $this->sectionSukolilo = Section::create([
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacSukolilo->id,
            'name' => 'Seksi Kaderisasi Sukolilo',
            'code' => 'SKS-SKL-KAD',
        ]);

        // 6. Data Pokja
        $this->pokjaWonokromo = Workgroup::create([
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
            'name' => 'Pokja Digital Wonokromo',
            'code' => 'PKJ-WNK-DIG',
            'is_active' => true,
        ]);

        $this->pokjaSukolilo = Workgroup::create([
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacSukolilo->id,
            'name' => 'Pokja Digital Sukolilo',
            'code' => 'PKJ-SKL-DIG',
            'is_active' => true,
        ]);

        // 7. Data Program
        $this->programWonokromo = Program::create([
            'section_id' => $this->sectionWonokromo->id,
            'name' => 'Pelatihan Digital Wonokromo',
            'code' => 'PRG-WNK-01',
            'is_active' => true,
        ]);

        $this->programSukolilo = Program::create([
            'section_id' => $this->sectionSukolilo->id,
            'name' => 'Pelatihan Digital Sukolilo',
            'code' => 'PRG-SKL-01',
            'is_active' => true,
        ]);

        // 8. Data Event
        $this->eventWonokromo = Event::create([
            'title' => 'Kajian Rutin PAC Wonokromo',
            'unique_code' => 'evt-wnk-01',
            'section_id' => $this->sectionWonokromo->id,
            'program_id' => $this->programWonokromo->id,
            'location' => 'Masjid Wonokromo',
            'method' => 'luring',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'presence_start_at' => now()->addDays(2)->setTime(8, 30),
            'presence_end_at' => now()->addDays(2)->setTime(12, 0),
            'status' => 'planned',
        ]);

        $this->eventSukolilo = Event::create([
            'title' => 'Kajian Rutin PAC Sukolilo',
            'unique_code' => 'evt-skl-01',
            'section_id' => $this->sectionSukolilo->id,
            'program_id' => $this->programSukolilo->id,
            'location' => 'Masjid Sukolilo',
            'method' => 'luring',
            'event_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'presence_start_at' => now()->addDays(3)->setTime(8, 30),
            'presence_end_at' => now()->addDays(3)->setTime(12, 0),
            'status' => 'planned',
        ]);
    }

    public function test_ketua_dan_sekretaris_pac_tidak_dapat_memverifikasi_pendaftar()
    {
        $applicant = Member::where('membership_status', 'menunggu_verifikasi')->first();
        if (! $applicant) {
            $applicant = $this->createTestMember([
                'member_number' => 'ISNU-SBY-26-REG001',
                'nik' => '3578999911112222',
                'full_name' => 'Pendaftar Baru',
                'email' => 'pendaftar@test.com',
                'phone' => '081234567899',
                'membership_status' => 'menunggu_verifikasi',
                'pac_id' => $this->pacWonokromo->id,
            ]);
        }

        // 1. Uji untuk Ketua PAC
        $this->actingAs($this->ketuaPac);
        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.verifikasi.show', $applicant->id))->assertStatus(403);
        $this->post(route('admin.verifikasi.approve', $applicant->id))->assertStatus(403);

        // 2. Uji untuk Sekretaris PAC
        $this->actingAs($this->sekretarisPac);
        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.verifikasi.show', $applicant->id))->assertStatus(403);
        $this->post(route('admin.verifikasi.approve', $applicant->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_tidak_dapat_mengelola_master_pac_isnu()
    {
        // 1. Ketua PAC
        $this->actingAs($this->ketuaPac);
        $this->get(route('admin.mwc.index'))->assertStatus(403);
        $this->post(route('admin.pac.store'), [
            'name' => 'PAC Baru',
            'code' => 'PAC-BARU',
            'kecamatan' => 'Tegalsari',
        ])->assertStatus(403);

        // 2. Sekretaris PAC
        $this->actingAs($this->sekretarisPac);
        $this->get(route('admin.mwc.index'))->assertStatus(403);
        $this->post(route('admin.pac.store'), [
            'name' => 'PAC Baru',
            'code' => 'PAC-BARU2',
            'kecamatan' => 'Tegalsari',
        ])->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_tidak_bisa_mengakses_eksport_laporan()
    {
        // 1. Ketua PAC
        $this->actingAs($this->ketuaPac);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.laporan.export'))->assertStatus(403);

        // 2. Sekretaris PAC
        $this->actingAs($this->sekretarisPac);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.laporan.export'))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_tidak_bisa_mengakses_audit_log()
    {
        // 1. Ketua PAC
        $this->actingAs($this->ketuaPac);
        $this->get(route('admin.audit.index'))->assertStatus(403);

        // 2. Sekretaris PAC
        $this->actingAs($this->sekretarisPac);
        $this->get(route('admin.audit.index'))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_tidak_bisa_mengakses_kartu_fisik()
    {
        // 1. Helper check
        $this->assertFalse($this->ketuaPac->canManageCardOrders());
        $this->assertFalse($this->sekretarisPac->canManageCardOrders());

        // 2. Route check - Ketua PAC
        $this->actingAs($this->ketuaPac);
        $this->get(route('admin.card_orders.index'))->assertStatus(403);

        // 3. Route check - Sekretaris PAC
        $this->actingAs($this->sekretarisPac);
        $this->get(route('admin.card_orders.index'))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_melihat_database_kader_di_wilayah_pac_sendiri()
    {
        $this->actingAs($this->ketuaPac);

        // 1. Index menampilkan kader di PAC Wonokromo, tidak menampilkan kader di PAC Sukolilo
        $res = $this->get(route('admin.anggota.index'));
        $res->assertStatus(200);
        $res->assertSee($this->memberWonokromo->full_name);
        $res->assertDontSee($this->memberSukolilo->full_name);

        // 2. Detail kader di PAC Wonokromo bisa dilihat
        $this->get(route('admin.anggota.show', $this->memberWonokromo->id))->assertStatus(200);

        // 3. Detail kader di PAC Sukolilo DILARANG (403)
        $this->get(route('admin.anggota.show', $this->memberSukolilo->id))->assertStatus(403);

        // 4. HANYA BISA MELIHAT: Tidak bisa mengedit, mengupdate, atau menghapus kader di PAC-nya sendiri
        $this->get(route('admin.anggota.edit', $this->memberWonokromo->id))->assertStatus(403);
        $this->put(route('admin.anggota.update', $this->memberWonokromo->id), [
            'full_name' => 'Nama Baru',
        ])->assertStatus(403);
        $this->delete(route('admin.anggota.destroy', $this->memberWonokromo->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_melihat_pengurus_di_pac_sendiri_dan_tidak_bisa_promote_demote()
    {
        // Buat pengurus di Sukolilo
        $userSkl = User::factory()->create(['role' => 'member']);
        $memberOfficerSkl = $this->createTestMember([
            'user_id' => $userSkl->id,
            'member_number' => 'ISNU-SBY-26-SKLOFF',
            'nik' => '3578030303030099',
            'full_name' => 'Pengurus Sukolilo',
            'email' => 'pengurus.sukolilo@test.com',
            'membership_status' => 'pengurus',
            'pac_id' => $this->pacSukolilo->id,
            'kecamatan' => 'Sukolilo',
        ]);
        Position::create([
            'member_id' => $memberOfficerSkl->id,
            'position_title' => 'Ketua',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacSukolilo->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        $this->actingAs($this->sekretarisPac);

        // 1. Index pengurus hanya menampilkan pengurus PAC Wonokromo
        $res = $this->get(route('admin.pengurus.index'));
        $res->assertStatus(200);
        $res->assertSee($this->ketuaPac->name);
        $res->assertDontSee($memberOfficerSkl->full_name);

        // 2. Dilarang promote
        $this->post(route('admin.pengurus.promote', $this->memberWonokromo->id), [
            'position_title' => 'Wakil Ketua',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
            'period' => '2026-2030',
        ])->assertStatus(403);

        // 3. Dilarang demote
        $this->post(route('admin.pengurus.demote', $this->ketuaPac->member->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_membuat_dan_mengelola_seksi_di_pac_sendiri()
    {
        $this->actingAs($this->ketuaPac);

        // 1. Index hanya melihat seksi di PAC Wonokromo
        $res = $this->get(route('admin.sections.index'));
        $res->assertStatus(200);
        $res->assertSee($this->sectionWonokromo->name);
        $res->assertDontSee($this->sectionSukolilo->name);

        // 2. Membuat seksi di PAC sendiri berhasil
        $storeRes = $this->post(route('admin.sections.store'), [
            'name' => 'Seksi Ekonomi Wonokromo',
            'code' => 'SKS-WNK-EKO',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
        ]);
        $storeRes->assertSessionHas('success');
        $this->assertDatabaseHas('sections', [
            'name' => 'Seksi Ekonomi Wonokromo',
            'pac_id' => $this->pacWonokromo->id,
            'level' => 'PAC ISNU',
        ]);

        // 3. Tidak dapat mengedit atau menghapus seksi di PAC lain
        $this->put(route('admin.sections.update', $this->sectionSukolilo->id), [
            'name' => 'Seksi Bajakan',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacSukolilo->id,
        ])->assertStatus(403);

        $this->delete(route('admin.sections.destroy', $this->sectionSukolilo->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_membuat_dan_mengelola_pokja_di_pac_sendiri()
    {
        $this->actingAs($this->ketuaPac);

        // 1. Index hanya melihat pokja di PAC Wonokromo
        $res = $this->get(route('admin.workgroups.index'));
        $res->assertStatus(200);
        $res->assertSee($this->pokjaWonokromo->name);
        $res->assertDontSee($this->pokjaSukolilo->name);

        // 2. Membuat pokja baru di PAC Wonokromo berhasil
        $storeRes = $this->post(route('admin.workgroups.store'), [
            'name' => 'Pokja Media Wonokromo',
            'code' => 'PKJ-WNK-MED',
            'level' => 'PAC ISNU',
            'pac_id' => $this->pacWonokromo->id,
        ]);
        $storeRes->assertSessionHas('success');
        $this->assertDatabaseHas('workgroups', [
            'name' => 'Pokja Media Wonokromo',
            'pac_id' => $this->pacWonokromo->id,
        ]);

        // 3. Tidak dapat melihat, mengedit, atau menghapus pokja di PAC lain
        $this->get(route('admin.workgroups.show', $this->pokjaSukolilo->id))->assertStatus(403);
        $this->put(route('admin.workgroups.update', $this->pokjaSukolilo->id), [
            'name' => 'Pokja Ganti',
            'level' => 'PAC ISNU',
        ])->assertStatus(403);
        $this->delete(route('admin.workgroups.destroy', $this->pokjaSukolilo->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_membuat_program_di_pac_sendiri()
    {
        $this->actingAs($this->sekretarisPac);

        // 1. Index hanya melihat program di PAC Wonokromo
        $res = $this->get(route('admin.programs.index'));
        $res->assertStatus(200);
        $res->assertSee($this->programWonokromo->name);
        $res->assertDontSee($this->programSukolilo->name);

        // 2. Membuat program pada seksi PAC sendiri berhasil
        $resStore = $this->post(route('admin.programs.store'), [
            'section_id' => $this->sectionWonokromo->id,
            'name' => 'Program Baru Wonokromo',
            'code' => 'PRG-NEW-WNK',
        ]);
        $resStore->assertSessionHas('success');
        $this->assertDatabaseHas('programs', [
            'name' => 'Program Baru Wonokromo',
            'section_id' => $this->sectionWonokromo->id,
        ]);

        // 3. Dilarang membuat program pada seksi PAC lain (403)
        $this->post(route('admin.programs.store'), [
            'section_id' => $this->sectionSukolilo->id,
            'name' => 'Program Ilegal Sukolilo',
        ])->assertStatus(403);

        // 4. Dilarang mengedit atau menghapus program di PAC lain
        $this->put(route('admin.programs.update', $this->programSukolilo->id), [
            'section_id' => $this->sectionSukolilo->id,
            'name' => 'Program Ganti Nama',
        ])->assertStatus(403);

        $this->delete(route('admin.programs.destroy', $this->programSukolilo->id))->assertStatus(403);
    }

    public function test_ketua_dan_sekretaris_pac_hanya_bisa_membuat_kegiatan_di_pac_sendiri()
    {
        $this->actingAs($this->ketuaPac);

        // 1. Index hanya melihat kegiatan di PAC Wonokromo
        $res = $this->get(route('admin.events.index'));
        $res->assertStatus(200);
        $res->assertSee($this->eventWonokromo->title);
        $res->assertDontSee($this->eventSukolilo->title);

        // 2. Membuat kegiatan pada seksi PAC sendiri berhasil
        $resStore = $this->post(route('admin.events.store'), [
            'title' => 'Pengajian Rutin PAC Wonokromo',
            'section_id' => $this->sectionWonokromo->id,
            'program_id' => $this->programWonokromo->id,
            'location' => 'Kantor MWC Wonokromo',
            'method' => 'luring',
            'event_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'presence_start_at' => now()->addDays(5)->setTime(12, 30)->format('Y-m-d H:i:s'),
            'presence_end_at' => now()->addDays(5)->setTime(16, 0)->format('Y-m-d H:i:s'),
            'status' => 'planned',
        ]);
        $resStore->assertSessionHas('success');
        $this->assertDatabaseHas('events', [
            'title' => 'Pengajian Rutin PAC Wonokromo',
            'section_id' => $this->sectionWonokromo->id,
        ]);

        // 3. Dilarang membuat kegiatan pada seksi PAC lain (403)
        $this->post(route('admin.events.store'), [
            'title' => 'Kegiatan Ilegal di Sukolilo',
            'section_id' => $this->sectionSukolilo->id,
            'location' => 'Masjid',
            'method' => 'luring',
            'event_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'presence_start_at' => now()->addDays(5)->setTime(12, 30)->format('Y-m-d H:i:s'),
            'presence_end_at' => now()->addDays(5)->setTime(16, 0)->format('Y-m-d H:i:s'),
            'status' => 'planned',
        ])->assertStatus(403);

        // 4. Dilarang melihat, mengedit, atau menghapus kegiatan di PAC lain
        $this->get(route('admin.events.show', $this->eventSukolilo->id))->assertStatus(403);
        $this->put(route('admin.events.update', $this->eventSukolilo->id), [
            'title' => 'Ganti Judul',
            'section_id' => $this->sectionSukolilo->id,
            'location' => 'Masjid',
            'method' => 'luring',
            'event_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'presence_start_at' => now()->addDays(5)->setTime(12, 30)->format('Y-m-d H:i:s'),
            'presence_end_at' => now()->addDays(5)->setTime(16, 0)->format('Y-m-d H:i:s'),
            'status' => 'planned',
        ])->assertStatus(403);
        $this->delete(route('admin.events.destroy', $this->eventSukolilo->id))->assertStatus(403);
    }
}
