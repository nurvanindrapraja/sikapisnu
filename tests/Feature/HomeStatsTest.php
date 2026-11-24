<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_stats_counts_pac_members_strictly_by_explicit_pac_id_or_active_pac_position(): void
    {
        Cache::forget('home_stats_metrics');

        $mwc = Mwc::create([
            'code' => 'MWC-GAYUNGAN',
            'name' => 'MWC NU Gayungan',
            'city' => 'Kota Surabaya',
        ]);

        $pac = Pac::create([
            'mwc_id' => $mwc->id,
            'code' => 'PAC-GAYUNGAN',
            'name' => 'PAC Gayungan',
            'kecamatan' => 'GAYUNGAN',
            'city' => 'KOTA SURABAYA',
            'province' => 'JAWA TIMUR',
        ]);

        // 1. Anggota terverifikasi tanpa pac_id, hanya punya kecamatan (TIDAK boleh dihitung sebagai anggota PAC)
        $this->createMember([
            'full_name' => 'Anggota Tanpa PAC',
            'membership_status' => 'terverifikasi',
            'kecamatan' => 'GAYUNGAN',
            'pac_id' => null,
        ]);

        // 2. Anggota terverifikasi dengan pac_id eksplisit (DIHITUNG)
        $this->createMember([
            'full_name' => 'Anggota Dengan PAC',
            'membership_status' => 'terverifikasi',
            'kecamatan' => 'RUNGKUT',
            'pac_id' => $pac->id,
        ]);

        // 3. Pengurus PAC dengan jabatan aktif pada pac_id meski member.pac_id null (DIHITUNG)
        $pacOfficer = $this->createMember([
            'full_name' => 'Pengurus PAC Aktif',
            'membership_status' => 'pengurus',
            'kecamatan' => 'WONOKROMO',
            'pac_id' => null,
        ]);
        Position::create([
            'member_id' => $pacOfficer->id,
            'position_title' => 'Ketua',
            'level' => 'PAC ISNU',
            'pac_id' => $pac->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // 4. Pengurus PC tanpa pac_id (TIDAK dihitung sebagai anggota PAC, tapi dihitung pengurus & terverifikasi)
        $pcOfficer = $this->createMember([
            'full_name' => 'Pengurus PC Aktif',
            'membership_status' => 'pengurus',
            'kecamatan' => 'SUKOLILO',
            'pac_id' => null,
        ]);
        Position::create([
            'member_id' => $pcOfficer->id,
            'position_title' => 'Sekretaris',
            'level' => 'Kota',
            'pac_id' => null,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // 5. Calon anggota menunggu verifikasi meski punya pac_id (TIDAK dihitung)
        $this->createMember([
            'full_name' => 'Calon Belum Verifikasi',
            'membership_status' => 'menunggu_verifikasi',
            'kecamatan' => 'GAYUNGAN',
            'pac_id' => $pac->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertViewHas('totalMembers', 4);
        $response->assertViewHas('totalPengurus', 2);
        $response->assertViewHas('totalPacMembers', 2);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createMember(array $overrides = []): Member
    {
        $user = User::factory()->create(['role' => 'member']);

        return Member::create(array_merge([
            'user_id' => $user->id,
            'member_number' => 'ISNU-'.fake()->numerify('######'),
            'nik' => '3578'.fake()->numerify('############'),
            'full_name' => fake()->name(),
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Ahmad Yani No. 1',
            'kelurahan' => 'Gayungan',
            'kecamatan' => 'GAYUNGAN',
            'city' => 'KOTA SURABAYA',
            'occupation' => 'Dosen',
            'phone' => '0812'.fake()->numerify('########'),
            'email' => $user->email,
            'membership_status' => 'terverifikasi',
        ], $overrides));
    }
}
