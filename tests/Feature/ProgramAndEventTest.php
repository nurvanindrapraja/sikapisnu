<?php

namespace Tests\Feature;

use App\Models\Event;
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
}
