<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPresence;
use App\Models\Pac;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionAndEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_sections_and_create_section()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.sections.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->post(route('admin.sections.store'), [
            'level' => 'PC ISNU',
            'name' => 'Seksi Kaderisasi dan Ideologi',
            'description' => 'Seksi penanggung jawab kaderisasi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sections', [
            'name' => 'Seksi Kaderisasi dan Ideologi',
            'level' => 'PC ISNU',
        ]);
    }

    public function test_admin_can_create_event_and_public_presensi_works()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $eventData = [
            'title' => 'Halqah Kebangsaan ISNU 2026',
            'description' => 'Diskusi publik ISNU',
            'location' => 'Gedung PCNU Surabaya',
            'method' => 'luring',
            'event_date' => date('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.store'), $eventData);
        $response->assertRedirect();

        $event = Event::where('title', 'Halqah Kebangsaan ISNU 2026')->first();
        $this->assertNotNull($event);

        // Test Public Presensi Page
        $response = $this->get(route('event.presence.show', $event->unique_code));
        $response->assertStatus(200);
        $response->assertSee('Halqah Kebangsaan ISNU 2026');

        // Test Submit Presensi
        $response = $this->post(route('event.presence.submit', $event->unique_code), [
            'name' => 'Ahmad Subandi, M.Pd.',
            'phone' => '081299998888',
            'institution_or_pac' => 'PAC ISNU Gayungan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('event_presences', [
            'event_id' => $event->id,
            'name' => 'Ahmad Subandi, M.Pd.',
            'phone' => '081299998888',
        ]);
    }
}
