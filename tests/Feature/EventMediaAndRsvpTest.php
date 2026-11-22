<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Member;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMediaAndRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_event_generates_rsvp_link_and_returns_it_in_json_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.events.store'), [
            'title' => 'Rapat Kerja PC ISNU Surabaya 2026',
            'description' => 'Rapat kerja tahunan pengurus',
            'location' => 'Gedung PCNU Surabaya',
            'method' => 'luring',
            'event_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '15:00',
            'presence_start_at' => now()->addDays(5)->setTime(9, 0)->format('Y-m-d\TH:i'),
            'presence_end_at' => now()->addDays(5)->setTime(15, 0)->format('Y-m-d\TH:i'),
            'rsvp_enabled' => '1',
            'rsvp_deadline_at' => now()->addDays(4)->setTime(20, 0)->format('Y-m-d\TH:i'),
            'status' => 'planned',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'rsvp_enabled' => true,
            ]);

        $event = Event::where('title', 'Rapat Kerja PC ISNU Surabaya 2026')->firstOrFail();
        $this->assertTrue($event->rsvp_enabled);
        $this->assertNotNull($event->rsvp_deadline_at);

        $expectedRsvpUrl = route('event.rsvp.show', $event->unique_code);
        $response->assertJsonFragment(['rsvp_url' => $expectedRsvpUrl]);

        // Public RSVP page should be accessible
        $this->get($expectedRsvpUrl)
            ->assertStatus(200)
            ->assertSee('Rapat Kerja PC ISNU Surabaya 2026')
            ->assertSee('KONFIRMASI KEHADIRAN (RSVP)');
    }

    public function test_guest_and_member_can_submit_and_update_rsvp_and_admin_sees_estimate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $memberUser = $this->createMemberUser('Dr. Ahmad Fauzi', '081234567801');

        $event = Event::create([
            'title' => 'Seminar Kebangsaan ISNU',
            'unique_code' => 'evt-seminar-rsvp-01',
            'location' => 'Hotel Bumi Surabaya',
            'method' => 'luring',
            'event_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->addDays(3)->setTime(8, 0),
            'presence_end_at' => now()->addDays(3)->setTime(12, 0),
            'rsvp_enabled' => true,
            'rsvp_deadline_at' => now()->addDays(2),
            'status' => 'planned',
            'created_by' => $admin->id,
        ]);

        // Guest confirms "hadir"
        $this->post(route('event.rsvp.submit', $event->unique_code), [
            'name' => 'Tamu Undangan',
            'attendance_status' => EventRsvp::STATUS_HADIR,
            'phone' => '081299990001',
            'institution_or_pac' => 'PCNU Surabaya',
            'notes' => 'InsyaAllah hadir tepat waktu',
        ])->assertRedirect(route('event.rsvp.show', $event->unique_code));

        // Authenticated member confirms "ragu", then updates to "hadir" (should update, not duplicate)
        $this->actingAs($memberUser)->post(route('event.rsvp.submit', $event->unique_code), [
            'name' => 'Dr. Ahmad Fauzi',
            'attendance_status' => EventRsvp::STATUS_RAGU,
            'phone' => '081234567801',
            'institution_or_pac' => 'PAC ISNU Gayungan',
        ])->assertRedirect(route('event.rsvp.show', $event->unique_code));

        $this->actingAs($memberUser)->post(route('event.rsvp.submit', $event->unique_code), [
            'name' => 'Dr. Ahmad Fauzi',
            'attendance_status' => EventRsvp::STATUS_HADIR,
            'phone' => '081234567801',
            'institution_or_pac' => 'PAC ISNU Gayungan',
        ])->assertRedirect(route('event.rsvp.show', $event->unique_code));

        // Another guest confirms "tidak_hadir"
        auth()->logout();
        $this->post(route('event.rsvp.submit', $event->unique_code), [
            'name' => 'Budi Santoso',
            'attendance_status' => EventRsvp::STATUS_TIDAK_HADIR,
            'phone' => '081299990002',
            'institution_or_pac' => 'PAC ISNU Wonokromo',
            'notes' => 'Sedang dinas luar kota',
        ])->assertRedirect(route('event.rsvp.show', $event->unique_code));

        $this->assertEquals(3, EventRsvp::where('event_id', $event->id)->count());
        $this->assertEquals(2, $event->attendingRsvps()->count());

        // Admin show page renders counts and respondents
        $this->actingAs($admin)->get(route('admin.events.show', $event->id))
            ->assertStatus(200)
            ->assertSee('Estimasi Jumlah Peserta')
            ->assertSee('Akan Hadir: 2')
            ->assertSee('Tidak Hadir: 1')
            ->assertSee('Tamu Undangan')
            ->assertSee('Dr. Ahmad Fauzi')
            ->assertSee('Budi Santoso');

        // Admin index page renders estimated attendees badge
        $this->actingAs($admin)->get(route('admin.events.index'))
            ->assertStatus(200)
            ->assertSee('Estimasi: 2');
    }

    public function test_rsvp_submission_is_rejected_when_deadline_passed_or_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $expiredEvent = Event::create([
            'title' => 'Event Lewat Batas RSVP',
            'unique_code' => 'evt-rsvp-expired',
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->addDays(2),
            'presence_end_at' => now()->addDays(2)->addHours(4),
            'rsvp_enabled' => true,
            'rsvp_deadline_at' => now()->subHour(),
            'status' => 'planned',
            'created_by' => $admin->id,
        ]);

        $this->post(route('event.rsvp.submit', $expiredEvent->unique_code), [
            'name' => 'Peserta Telat',
            'attendance_status' => EventRsvp::STATUS_HADIR,
            'phone' => '081200001111',
            'institution_or_pac' => 'Umum',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('event_rsvps', 0);

        $disabledEvent = Event::create([
            'title' => 'Event RSVP Nonaktif',
            'unique_code' => 'evt-rsvp-disabled',
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->addDays(2),
            'presence_end_at' => now()->addDays(2)->addHours(4),
            'rsvp_enabled' => false,
            'status' => 'planned',
            'created_by' => $admin->id,
        ]);

        $this->get(route('event.rsvp.show', $disabledEvent->unique_code))
            ->assertStatus(200)
            ->assertSee('Konfirmasi Kehadiran Ditutup');
    }

    public function test_authorized_user_can_add_multiple_online_media_links_and_delete_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Media']);

        $event = Event::create([
            'title' => 'Harlah ISNU ke-27 Surabaya',
            'unique_code' => 'evt-harlah-27-media',
            'section_id' => $section->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour(),
            'presence_end_at' => now()->addHours(2),
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        // Add multiple online media links at once (one with media_name, one auto-derived from URL host)
        $response = $this->actingAs($admin)->post(route('admin.events.media_links.store', $event->id), [
            'media_links' => [
                [
                    'media_name' => 'NU Online',
                    'title' => 'PC ISNU Surabaya Gelar Harlah ke-27',
                    'url' => 'https://www.nu.or.id/daerah/harlah-isnu-surabaya',
                ],
                [
                    'media_name' => '',
                    'title' => 'Sinergi Cendekiawan NU di Surabaya',
                    'url' => 'https://jatim.detik.com/berita/harlah-isnu-27',
                ],
            ],
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $event->refresh();
        $this->assertCount(2, $event->media_links);
        $this->assertEquals('NU Online', $event->media_links[0]['media_name']);
        $this->assertEquals('jatim.detik.com', $event->media_links[1]['media_name']);

        // Show page displays both media links
        $this->actingAs($admin)->get(route('admin.events.show', $event->id))
            ->assertStatus(200)
            ->assertSee('NU Online')
            ->assertSee('https://www.nu.or.id/daerah/harlah-isnu-surabaya')
            ->assertSee('jatim.detik.com')
            ->assertSee('https://jatim.detik.com/berita/harlah-isnu-27');

        // Delete first media link
        $this->actingAs($admin)->delete(route('admin.events.media_links.destroy', [$event->id, 0]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertCount(1, $event->media_links);
        $this->assertEquals('https://jatim.detik.com/berita/harlah-isnu-27', $event->media_links[0]['url']);
    }

    public function test_unauthorized_officer_cannot_add_or_delete_media_links(): void
    {
        $sectionA = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Media']);
        $sectionB = Section::create(['level' => 'PC ISNU', 'name' => 'Seksi Kaderisasi']);
        $wakilKetuaB = $this->createOfficerUser('Wakil Ketua', $sectionB->id);

        $event = Event::create([
            'title' => 'Kegiatan Seksi Media',
            'unique_code' => 'evt-media-unauth',
            'section_id' => $sectionA->id,
            'location' => 'Surabaya',
            'method' => 'luring',
            'event_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour(),
            'presence_end_at' => now()->addHours(2),
            'status' => 'completed',
            'media_links' => [
                ['media_name' => 'NU Online', 'title' => 'Berita', 'url' => 'https://nu.or.id/test'],
            ],
        ]);

        $this->actingAs($wakilKetuaB)->post(route('admin.events.media_links.store', $event->id), [
            'media_links' => [
                ['media_name' => 'Kompas', 'title' => 'Test', 'url' => 'https://kompas.com/test'],
            ],
        ])->assertStatus(403);

        $this->actingAs($wakilKetuaB)->delete(route('admin.events.media_links.destroy', [$event->id, 0]))
            ->assertStatus(403);
    }

    protected function createMemberUser(string $fullName, string $phone): User
    {
        $user = User::factory()->create([
            'name' => $fullName,
            'phone' => $phone,
            'role' => 'member',
        ]);

        Member::create([
            'user_id' => $user->id,
            'member_number' => 'ISNU-'.fake()->numerify('######'),
            'nik' => '3578'.fake()->numerify('############'),
            'full_name' => $fullName,
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Gayungan No. 1',
            'kelurahan' => 'Gayungan',
            'kecamatan' => 'Gayungan',
            'city' => 'Kota Surabaya',
            'occupation' => 'Dosen',
            'phone' => $phone,
            'email' => $user->email,
            'membership_status' => 'terverifikasi',
        ]);

        return $user->fresh();
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
