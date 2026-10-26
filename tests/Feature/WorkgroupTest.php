<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use App\Models\Workgroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkgroupTest extends TestCase
{
    use RefreshDatabase;

    private function createTestMember(array $attributes = []): Member
    {
        $user = User::factory()->create();

        return Member::create(array_merge([
            'user_id' => $user->id,
            'member_number' => 'ISNU-'.fake()->numerify('######'),
            'nik' => fake()->numerify('3578############'),
            'full_name' => fake()->name(),
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Masjid Al-Akbar No. 1',
            'kelurahan' => 'Gayungan',
            'kecamatan' => 'Gayungan',
            'city' => 'Kota Surabaya',
            'occupation' => 'Akademisi / Dosen',
            'phone' => '0812'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'membership_status' => 'pengurus',
        ], $attributes));
    }

    public function test_admin_can_access_workgroups_index_and_create_workgroup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.workgroups.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Kelompok Kerja');

        $response = $this->actingAs($admin)->post(route('admin.workgroups.store'), [
            'level' => 'PC ISNU',
            'name' => 'Kelompok Kerja Digitalisasi & AI',
            'code' => 'PKJ-DIGITAL-AI',
            'description' => 'Pokja pengembangan AI dan digitalisasi data',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('workgroups', [
            'name' => 'Kelompok Kerja Digitalisasi & AI',
            'code' => 'PKJ-DIGITAL-AI',
            'level' => 'PC ISNU',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_and_delete_workgroup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $workgroup = Workgroup::create([
            'level' => 'PC ISNU',
            'name' => 'Pokja Beasiswa S3',
            'code' => 'PKJ-S3',
            'description' => 'Fasilitasi beasiswa',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.workgroups.update', $workgroup->id), [
            'level' => 'PC ISNU',
            'name' => 'Pokja Beasiswa dan Riset Akademik',
            'code' => 'PKJ-S3-RISET',
            'description' => 'Fasilitasi beasiswa dan riset',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('workgroups', [
            'id' => $workgroup->id,
            'name' => 'Pokja Beasiswa dan Riset Akademik',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.workgroups.destroy', $workgroup->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('workgroups', [
            'id' => $workgroup->id,
        ]);
    }

    public function test_officer_in_section_can_be_added_to_workgroup_and_retains_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mwc = Mwc::create(['name' => 'MWC Gayungan', 'code' => 'MWC-GYG']);
        $pac = Pac::create(['name' => 'Gayungan', 'code' => 'PAC-GYG', 'mwc_id' => $mwc->id]);

        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Sains dan Teknologi',
        ]);

        $workgroup = Workgroup::create([
            'level' => 'PC ISNU',
            'name' => 'Pokja Transformasi Digital',
            'code' => 'PKJ-DIGITAL',
        ]);

        $member = $this->createTestMember([
            'full_name' => 'Dr. H. Muhammad Farhan, M.Kom.',
            'pac_id' => $pac->id,
        ]);

        // Berikan posisi aktif di Seksi
        Position::create([
            'member_id' => $member->id,
            'position_title' => 'Anggota Seksi Sains dan Teknologi',
            'level' => 'Kota',
            'section_id' => $section->id,
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Pastikan terbukti eligible
        $this->assertTrue($member->fresh()->isEligibleForWorkgroup());

        // Tambahkan ke Pokja
        $response = $this->actingAs($admin)->post(route('admin.workgroups.members.add', $workgroup->id), [
            'member_id' => $member->id,
            'role' => 'Ketua Pokja',
            'notes' => 'Koordinator arsitektur sistem SIKAP ISNU',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('workgroup_members', [
            'workgroup_id' => $workgroup->id,
            'member_id' => $member->id,
            'role' => 'Ketua Pokja',
            'notes' => 'Koordinator arsitektur sistem SIKAP ISNU',
        ]);

        // Verifikasi bahwa anggota masuk di Seksi DAN di Pokja secara bersamaan
        $freshMember = $member->fresh();
        $this->assertEquals($section->id, $freshMember->activePosition->section_id);
        $this->assertTrue($freshMember->workgroups()->where('workgroups.id', $workgroup->id)->exists());
    }

    public function test_member_outside_section_cannot_be_added_to_workgroup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $workgroup = Workgroup::create([
            'level' => 'PC ISNU',
            'name' => 'Pokja Inkubasi Bisnis',
            'code' => 'PKJ-BISNIS',
        ]);

        // 1. Member biasa tanpa status pengurus & tanpa seksi
        $regularMember = $this->createTestMember([
            'full_name' => 'Siti Nurhaliza, S.E.',
            'membership_status' => 'terverifikasi',
        ]);

        $this->assertFalse($regularMember->fresh()->isEligibleForWorkgroup());

        // Percobaan menambahkan member biasa harus gagal
        $response = $this->actingAs($admin)->post(route('admin.workgroups.members.add', $workgroup->id), [
            'member_id' => $regularMember->id,
            'role' => 'Anggota Pokja',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('workgroup_members', [
            'workgroup_id' => $workgroup->id,
            'member_id' => $regularMember->id,
        ]);

        // 2. Pengurus tapi tidak berada di seksi (section_id = null) juga harus ditolak
        $officerWithoutSection = $this->createTestMember([
            'full_name' => 'Drs. KH. Ahmad Fauzi, M.Ag.',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $officerWithoutSection->id,
            'position_title' => 'Ketua Umum',
            'level' => 'Kota',
            'section_id' => null, // Di luar seksi
            'is_active' => true,
        ]);

        $this->assertFalse($officerWithoutSection->fresh()->isEligibleForWorkgroup());

        $response2 = $this->actingAs($admin)->post(route('admin.workgroups.members.add', $workgroup->id), [
            'member_id' => $officerWithoutSection->id,
            'role' => 'Anggota Pokja',
        ]);

        $response2->assertSessionHas('error');
        $this->assertDatabaseMissing('workgroup_members', [
            'workgroup_id' => $workgroup->id,
            'member_id' => $officerWithoutSection->id,
        ]);
    }

    public function test_admin_can_update_member_role_and_remove_from_workgroup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $section = Section::create([
            'level' => 'PC ISNU',
            'name' => 'Seksi Kewirausahaan',
        ]);

        $workgroup = Workgroup::create([
            'level' => 'PC ISNU',
            'name' => 'Pokja Inkubasi Bisnis',
        ]);

        $member = $this->createTestMember([
            'full_name' => 'Rahmat Hidayat, M.M.',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $member->id,
            'position_title' => 'Anggota Seksi Kewirausahaan',
            'level' => 'Kota',
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $workgroup->members()->attach($member->id, [
            'role' => 'Anggota Pokja',
            'notes' => 'Catatan awal',
        ]);

        // Update Peran
        $response = $this->actingAs($admin)->put(route('admin.workgroups.members.update', [$workgroup->id, $member->id]), [
            'role' => 'Sekretaris Pokja',
            'notes' => 'Catatan baru diperbarui',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('workgroup_members', [
            'workgroup_id' => $workgroup->id,
            'member_id' => $member->id,
            'role' => 'Sekretaris Pokja',
            'notes' => 'Catatan baru diperbarui',
        ]);

        // Hapus dari Pokja
        $response = $this->actingAs($admin)->delete(route('admin.workgroups.members.remove', [$workgroup->id, $member->id]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('workgroup_members', [
            'workgroup_id' => $workgroup->id,
            'member_id' => $member->id,
        ]);

        // Posisi Seksi tetap aktif
        $this->assertEquals($section->id, $member->fresh()->activePosition->section_id);
        $this->assertTrue($member->fresh()->activePosition->is_active);
    }
}
