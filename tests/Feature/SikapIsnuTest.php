<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardOrder;
use App\Models\CustomLocation;
use App\Models\Event;
use App\Models\Member;
use App\Models\Pac;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SikapIsnuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_is_accessible()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('SIKAP ISNU');
    }

    public function test_public_member_directory_is_accessible()
    {
        $response = $this->get('/daftar-anggota');
        $response->assertStatus(200);
        $response->assertSee('Katalog Anggota');
    }

    public function test_public_qr_verification_is_accessible()
    {
        $card = Card::first();
        $this->assertNotNull($card);

        $response = $this->get('/verify/'.$card->qr_token);
        $response->assertStatus(200);
        $response->assertSee('KARTU TERVERIFIKASI SAH');
    }

    public function test_member_registration_flow()
    {
        $response = $this->post('/register', [
            'name' => 'Budi Utomo, S.T.',
            'email' => 'budi.utomo@example.com',
            'phone' => '081299998888',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nik' => '3578999900001111',
            'birth_place' => 'Surabaya',
            'birth_date' => '1995-04-10',
            'gender' => 'L',
            'address' => 'Jl. Ketintang No. 10',
            'kelurahan' => 'Ketintang',
            'kecamatan' => 'Gayungan',
            'occupation' => 'Software Developer',
            'education_level' => 'S1',
            'education_institution' => 'ITS Surabaya',
            'education_major' => 'Teknik Informatika',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', ['email' => 'budi.utomo@example.com', 'is_active' => false]);
        $this->assertDatabaseHas('members', ['nik' => '3578999900001111', 'membership_status' => 'menunggu_verifikasi']);

        $user = User::where('email', 'budi.utomo@example.com')->first();
        $this->assertNotNull($user->activation_token);

        $activateResponse = $this->get('/activate/'.$user->activation_token);
        $activateResponse->assertRedirect('/member/dashboard');

        $user->refresh();
        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->activation_token);
    }

    public function test_admin_verification_approves_applicant()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $pendingMember = Member::where('membership_status', 'menunggu_verifikasi')->first();

        $response = $this->actingAs($admin)
            ->post('/admin/verifikasi/'.$pendingMember->id.'/approve');

        $response->assertRedirect('/admin/verifikasi');
        $this->assertDatabaseHas('members', [
            'id' => $pendingMember->id,
            'membership_status' => 'terverifikasi',
        ]);
        $this->assertDatabaseHas('cards', [
            'member_id' => $pendingMember->id,
            'card_type' => 'MEMBER',
            'is_active' => true,
        ]);
    }

    public function test_admin_promotes_member_to_officer()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::where('membership_status', 'terverifikasi')->first();

        $response = $this->actingAs($admin)
            ->post('/admin/pengurus/'.$member->id.'/promote', [
                'position_title' => 'Wakil Ketua',
                'level' => 'Kota',
                'period' => '2026-2030',
                'sk_number' => 'SK-001/2026',
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'membership_status' => 'pengurus',
        ]);
        $this->assertDatabaseHas('cards', [
            'member_id' => $member->id,
            'card_type' => 'OFFICER',
        ]);
    }

    public function test_admin_can_edit_member_profile()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::first();

        $response = $this->actingAs($admin)
            ->get('/admin/anggota/'.$member->id.'/edit');

        $response->assertStatus(200);
        $response->assertSee('Edit Profil Anggota');

        $updateResponse = $this->actingAs($admin)
            ->put('/admin/anggota/'.$member->id, [
                'full_name' => 'Updated Name Admin',
                'nik' => '3578999900002222',
                'email' => 'updated.email@example.com',
                'phone' => '08123456789',
                'occupation' => 'Dosen Pengajar',
                'membership_status' => 'terverifikasi',
                'birth_place' => 'Surabaya',
                'birth_date' => '1990-01-01',
                'gender' => 'L',
                'address' => 'Jl. Pemuda No. 1',
                'province' => 'JAWA TIMUR',
                'city' => 'KOTA SURABAYA',
                'kecamatan' => 'Gubeng',
                'kelurahan' => 'Airlangga',
            ]);

        $updateResponse->assertRedirect('/admin/anggota');
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'Updated Name Admin',
            'email' => 'updated.email@example.com',
        ]);
    }

    public function test_admin_can_soft_delete_and_restore_member()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::latest()->first();
        $memberId = $member->id;

        // 1. Soft Delete
        $response = $this->actingAs($admin)
            ->delete('/admin/anggota/'.$memberId);

        $response->assertRedirect('/admin/anggota');

        // Member is soft-deleted (deleted_at is not null)
        $this->assertSoftDeleted('members', ['id' => $memberId]);

        // 2. View Trash Page
        $trashResponse = $this->actingAs($admin)
            ->get('/admin/sampah');
        $trashResponse->assertStatus(200);
        $trashResponse->assertSee($member->full_name);

        // 3. Restore Member
        $restoreResponse = $this->actingAs($admin)
            ->post('/admin/sampah/'.$memberId.'/restore');

        $restoreResponse->assertRedirect('/admin/sampah');
        $this->assertDatabaseHas('members', [
            'id' => $memberId,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_force_delete_member()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::latest()->first();
        $memberId = $member->id;

        // Soft delete first
        $member->delete();
        $this->assertSoftDeleted('members', ['id' => $memberId]);

        // Force delete
        $forceResponse = $this->actingAs($admin)
            ->delete('/admin/sampah/'.$memberId.'/force-delete');

        $forceResponse->assertRedirect('/admin/sampah');
        $this->assertDatabaseMissing('members', ['id' => $memberId]);
    }

    public function test_admin_can_view_presensi_rekap_and_kader_detail()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::first();

        $rekapResponse = $this->actingAs($admin)->get('/admin/presensi/rekap');
        $rekapResponse->assertStatus(200);
        $rekapResponse->assertSee('Rekap Tingkat Kehadiran Kegiatan');

        $detailResponse = $this->actingAs($admin)->get('/admin/presensi/detail-kader/'.$member->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_user_crud()
    {
        $admin = User::where('role', 'admin_kota')->first();

        // 1. List users
        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Akun User');

        // 2. Create User
        $createResponse = $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Testing User CRUD',
            'email' => 'testusercrud@isnusurabaya.or.id',
            'phone' => '089988776655',
            'role' => 'admin_pac',
            'password' => 'secret12345',
            'is_active' => true,
        ]);

        $createResponse->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'testusercrud@isnusurabaya.or.id']);

        $newUser = User::where('email', 'testusercrud@isnusurabaya.or.id')->first();

        // 3. Update User
        $updateResponse = $this->actingAs($admin)->putJson('/admin/users/'.$newUser->id, [
            'name' => 'Testing User CRUD Updated',
            'email' => 'testusercrud@isnusurabaya.or.id',
            'phone' => '081234567899',
            'role' => 'admin_kota',
            'is_active' => true,
        ]);

        $updateResponse->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['name' => 'Testing User CRUD Updated', 'role' => 'admin_kota']);

        // 4. Toggle Status
        $toggleResponse = $this->actingAs($admin)->patchJson('/admin/users/'.$newUser->id.'/toggle-status');
        $toggleResponse->assertStatus(200)->assertJson(['success' => true, 'is_active' => false]);
        $this->assertDatabaseHas('users', ['id' => $newUser->id, 'is_active' => false]);

        // 5. Delete User
        $deleteResponse = $this->actingAs($admin)->deleteJson('/admin/users/'.$newUser->id);
        $deleteResponse->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSoftDeleted('users', ['id' => $newUser->id]);
    }

    public function test_admin_pac_scoped_permissions()
    {
        $pac1 = Pac::first();
        $pac2 = Pac::skip(1)->first();

        $adminPacUser = User::create([
            'name' => 'Admin PAC Rungkut',
            'email' => 'adminpac1@example.com',
            'phone' => '081122334455',
            'role' => 'admin_pac',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $adminPacMember = Member::create([
            'user_id' => $adminPacUser->id,
            'full_name' => 'Admin PAC Rungkut',
            'nik' => '3578010101010001',
            'phone' => '081122334455',
            'email' => 'adminpac1@example.com',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Rungkut No. 1',
            'kecamatan' => $pac1->name,
            'kelurahan' => 'Rungkut',
            'occupation' => 'Dosen',
            'pac_id' => $pac1->id,
            'membership_status' => 'pengurus',
        ]);

        $user1 = User::create([
            'name' => 'Member PAC 1',
            'email' => 'memberpac1@example.com',
            'phone' => '081122334456',
            'role' => 'member',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $memberInPac1 = Member::create([
            'user_id' => $user1->id,
            'full_name' => 'Member PAC 1',
            'nik' => '3578010101010002',
            'phone' => '081122334456',
            'email' => 'memberpac1@example.com',
            'birth_place' => 'Surabaya',
            'birth_date' => '1992-01-01',
            'gender' => 'L',
            'address' => 'Jl. Rungkut No. 2',
            'kecamatan' => $pac1->name,
            'kelurahan' => 'Rungkut',
            'occupation' => 'Guru',
            'pac_id' => $pac1->id,
            'membership_status' => 'terverifikasi',
        ]);

        $user2 = User::create([
            'name' => 'Member PAC 2',
            'email' => 'memberpac2@example.com',
            'phone' => '081122334457',
            'role' => 'member',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $memberInPac2 = Member::create([
            'user_id' => $user2->id,
            'full_name' => 'Member PAC 2',
            'nik' => '3578010101010003',
            'phone' => '081122334457',
            'email' => 'memberpac2@example.com',
            'birth_place' => 'Surabaya',
            'birth_date' => '1993-01-01',
            'gender' => 'P',
            'address' => 'Jl. Wonokromo No. 1',
            'kecamatan' => $pac2->name,
            'kelurahan' => 'Wonokromo',
            'occupation' => 'Pengusaha',
            'pac_id' => $pac2->id,
            'membership_status' => 'terverifikasi',
        ]);

        // Admin PAC can view member in their own PAC
        $response1 = $this->actingAs($adminPacUser)->get('/admin/anggota/'.$memberInPac1->id);
        $response1->assertStatus(200);

        // Admin PAC cannot view/manage member outside their PAC (gets 403)
        $response2 = $this->actingAs($adminPacUser)->get('/admin/anggota/'.$memberInPac2->id);
        $response2->assertStatus(403);
    }

    public function test_admin_location_duplicate_validation_and_deletion()
    {
        $admin = User::where('role', 'admin_kota')->first();

        // 1. Add valid custom location
        $response = $this->actingAs($admin)->post('/admin/locations', [
            'level' => 'province',
            'code' => '99',
            'name' => 'PROVINSI BARU CUSTOM',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('custom_locations', ['code' => '99', 'name' => 'PROVINSI BARU CUSTOM']);

        // 2. Try adding duplicate code (should fail)
        $dupCodeResponse = $this->actingAs($admin)->post('/admin/locations', [
            'level' => 'province',
            'code' => '99',
            'name' => 'NAMA LAIN',
        ]);
        $dupCodeResponse->assertSessionHasErrors(['code']);

        // 3. Try adding duplicate name (should fail)
        $dupNameResponse = $this->actingAs($admin)->post('/admin/locations', [
            'level' => 'province',
            'code' => '98',
            'name' => 'PROVINSI BARU CUSTOM',
        ]);
        $dupNameResponse->assertSessionHasErrors(['name']);

        // 4. Delete location
        $loc = CustomLocation::where('code', '99')->first();
        $delResponse = $this->actingAs($admin)->delete('/admin/locations/'.$loc->id);
        $delResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('custom_locations', ['id' => $loc->id]);
    }

    public function test_member_can_submit_card_order_via_ajax()
    {
        Storage::fake('public');
        $verifiedMember = Member::where('membership_status', 'terverifikasi')->first();
        $user = $verifiedMember->user;

        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($user)
            ->postJson('/member/card-order', [
                'shipping_address' => 'Jl. Mawar No. 123',
                'phone' => '081234567890',
                'notes' => 'Catatan tes',
                'payment_proof' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('card_orders', [
            'member_id' => $verifiedMember->id,
            'status' => 'pending',
            'shipping_address' => 'Jl. Mawar No. 123',
        ]);

        // Duplicate active order check via AJAX
        $dupResponse = $this->actingAs($user)
            ->postJson('/member/card-order', [
                'shipping_address' => 'Jl. Mawar No. 123',
                'phone' => '081234567890',
                'payment_proof' => $file,
            ]);

        $dupResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_member_can_confirm_card_receipt_and_reorder()
    {
        Storage::fake('public');
        $verifiedMember = Member::where('membership_status', 'terverifikasi')->first();
        $user = $verifiedMember->user;

        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 200, 'image/jpeg');

        // 1. Initial Order
        $orderResponse = $this->actingAs($user)->postJson('/member/card-order', [
            'shipping_address' => 'Jl. Mawar No. 123',
            'phone' => '081234567890',
            'payment_proof' => $file,
        ]);
        $orderResponse->assertStatus(200);

        $order = CardOrder::where('member_id', $verifiedMember->id)->latest()->first();
        $this->assertEquals('pending', $order->status);

        // 2. Member confirms receipt
        $receiveResponse = $this->actingAs($user)->postJson('/member/card-order/'.$order->id.'/receive');
        $receiveResponse->assertStatus(200)->assertJson(['success' => true]);

        $order->refresh();
        $this->assertEquals('received', $order->status);
        $this->assertNotNull($order->received_at);

        // 3. After received, member can order again!
        $reorderResponse = $this->actingAs($user)->postJson('/member/card-order', [
            'shipping_address' => 'Jl. Anggrek No. 456',
            'phone' => '081299990000',
            'payment_proof' => $file,
        ]);
        $reorderResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(2, CardOrder::where('member_id', $verifiedMember->id)->count());
    }

    public function test_admin_can_delete_card_order()
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin_kota')->first();
        $verifiedMember = Member::where('membership_status', 'terverifikasi')->first();

        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 200, 'image/jpeg');
        $path = $file->store('payment_proofs', 'public');

        $order = CardOrder::create([
            'member_id' => $verifiedMember->id,
            'status' => 'pending',
            'shipping_address' => 'Jl. Mawar No. 123',
            'phone' => '081234567890',
            'payment_proof' => $path,
            'ordered_at' => now(),
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($admin)
            ->deleteJson('/admin/card-orders/'.$order->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data pemesanan kartu fisik berhasil dihapus.',
            ]);

        $this->assertDatabaseMissing('card_orders', [
            'id' => $order->id,
        ]);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_demote_officer_to_regular_member()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $officerMember = Member::where('membership_status', 'pengurus')->first();

        $response = $this->actingAs($admin)
            ->post('/admin/pengurus/'.$officerMember->id.'/demote');

        $response->assertRedirect();

        $officerMember->refresh();
        $this->assertEquals('terverifikasi', $officerMember->membership_status);

        $activeCard = $officerMember->activeCard;
        if ($activeCard) {
            $this->assertEquals('MEMBER', $activeCard->card_type);
        }

        $this->assertDatabaseHas('membership_status_histories', [
            'member_id' => $officerMember->id,
            'status_from' => 'pengurus',
            'status_to' => 'terverifikasi',
        ]);
    }

    public function test_registration_allows_reusing_soft_deleted_data()
    {
        $member = Member::first();
        $user = $member->user;
        $email = $user->email;
        $phone = $user->phone;
        $nik = $member->nik;

        // Soft delete user and member
        $user->delete();
        $member->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSoftDeleted('members', ['id' => $member->id]);

        // Attempt new registration with the trashed email, phone, and NIK
        $response = $this->post('/register', [
            'name' => 'Member Baru',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nik' => $nik,
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Rungkut Asri No. 5',
            'kelurahan' => 'Rungkut',
            'kecamatan' => 'Rungkut',
            'occupation' => 'Jurnalis',
            'education_level' => 'S1',
            'education_institution' => 'UNAIR Surabaya',
            'education_major' => 'Ilmu Komunikasi',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', ['email' => $email, 'phone' => $phone, 'name' => 'Member Baru']);
    }

    public function test_member_can_change_password()
    {
        $user = User::where('role', 'member')->first();
        $this->actingAs($user);

        $response = $this->post('/member/profile/change-password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_admin_can_change_password()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $this->actingAs($admin);

        $response = $this->post('/admin/profile/change-password', [
            'current_password' => 'password',
            'password' => 'newadminpassword123',
            'password_confirmation' => 'newadminpassword123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newadminpassword123', $admin->fresh()->password));
    }

    public function test_eligible_members_excludes_active_pengurus()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $officerMember = Member::where('membership_status', 'pengurus')->first();
        $regularMember = Member::where('membership_status', 'terverifikasi')->whereDoesntHave('activePosition')->first();

        $response = $this->actingAs($admin)->get('/admin/pengurus');
        $response->assertStatus(200);

        $eligibleMembers = $response->viewData('eligibleMembers');
        $this->assertFalse($eligibleMembers->contains('id', $officerMember->id));
        $this->assertTrue($eligibleMembers->contains('id', $regularMember->id));
    }

    public function test_login_redirects_back_to_presence_page_when_coming_from_presence()
    {
        $event = Event::firstOrCreate(
            ['unique_code' => 'TEST-PRESENCE-CODE'],
            [
                'title' => 'Kegiatan Uji Presensi',
                'event_date' => now(),
                'start_time' => '08:00',
                'end_time' => '12:00',
                'method' => 'luring',
                'location' => 'Surabaya',
                'presence_start_at' => now()->subHour(),
                'presence_end_at' => now()->addHour(),
                'created_by' => 1,
            ]
        );

        $presenceUrl = route('event.presence.show', $event->unique_code);

        $loginPageResponse = $this->get('/login?redirect='.urlencode($presenceUrl));
        $loginPageResponse->assertStatus(200);
        $this->assertEquals($presenceUrl, session('url.intended'));

        $memberUser = User::where('role', 'member')->first();

        $response = $this->post('/login', [
            'email' => $memberUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect($presenceUrl);
    }
}
