<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Member;
use App\Models\Position;
use App\Models\Program;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramAndEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_programs_index_and_create_program_with_indicator_and_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Kaderisasi dan Ideologi',
            'code' => 'SKS-KDR',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.programs.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Program Kerja Seksi');

        $response = $this->actingAs($admin)->post(route('admin.programs.store'), [
            'section_id' => $section->id,
            'name' => 'Program Sekolah Kader Intelektual ASWAJA',
            'code' => 'PRG-KDR-01',
            'indicator' => 'Terlaksananya pelatihan kader berkala dan pembinaan alumni',
            'target' => 'Minimal 100 kader sarjana terdidik per tahun',
            'description' => 'Fokus pada penguatan kapasitas berpikir kritis dan ideologi keaswajaan',
            'year' => '2026',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('programs', [
            'section_id' => $section->id,
            'name' => 'Program Sekolah Kader Intelektual ASWAJA',
            'code' => 'PRG-KDR-01',
            'indicator' => 'Terlaksananya pelatihan kader berkala dan pembinaan alumni',
            'target' => 'Minimal 100 kader sarjana terdidik per tahun',
            'year' => '2026',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_and_delete_program(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Sains dan Teknologi',
        ]);

        $program = Program::create([
            'section_id' => $section->id,
            'name' => 'Program Riset Strategis',
            'code' => 'PRG-RST-01',
            'indicator' => 'Publikasi riset',
            'target' => '2 Paper',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.programs.update', $program->id), [
            'section_id' => $section->id,
            'name' => 'Program Riset Strategis dan Publikasi Ilmiah',
            'code' => 'PRG-RST-02',
            'indicator' => 'Publikasi riset terindeks Sinta/Scopus',
            'target' => '5 Paper Terpublikasi',
            'year' => '2026',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => 'Program Riset Strategis dan Publikasi Ilmiah',
            'target' => '5 Paper Terpublikasi',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.programs.destroy', $program->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('programs', [
            'id' => $program->id,
        ]);
    }

    public function test_admin_can_create_event_with_section_program_and_rab(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Kewirausahaan dan Ekonomi',
        ]);

        $program = Program::create([
            'section_id' => $section->id,
            'name' => 'Program Inkubasi Bisnis Kader',
            'indicator' => 'Jumlah kader membuka usaha mandiri',
            'target' => '20 UMKM Baru',
        ]);

        $eventData = [
            'title' => 'Workshop Manajemen Usaha & Akses Permodalan Kader ISNU',
            'description' => 'Pelatihan intensif permodalan',
            'section_id' => $section->id,
            'program_id' => $program->id,
            'budget' => 25000000, // 25 Juta
            'location' => 'Hotel Grand City Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(5)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.store'), $eventData);
        $response->assertRedirect();

        $event = Event::where('title', 'Workshop Manajemen Usaha & Akses Permodalan Kader ISNU')->first();
        $this->assertNotNull($event);
        $this->assertEquals($section->id, $event->section_id);
        $this->assertEquals($program->id, $event->program_id);
        $this->assertEquals('25000000.00', $event->budget);
        $this->assertEquals('Rp 25.000.000', $event->formatted_budget);

        // Test Event Show Page renders Section, Program and RAB
        $responseShow = $this->actingAs($admin)->get(route('admin.events.show', $event->id));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Seksi Kewirausahaan dan Ekonomi');
        $responseShow->assertSee('Program Inkubasi Bisnis Kader');
        $responseShow->assertSee('Rp 25.000.000');
    }

    public function test_rab_is_optional_in_event_creation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $eventData = [
            'title' => 'Kajian Rutin Selapanan ASWAJA',
            'description' => 'Kajian rutin bulanan',
            'section_id' => null,
            'program_id' => null,
            'budget' => null, // RAB tidak diisi (opsional)
            'location' => 'Masjid Al-Akbar Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '19:30',
            'end_time' => '21:30',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.store'), $eventData);
        $response->assertRedirect();

        $event = Event::where('title', 'Kajian Rutin Selapanan ASWAJA')->first();
        $this->assertNotNull($event);
        $this->assertNull($event->budget);
        $this->assertNull($event->formatted_budget);
    }

    public function test_api_get_programs_by_section_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Media dan Komunikasi',
        ]);

        Program::create([
            'section_id' => $section->id,
            'name' => 'Program Literasi Digital dan Media Sosial',
            'code' => 'PRG-MED-01',
            'indicator' => 'Jangkauan publikasi',
            'target' => '50.000 Views',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.programs.by_section', $section->id));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Program Literasi Digital dan Media Sosial',
            'code' => 'PRG-MED-01',
        ]);
    }

    public function test_program_cannot_be_deleted_if_it_has_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Kaderisasi',
        ]);

        $program = Program::create([
            'section_id' => $section->id,
            'name' => 'Program Pelatihan Kader',
            'is_active' => true,
        ]);

        Event::create([
            'title' => 'Pelatihan Kader Tingkat 1',
            'section_id' => $section->id,
            'program_id' => $program->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'presence_start_at' => now()->subHour(),
            'presence_end_at' => now()->addHours(2),
            'status' => 'planned',
            'unique_code' => 'evt-test-123456',
        ]);

        // Percobaan hapus via AJAX/JSON
        $responseJson = $this->actingAs($admin)->deleteJson(route('admin.programs.destroy', $program->id));
        $responseJson->assertStatus(422);
        $responseJson->assertJsonFragment(['success' => false]);

        // Percobaan hapus via web request
        $responseWeb = $this->actingAs($admin)->delete(route('admin.programs.destroy', $program->id));
        $responseWeb->assertSessionHas('error');

        // Pastikan program tetap ada di database
        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_ketua_and_sekretaris_can_manage_all_programs_and_events(): void
    {
        $sectionA = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi A']);
        $sectionB = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi B']);

        $ketuaUser = $this->createOfficerUser('Ketua');
        $sekretarisUser = $this->createOfficerUser('Sekretaris');

        // Ketua can add program to any section
        $resp = $this->actingAs($ketuaUser)->post(route('admin.programs.store'), [
            'section_id' => $sectionA->id,
            'name' => 'Program Seksi A oleh Ketua',
        ]);
        $resp->assertRedirect();
        $this->assertDatabaseHas('programs', ['name' => 'Program Seksi A oleh Ketua']);

        // Sekretaris can add program to another section
        $resp2 = $this->actingAs($sekretarisUser)->post(route('admin.programs.store'), [
            'section_id' => $sectionB->id,
            'name' => 'Program Seksi B oleh Sekretaris',
        ]);
        $resp2->assertRedirect();
        $this->assertDatabaseHas('programs', ['name' => 'Program Seksi B oleh Sekretaris']);

        // Ketua can add event to Section B
        $respEvent = $this->actingAs($ketuaUser)->post(route('admin.events.store'), [
            'title' => 'Kegiatan Seksi B oleh Ketua',
            'section_id' => $sectionB->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ]);
        $respEvent->assertRedirect();
        $this->assertDatabaseHas('events', ['title' => 'Kegiatan Seksi B oleh Ketua']);
    }

    public function test_wakil_ketua_and_wakil_sekretaris_can_only_manage_their_own_section(): void
    {
        $sectionA = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Publikasi']);
        $sectionB = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Kaderisasi']);

        $wakilKetua = $this->createOfficerUser('Wakil Ketua', $sectionA->id);
        $wakilSekretaris = $this->createOfficerUser('Wakil Sekretaris', $sectionA->id);

        // Wakil Ketua can create program in Section A
        $respA = $this->actingAs($wakilKetua)->post(route('admin.programs.store'), [
            'section_id' => $sectionA->id,
            'name' => 'Program Seksi A oleh Wakil Ketua',
        ]);
        $respA->assertRedirect();
        $this->assertDatabaseHas('programs', ['name' => 'Program Seksi A oleh Wakil Ketua']);

        // Wakil Ketua CANNOT create program in Section B (403)
        $respB = $this->actingAs($wakilKetua)->post(route('admin.programs.store'), [
            'section_id' => $sectionB->id,
            'name' => 'Program Seksi B oleh Wakil Ketua',
        ]);
        $respB->assertStatus(403);

        // Wakil Sekretaris can create event in Section A
        $respEventA = $this->actingAs($wakilSekretaris)->post(route('admin.events.store'), [
            'title' => 'Kegiatan Seksi A oleh Wakil Sekretaris',
            'section_id' => $sectionA->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ]);
        $respEventA->assertRedirect();
        $this->assertDatabaseHas('events', ['title' => 'Kegiatan Seksi A oleh Wakil Sekretaris']);

        // Wakil Sekretaris CANNOT create event in Section B (403)
        $respEventB = $this->actingAs($wakilSekretaris)->post(route('admin.events.store'), [
            'title' => 'Kegiatan Seksi B oleh Wakil Sekretaris',
            'section_id' => $sectionB->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ]);
        $respEventB->assertStatus(403);
    }

    public function test_other_positions_cannot_manage_programs_and_events(): void
    {
        $section = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Keuangan']);
        $bendahara = $this->createOfficerUser('Bendahara', null);

        $resp = $this->actingAs($bendahara)->post(route('admin.programs.store'), [
            'section_id' => $section->id,
            'name' => 'Program oleh Bendahara',
        ]);
        $resp->assertStatus(403);
    }

    protected function createOfficerUser(string $positionTitle, ?int $sectionId = null): User
    {
        $user = User::factory()->create(['role' => 'member']);
        $member = Member::create([
            'user_id' => $user->id,
            'member_number' => 'ISNU-'.fake()->numerify('######'),
            'nik' => '3578'.fake()->numerify('############'),
            'full_name' => 'Pengurus '.$positionTitle,
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Gayungan No. 1',
            'kelurahan' => 'Gayungan',
            'kecamatan' => 'Gayungan',
            'city' => 'Kota Surabaya',
            'occupation' => 'Pengurus',
            'phone' => '0812'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $member->id,
            'position_title' => $positionTitle,
            'level' => 'Kota',
            'section_id' => $sectionId,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        return $user->fresh();
    }
}
