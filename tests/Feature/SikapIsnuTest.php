<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardOrder;
use App\Models\CustomLocation;
use App\Models\Event;
use App\Models\Letter;
use App\Models\Member;
use App\Models\Mwc;
use App\Models\Pac;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
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
        $response->assertSee('Dr. H. Ahmad Fauzi, S.T., M.Kom.');
        $response->assertSee('ISNU-SBY-26-000001');
        $response->assertSee('dummy_member_photo.jpg');
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

        $showRes = $this->actingAs($admin)->get(route('admin.anggota.show', $member->id));
        $showRes->assertStatus(200);
        $showRes->assertSee(':disabled="submitting"', false);
        $showRes->assertSee('spinner-border', false);

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

    public function test_officer_position_dropdown_options_and_filter()
    {
        $admin = User::where('role', 'admin_kota')->first();
        $member = Member::where('membership_status', 'terverifikasi')->first();

        $responseAnggota = $this->actingAs($admin)->get(route('admin.anggota.show', $member->id));
        $responseAnggota->assertStatus(200);

        foreach (Position::POSITION_TITLES as $positionTitle) {
            $responseAnggota->assertSee($positionTitle);
        }

        $responsePengurus = $this->actingAs($admin)->get(route('admin.pengurus.index'));
        $responsePengurus->assertStatus(200);

        foreach (Position::POSITION_TITLES as $positionTitle) {
            $responsePengurus->assertSee($positionTitle);
        }

        $responseFilter = $this->actingAs($admin)->get(route('admin.pengurus.index', ['position_title' => 'Ketua']));
        $responseFilter->assertStatus(200);
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

    public function test_admin_can_preview_rekap_potensi_pdf_stream()
    {
        $admin = User::where('role', 'admin_kota')->first() ?? User::where('role', 'super_admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.anggota.rekap_pdf', ['stream' => 1]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_download_rekap_potensi_pdf()
    {
        $admin = User::where('role', 'admin_kota')->first() ?? User::where('role', 'super_admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.anggota.rekap_pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Rekap_Potensi_Kader_ISNU_', $response->headers->get('content-disposition'));
    }

    public function test_database_anggota_page_contains_download_rekap_pdf_button_and_modal()
    {
        $admin = User::where('role', 'admin_kota')->first() ?? User::where('role', 'super_admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.anggota.index'));

        $response->assertStatus(200);
        $response->assertSee('Download Rekap PDF');
        $response->assertSee('modalPreviewRekapPdf');
        $response->assertSee('iframeRekapPdf');
    }

    public function test_ketua_and_sekretaris_can_manage_members_and_officers()
    {
        $targetMember = Member::where('membership_status', 'terverifikasi')->first();

        // Setup user as Ketua PC ISNU
        $ketuaUser = User::create([
            'name' => 'Ketua PC ISNU',
            'email' => 'ketua@isnu.test',
            'phone' => '081234500001',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'full_name' => 'Ketua PC ISNU',
            'nik' => '3578000000000001',
            'email' => 'ketua@isnu.test',
            'phone' => '081234500001',
            'occupation' => 'Dosen',
            'birth_place' => 'Surabaya',
            'birth_date' => '1980-01-01',
            'gender' => 'L',
            'address' => 'Jl. Ketintang No. 1',
            'kelurahan' => 'Ketintang',
            'kecamatan' => 'Gayungan',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $ketuaMember->id,
            'position_title' => 'Ketua',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        $this->assertTrue($ketuaUser->fresh()->canManageMembersAndOfficers());
        $this->assertTrue($ketuaUser->fresh()->canManageMember($targetMember));

        // Ketua can promote an eligible member to officer
        $promoteResponse = $this->actingAs($ketuaUser)->post('/admin/pengurus/'.$targetMember->id.'/promote', [
            'position_title' => 'Bendahara',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
        ]);
        $promoteResponse->assertRedirect();
        $this->assertEquals('pengurus', $targetMember->fresh()->membership_status);

        // Ketua can update member data
        $updateResponse = $this->actingAs($ketuaUser)->put('/admin/anggota/'.$targetMember->id, [
            'full_name' => 'Nama Baru Diupdate Ketua',
            'email' => $targetMember->email,
            'phone' => $targetMember->phone,
            'occupation' => $targetMember->occupation,
            'birth_place' => $targetMember->birth_place,
            'birth_date' => $targetMember->birth_date->format('Y-m-d'),
            'gender' => $targetMember->gender,
            'address' => $targetMember->address,
            'kelurahan' => $targetMember->kelurahan,
            'kecamatan' => $targetMember->kecamatan,
            'membership_status' => 'pengurus',
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals('Nama Baru Diupdate Ketua', $targetMember->fresh()->full_name);

        // Ketua can demote officer
        $demoteResponse = $this->actingAs($ketuaUser)->post('/admin/pengurus/'.$targetMember->id.'/demote');
        $demoteResponse->assertRedirect();
        $this->assertEquals('terverifikasi', $targetMember->fresh()->membership_status);
    }

    public function test_non_manager_officers_can_only_view_and_cannot_manage_members_or_officers()
    {
        $targetMember = Member::where('membership_status', 'terverifikasi')->first();

        // Setup user as Bendahara PC ISNU
        $bendaharaUser = User::create([
            'name' => 'Bendahara PC ISNU',
            'email' => 'bendahara@isnu.test',
            'phone' => '081234500002',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $bendaharaMember = Member::create([
            'user_id' => $bendaharaUser->id,
            'full_name' => 'Bendahara PC ISNU',
            'nik' => '3578000000000002',
            'email' => 'bendahara@isnu.test',
            'phone' => '081234500002',
            'occupation' => 'Akuntan',
            'birth_place' => 'Surabaya',
            'birth_date' => '1985-05-05',
            'gender' => 'P',
            'address' => 'Jl. Gubeng No. 2',
            'kelurahan' => 'Gubeng',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $bendaharaMember->id,
            'position_title' => 'Bendahara',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        $this->assertFalse($bendaharaUser->fresh()->canManageMembersAndOfficers());
        $this->assertFalse($bendaharaUser->fresh()->canManageMember($targetMember));
        $this->assertTrue($bendaharaUser->fresh()->canViewMember($targetMember));

        // 1. Read-only access: CAN view index and show pages
        $this->actingAs($bendaharaUser)->get('/admin/anggota')->assertStatus(200);
        $this->actingAs($bendaharaUser)->get('/admin/anggota/'.$targetMember->id)->assertStatus(200);
        $this->actingAs($bendaharaUser)->get('/admin/pengurus')->assertStatus(200);
        $this->actingAs($bendaharaUser)->get('/admin/verifikasi')->assertStatus(200);
        $this->actingAs($bendaharaUser)->get('/admin/anggota/rekap-pdf')->assertStatus(200);

        // In admin anggota index, edit and delete buttons are hidden
        $indexResponse = $this->actingAs($bendaharaUser)->get('/admin/anggota');
        $indexResponse->assertDontSee('/admin/anggota/'.$targetMember->id.'/edit');

        // In pengurus index, Tetapkan Pengurus Baru button is hidden
        $pengurusResponse = $this->actingAs($bendaharaUser)->get('/admin/pengurus');
        $pengurusResponse->assertDontSee('Tetapkan Pengurus Baru');

        // 2. Action mutation: BLOCKED with 403 Forbidden
        // Attempt update member
        $this->actingAs($bendaharaUser)->put('/admin/anggota/'.$targetMember->id, [
            'full_name' => 'Nama Ditolak',
            'email' => $targetMember->email,
            'phone' => $targetMember->phone,
            'occupation' => $targetMember->occupation,
            'birth_place' => $targetMember->birth_place,
            'birth_date' => $targetMember->birth_date->format('Y-m-d'),
            'gender' => $targetMember->gender,
            'address' => $targetMember->address,
            'kelurahan' => $targetMember->kelurahan,
            'kecamatan' => $targetMember->kecamatan,
            'membership_status' => 'terverifikasi',
        ])->assertStatus(403);

        // Attempt delete member
        $this->actingAs($bendaharaUser)->delete('/admin/anggota/'.$targetMember->id)->assertStatus(403);

        // Attempt promote officer
        $this->actingAs($bendaharaUser)->post('/admin/pengurus/'.$targetMember->id.'/promote', [
            'position_title' => 'Koordinator Seksi',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
        ])->assertStatus(403);

        // Attempt demote officer
        $this->actingAs($bendaharaUser)->post('/admin/pengurus/'.$targetMember->id.'/demote')->assertStatus(403);

        // Attempt verify account
        $this->actingAs($bendaharaUser)->post('/admin/verifikasi/'.$targetMember->id.'/verify-account')->assertStatus(403);

        // Attempt approve verification
        $this->actingAs($bendaharaUser)->post('/admin/verifikasi/'.$targetMember->id.'/approve')->assertStatus(403);
    }

    public function test_location_master_is_only_accessible_to_superadmin_and_admin_pc()
    {
        $admin = User::where('role', 'admin_kota')->first();

        // 1. Admin PC can access and see menu
        $responseAdmin = $this->actingAs($admin)->get('/admin/locations');
        $responseAdmin->assertStatus(200);

        $responseDashboardAdmin = $this->actingAs($admin)->get('/admin/dashboard');
        $responseDashboardAdmin->assertSee('Master Lokasi');

        // 2. Setup Ketua PC ISNU
        $ketuaUser = User::create([
            'name' => 'Ketua Test Lokasi',
            'email' => 'ketua_lokasi@isnu.test',
            'phone' => '081234500010',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $ketuaMember = Member::create([
            'user_id' => $ketuaUser->id,
            'full_name' => 'Ketua Test Lokasi',
            'nik' => '3578000000000010',
            'email' => 'ketua_lokasi@isnu.test',
            'phone' => '081234500010',
            'occupation' => 'Dosen',
            'birth_place' => 'Surabaya',
            'birth_date' => '1980-01-01',
            'gender' => 'L',
            'address' => 'Surabaya',
            'kelurahan' => 'Rungkut',
            'kecamatan' => 'Rungkut',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $ketuaMember->id,
            'position_title' => 'Ketua',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Ketua cannot access Master Lokasi
        $this->actingAs($ketuaUser)->get('/admin/locations')->assertStatus(403);
        $this->actingAs($ketuaUser)->post('/admin/locations', [
            'level' => 'kecamatan',
            'code' => 'TEST-KEC',
            'name' => 'Kecamatan Test',
        ])->assertStatus(403);

        // Ketua cannot see Master Lokasi in sidebar
        $responseDashboardKetua = $this->actingAs($ketuaUser)->get('/admin/dashboard');
        $responseDashboardKetua->assertDontSee('Master Lokasi');
    }

    public function test_pac_seksi_and_trash_masters_accessible_to_superadmin_admin_pc_ketua_sekretaris_only()
    {
        $admin = User::where('role', 'admin_kota')->first();

        // 1. Admin PC can access Master PAC, Seksi, Sampah and see menus
        $this->actingAs($admin)->get('/admin/mwc')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/sections')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/sampah')->assertStatus(200);

        $dashAdmin = $this->actingAs($admin)->get('/admin/dashboard');
        $dashAdmin->assertSee('Master PAC ISNU');
        $dashAdmin->assertSee('Master Seksi');
        $dashAdmin->assertSee('Data Sampah');

        // 2. Setup Sekretaris PC ISNU
        $sekretarisUser = User::create([
            'name' => 'Sekretaris Test',
            'email' => 'sekretaris_test@isnu.test',
            'phone' => '081234500011',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $sekretarisMember = Member::create([
            'user_id' => $sekretarisUser->id,
            'full_name' => 'Sekretaris Test',
            'nik' => '3578000000000011',
            'email' => 'sekretaris_test@isnu.test',
            'phone' => '081234500011',
            'occupation' => 'Peneliti',
            'birth_place' => 'Surabaya',
            'birth_date' => '1982-02-02',
            'gender' => 'L',
            'address' => 'Surabaya',
            'kelurahan' => 'Gubeng',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $sekretarisMember->id,
            'position_title' => 'Sekretaris',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Sekretaris can access Master PAC, Seksi, Sampah and see menus
        $this->actingAs($sekretarisUser)->get('/admin/mwc')->assertStatus(200);
        $this->actingAs($sekretarisUser)->get('/admin/sections')->assertStatus(200);
        $this->actingAs($sekretarisUser)->get('/admin/sampah')->assertStatus(200);

        $dashSekretaris = $this->actingAs($sekretarisUser)->get('/admin/dashboard');
        $dashSekretaris->assertSee('Master PAC ISNU');
        $dashSekretaris->assertSee('Master Seksi');
        $dashSekretaris->assertSee('Data Sampah');

        // 3. Setup Wakil Ketua PC ISNU (Other officer)
        $wakilUser = User::create([
            'name' => 'Wakil Ketua Test',
            'email' => 'wakil_test@isnu.test',
            'phone' => '081234500012',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $wakilMember = Member::create([
            'user_id' => $wakilUser->id,
            'full_name' => 'Wakil Ketua Test',
            'nik' => '3578000000000012',
            'email' => 'wakil_test@isnu.test',
            'phone' => '081234500012',
            'occupation' => 'Wiraswasta',
            'birth_place' => 'Surabaya',
            'birth_date' => '1983-03-03',
            'gender' => 'L',
            'address' => 'Surabaya',
            'kelurahan' => 'Wonokromo',
            'kecamatan' => 'Wonokromo',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $wakilMember->id,
            'position_title' => 'Wakil Ketua',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Wakil Ketua cannot access Master PAC, Seksi, Sampah
        $this->actingAs($wakilUser)->get('/admin/mwc')->assertStatus(403);
        $this->actingAs($wakilUser)->get('/admin/sections')->assertStatus(403);
        $this->actingAs($wakilUser)->get('/admin/sampah')->assertStatus(403);

        // Wakil Ketua cannot see these menus in sidebar
        $dashWakil = $this->actingAs($wakilUser)->get('/admin/dashboard');
        $dashWakil->assertDontSee('Master PAC ISNU');
        $dashWakil->assertDontSee('Master Seksi');
        $dashWakil->assertDontSee('Data Sampah');
    }

    public function test_card_orders_accessible_to_superadmin_admin_pc_ketua_sekretaris_bendahara_only()
    {
        $admin = User::where('role', 'admin_kota')->first();

        // 1. Admin PC can access and see Kartu Fisik menu
        $this->actingAs($admin)->get('/admin/card-orders')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/dashboard')->assertSee('Kartu Fisik');

        // 2. Setup Bendahara PC ISNU
        $bendaharaUser = User::create([
            'name' => 'Bendahara Kartu Test',
            'email' => 'bendahara_card@isnu.test',
            'phone' => '081234500020',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $bendaharaMember = Member::create([
            'user_id' => $bendaharaUser->id,
            'full_name' => 'Bendahara Kartu Test',
            'nik' => '3578000000000020',
            'email' => 'bendahara_card@isnu.test',
            'phone' => '081234500020',
            'occupation' => 'Bankir',
            'birth_place' => 'Surabaya',
            'birth_date' => '1988-08-08',
            'gender' => 'P',
            'address' => 'Surabaya',
            'kelurahan' => 'Sukolilo',
            'kecamatan' => 'Sukolilo',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $bendaharaMember->id,
            'position_title' => 'Bendahara',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Bendahara can access Kartu Fisik and see menu in sidebar
        $this->actingAs($bendaharaUser)->get('/admin/card-orders')->assertStatus(200);
        $this->actingAs($bendaharaUser)->get('/admin/dashboard')->assertSee('Kartu Fisik');

        // 3. Setup Wakil Sekretaris PC ISNU
        $wakilSekretarisUser = User::create([
            'name' => 'Wakil Sekretaris Test',
            'email' => 'wakil_sek@isnu.test',
            'phone' => '081234500021',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $wakilSekretarisMember = Member::create([
            'user_id' => $wakilSekretarisUser->id,
            'full_name' => 'Wakil Sekretaris Test',
            'nik' => '3578000000000021',
            'email' => 'wakil_sek@isnu.test',
            'phone' => '081234500021',
            'occupation' => 'Guru',
            'birth_place' => 'Surabaya',
            'birth_date' => '1989-09-09',
            'gender' => 'L',
            'address' => 'Surabaya',
            'kelurahan' => 'Tegalsari',
            'kecamatan' => 'Tegalsari',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $wakilSekretarisMember->id,
            'position_title' => 'Wakil Sekretaris',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        // Wakil Sekretaris cannot access Kartu Fisik and does not see menu
        $this->actingAs($wakilSekretarisUser)->get('/admin/card-orders')->assertStatus(403);
        $this->actingAs($wakilSekretarisUser)->get('/admin/dashboard')->assertDontSee('Kartu Fisik');
    }

    public function test_member_can_view_own_profile_and_dashboard_but_cannot_access_admin_pages()
    {
        $memberUser = User::create([
            'name' => 'Anggota Biasa Satu',
            'email' => 'anggota_biasa_1@isnu.test',
            'phone' => '089911223301',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $member = Member::create([
            'user_id' => $memberUser->id,
            'full_name' => 'Anggota Biasa Satu',
            'nik' => '3578010101010001',
            'email' => 'anggota_biasa_1@isnu.test',
            'phone' => '089911223301',
            'occupation' => 'Arsitek',
            'birth_place' => 'Surabaya',
            'birth_date' => '1992-02-02',
            'gender' => 'L',
            'address' => 'Jl. Gubeng No. 1',
            'kelurahan' => 'Gubeng',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'terverifikasi',
        ]);

        $otherUser = User::create([
            'name' => 'Anggota Biasa Dua',
            'email' => 'anggota_biasa_2@isnu.test',
            'phone' => '089911223302',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $otherMember = Member::create([
            'user_id' => $otherUser->id,
            'full_name' => 'Anggota Biasa Dua',
            'nik' => '3578010101010002',
            'email' => 'anggota_biasa_2@isnu.test',
            'phone' => '089911223302',
            'occupation' => 'Dokter',
            'birth_place' => 'Surabaya',
            'birth_date' => '1993-03-03',
            'gender' => 'P',
            'address' => 'Jl. Rungkut No. 2',
            'kelurahan' => 'Rungkut',
            'kecamatan' => 'Rungkut',
            'membership_status' => 'terverifikasi',
        ]);

        // Member can view own profile & dashboard
        $response = $this->actingAs($memberUser)->get('/member/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Anggota Biasa Satu');
        $response->assertSee('Profil Saya');
        $response->assertSee('Katalog Anggota');

        // Member is blocked from all admin routes
        $this->actingAs($memberUser)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($memberUser)->get('/admin/anggota')->assertStatus(403);
        $this->actingAs($memberUser)->get('/admin/anggota/'.$otherMember->id)->assertStatus(403);
        $this->actingAs($memberUser)->get('/admin/verifikasi')->assertStatus(403);
        $this->actingAs($memberUser)->get('/admin/card-orders')->assertStatus(403);
        $this->actingAs($memberUser)->get('/admin/locations')->assertStatus(403);

        // Verification of canViewMember and canManageMember logic
        $this->assertTrue($memberUser->canViewMember($member));
        $this->assertFalse($memberUser->canViewMember($otherMember));
        $this->assertFalse($memberUser->canManageMember($otherMember));
    }

    public function test_member_can_edit_and_add_to_own_profile()
    {
        $memberUser = User::create([
            'name' => 'Kader Kreatif',
            'email' => 'kader_kreatif@isnu.test',
            'phone' => '089911223303',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $member = Member::create([
            'user_id' => $memberUser->id,
            'full_name' => 'Kader Kreatif',
            'nik' => '3578010101010003',
            'email' => 'kader_kreatif@isnu.test',
            'phone' => '089911223303',
            'occupation' => 'Desainer Grafis',
            'birth_place' => 'Surabaya',
            'birth_date' => '1994-04-04',
            'gender' => 'L',
            'address' => 'Jl. Wonokromo No. 3',
            'kelurahan' => 'Sawunggaling',
            'kecamatan' => 'Wonokromo',
            'membership_status' => 'terverifikasi',
        ]);

        // Edit profile page is accessible
        $this->actingAs($memberUser)->get('/member/profile/edit')->assertStatus(200)->assertSee('Edit Biodata');

        // Member can update their own biodata
        $updateResponse = $this->actingAs($memberUser)->post('/member/profile/update', [
            'full_name' => 'Kader Kreatif, S.Ds.',
            'birth_place' => 'Surabaya',
            'birth_date' => '1994-04-04',
            'gender' => 'L',
            'address' => 'Jl. Wonokromo Baru No. 3A',
            'kelurahan' => 'Sawunggaling',
            'kecamatan' => 'Wonokromo',
            'occupation' => 'Senior Product Designer',
        ]);
        $updateResponse->assertRedirect(route('member.dashboard'));
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'Kader Kreatif, S.Ds.',
            'occupation' => 'Senior Product Designer',
        ]);

        // Member can add education to own profile
        $eduResponse = $this->actingAs($memberUser)->post('/member/profile/education', [
            'level' => 'S1',
            'institution_name' => 'Institut Teknologi Sepuluh Nopember',
            'major' => 'Desain Komunikasi Visual',
            'degree' => 'S.Ds.',
            'start_year' => 2012,
            'end_year' => 2016,
        ]);
        $eduResponse->assertRedirect();
        $this->assertDatabaseHas('member_educations', [
            'member_id' => $member->id,
            'institution_name' => 'Institut Teknologi Sepuluh Nopember',
        ]);

        // Member can add organization to own profile
        $orgResponse = $this->actingAs($memberUser)->post('/member/profile/organization', [
            'organization_name' => 'PMII Rayon Desain',
            'position' => 'Ketua Bidang Kaderisasi',
            'period' => '2014-2015',
        ]);
        $orgResponse->assertRedirect();
        $this->assertDatabaseHas('member_organizations', [
            'member_id' => $member->id,
            'organization_name' => 'PMII Rayon Desain',
        ]);

        // Member can add employment to own profile
        $empResponse = $this->actingAs($memberUser)->post('/member/profile/employment', [
            'company_name' => 'PT Studio Kreatif Digital',
            'position' => 'Lead UI/UX Designer',
            'start_year' => 2020,
        ]);
        $empResponse->assertRedirect();
        $this->assertDatabaseHas('member_employments', [
            'member_id' => $member->id,
            'company_name' => 'PT Studio Kreatif Digital',
        ]);

        // Member can add NU training to own profile
        $nuResponse = $this->actingAs($memberUser)->post('/member/profile/nu-training', [
            'training_type' => 'MKNU',
            'organizer' => 'PWNU Jawa Timur',
            'year' => 2021,
        ]);
        $nuResponse->assertRedirect();
        $this->assertDatabaseHas('member_nu_trainings', [
            'member_id' => $member->id,
            'training_type' => 'MKNU',
        ]);

        // Member can add certification to own profile
        $certResponse = $this->actingAs($memberUser)->post('/member/profile/certification', [
            'certification_name' => 'Certified Scrum Product Owner',
            'field' => 'Product Management',
            'issue_year' => 2023,
        ]);
        $certResponse->assertRedirect();
        $this->assertDatabaseHas('member_certifications', [
            'member_id' => $member->id,
            'certification_name' => 'Certified Scrum Product Owner',
        ]);
    }

    public function test_member_can_view_public_member_catalog_and_sees_catalog_menu()
    {
        $memberUser = User::create([
            'name' => 'Anggota Publik Test',
            'email' => 'anggota_publik@isnu.test',
            'phone' => '089911223304',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        Member::create([
            'user_id' => $memberUser->id,
            'full_name' => 'Anggota Publik Test',
            'nik' => '3578010101010004',
            'email' => 'anggota_publik@isnu.test',
            'phone' => '089911223304',
            'occupation' => 'Peneliti LIPI / BRIN',
            'birth_place' => 'Surabaya',
            'birth_date' => '1991-01-01',
            'gender' => 'L',
            'address' => 'Surabaya',
            'kelurahan' => 'Mulyorejo',
            'kecamatan' => 'Mulyorejo',
            'membership_status' => 'terverifikasi',
        ]);

        // Public can access Katalog Anggota
        $this->get('/daftar-anggota')->assertStatus(200)->assertSee('Katalog Anggota');

        // Member dashboard displays menu & quick link to Katalog Anggota
        $this->actingAs($memberUser)->get('/member/dashboard')
            ->assertStatus(200)
            ->assertSee('Katalog Anggota')
            ->assertSee(route('daftar.anggota'));

        // Member can access and browse Katalog Anggota
        $this->actingAs($memberUser)->get('/daftar-anggota')
            ->assertStatus(200)
            ->assertSee('Katalog Anggota')
            ->assertSee('Peneliti LIPI / BRIN');
    }

    public function test_public_member_catalog_displays_short_profile_modal_data()
    {
        $user = User::create([
            'name' => 'Kader Profil Singkat',
            'email' => 'kader_modal@isnu.test',
            'phone' => '089911223305',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
        ]);
        $member = Member::create([
            'user_id' => $user->id,
            'full_name' => 'Kader Profil Singkat, M.Kom.',
            'nik' => '3578010101010005',
            'email' => 'kader_modal@isnu.test',
            'phone' => '089911223305',
            'occupation' => 'Chief Technology Officer',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-05-05',
            'gender' => 'L',
            'address' => 'Jl. Rungkut Asri No. 5',
            'kelurahan' => 'Rungkut Kidul',
            'kecamatan' => 'Rungkut',
            'membership_status' => 'terverifikasi',
        ]);

        $member->educations()->create([
            'level' => 'S2',
            'institution_name' => 'Universitas Indonesia',
            'major' => 'Ilmu Komputer',
            'degree' => 'M.Kom.',
            'start_year' => 2014,
            'end_year' => 2017,
        ]);

        $member->employments()->create([
            'company_name' => 'PT Solusi Teknologi Nusantara',
            'position' => 'Chief Technology Officer',
            'employment_status' => 'Tetap',
            'start_year' => 2021,
        ]);

        $member->certifications()->create([
            'certification_name' => 'AWS Certified Solutions Architect',
            'field' => 'Cloud Computing',
            'issue_year' => 2023,
        ]);

        $shortProfile = $member->getShortProfileData();
        $this->assertEquals('Kader Profil Singkat, M.Kom.', $shortProfile['full_name']);
        $this->assertTrue($shortProfile['latest_education']['has_data']);
        $this->assertEquals('S2', $shortProfile['latest_education']['level']);
        $this->assertEquals('Ilmu Komputer', $shortProfile['latest_education']['major']);
        $this->assertTrue($shortProfile['latest_employment']['has_data']);
        $this->assertEquals('Chief Technology Officer', $shortProfile['latest_employment']['position']);
        $this->assertTrue($shortProfile['latest_certification']['has_data']);
        $this->assertEquals('AWS Certified Solutions Architect', $shortProfile['latest_certification']['name']);

        // Check HTML contains modal and card attributes
        $response = $this->get('/daftar-anggota');
        $response->assertStatus(200);
        $response->assertSee('modalDetailAnggota');
        $response->assertSee('Profil Singkat Anggota ISNU Surabaya');
        $response->assertSee('Pendidikan Terakhir');
        $response->assertSee('Pekerjaan Terakhir');
        $response->assertSee('Keahlian / Sertifikasi Terakhir');
        $response->assertSee('Lihat Profil Singkat');
        $response->assertSee('Kader Profil Singkat, M.Kom.');

        // AJAX search request also includes data-member-info and card markup
        $ajaxResponse = $this->get('/daftar-anggota?search=Chief', ['X-Requested-With' => 'XMLHttpRequest']);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('data-member-info');
        $ajaxResponse->assertSee('Lihat Profil Singkat');
    }

    public function test_pengurus_anggota_seksi_has_restricted_menus_and_actions()
    {
        $section = Section::first();

        $user = User::factory()->create([
            'role' => 'member',
            'email' => 'anggotaseksi@isnu.id',
            'phone' => '089911223399',
            'is_active' => true,
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'full_name' => 'Pengurus Anggota Seksi Test',
            'nik' => '3578010101019999',
            'email' => 'anggotaseksi@isnu.id',
            'phone' => '089911223399',
            'occupation' => 'Teknisi',
            'birth_place' => 'Surabaya',
            'birth_date' => '1995-05-05',
            'gender' => 'L',
            'address' => 'Jl. Rungkut No. 99',
            'kelurahan' => 'Rungkut',
            'kecamatan' => 'Rungkut',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $member->id,
            'position_title' => 'Anggota Seksi',
            'level' => 'Kota',
            'section_id' => $section?->id,
            'period' => '2026 - 2030',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // 1. Dashboard Admin: restricted menus are hidden
        $dashboardResponse = $this->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee('Verifikasi Pendaftar');
        $dashboardResponse->assertDontSee('Database Potensi Kader');
        $dashboardResponse->assertDontSee('Master Program Kerja');
        $dashboardResponse->assertDontSee('Master Kelompok Kerja');
        $dashboardResponse->assertDontSee('Export Laporan');
        $dashboardResponse->assertDontSee('Audit Log System');
        $dashboardResponse->assertSee('Pengurus ISNU');

        // 2. Direct route access to restricted pages return 403 Forbidden
        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.anggota.index'))->assertStatus(403);
        $this->get(route('admin.programs.index'))->assertStatus(403);
        $this->get(route('admin.workgroups.index'))->assertStatus(403);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.audit.index'))->assertStatus(403);

        // 3. Pengurus ISNU index: accessible, but Kartu Digital button & Detail action are hidden
        $pengurusResponse = $this->get(route('admin.pengurus.index'));
        $pengurusResponse->assertStatus(200);
        $pengurusResponse->assertDontSee('KARTU PENGURUS');
        $pengurusResponse->assertDontSee('Detail & Kelola');
        $pengurusResponse->assertDontSee('Detail');

        // Other member detail view should be 403 for Anggota Seksi
        $otherMember = Member::where('id', '!=', $member->id)->first();
        if ($otherMember) {
            $this->get(route('admin.anggota.show', $otherMember->id))->assertStatus(403);
        }
    }

    public function test_koordinator_seksi_can_only_manage_events_in_their_own_section()
    {
        $section1 = Section::create(['name' => 'Seksi Kaderisasi Unik', 'level' => 'PC ISNU']);
        $section2 = Section::create(['name' => 'Seksi Riset Unik', 'level' => 'PC ISNU']);

        $user = User::factory()->create([
            'role' => 'member',
            'email' => 'koordinator@isnu.id',
            'phone' => '089911223388',
            'is_active' => true,
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'full_name' => 'Koordinator Seksi Test',
            'nik' => '3578010101018888',
            'email' => 'koordinator@isnu.id',
            'phone' => '089911223388',
            'occupation' => 'Dosen Peneliti',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Gubeng No. 88',
            'kelurahan' => 'Gubeng',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $member->id,
            'position_title' => 'Koordinator Seksi',
            'level' => 'Kota',
            'section_id' => $section1->id,
            'period' => '2026 - 2030',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // 1. Can access events index and see create event button
        $response = $this->get(route('admin.events.index'));
        $response->assertStatus(200);
        $response->assertSee('Rencanakan Kegiatan Baru');

        // 2. Can create event in their own section ($section1)
        $validPayload = [
            'title' => 'Pelatihan Kader Seksi Sendiri',
            'section_id' => $section1->id,
            'location' => 'Gedung PCNU Lt. 3',
            'method' => 'luring',
            'event_date' => '2026-11-20',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'presence_start_at' => '2026-11-20 07:30:00',
            'presence_end_at' => '2026-11-20 12:30:00',
            'status' => 'planned',
        ];
        $postResponse = $this->post(route('admin.events.store'), $validPayload);
        $postResponse->assertStatus(302);
        $this->assertDatabaseHas('events', [
            'title' => 'Pelatihan Kader Seksi Sendiri',
            'section_id' => $section1->id,
        ]);

        // 3. CANNOT create event in another section ($section2) -> 403 Forbidden
        $invalidSectionPayload = array_merge($validPayload, [
            'title' => 'Kegiatan di Seksi Lain',
            'section_id' => $section2->id,
        ]);
        $failResponse1 = $this->post(route('admin.events.store'), $invalidSectionPayload);
        $failResponse1->assertStatus(403);
        $this->assertDatabaseMissing('events', ['title' => 'Kegiatan di Seksi Lain']);

        // 4. CANNOT create event without section (null) -> 403 Forbidden
        $nullSectionPayload = array_merge($validPayload, [
            'title' => 'Kegiatan Tanpa Seksi',
            'section_id' => null,
        ]);
        $failResponse2 = $this->post(route('admin.events.store'), $nullSectionPayload);
        $failResponse2->assertStatus(403);
        $this->assertDatabaseMissing('events', ['title' => 'Kegiatan Tanpa Seksi']);
    }

    public function test_koordinator_seksi_access_restrictions_and_personal_account_management()
    {
        $section = Section::create(['name' => 'Seksi Riset Kebijakan', 'level' => 'PC ISNU']);

        $user = User::factory()->create([
            'role' => 'member',
            'email' => 'koor_test@isnu.id',
            'phone' => '089912345678',
            'is_active' => true,
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'full_name' => 'H. Koordinator Riset',
            'nik' => '3578010101019999',
            'email' => 'koor_test@isnu.id',
            'phone' => '089912345678',
            'occupation' => 'Peneliti Madya',
            'birth_place' => 'Surabaya',
            'birth_date' => '1988-05-15',
            'gender' => 'L',
            'address' => 'Jl. Dharmawangsa No. 12',
            'kelurahan' => 'Kertajaya',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $member->id,
            'position_title' => 'Koordinator Seksi',
            'level' => 'Kota',
            'section_id' => $section->id,
            'period' => '2026 - 2030',
            'is_active' => true,
        ]);

        $otherMember = Member::create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
            'full_name' => 'Anggota Lain Test',
            'nik' => '3578010101019998',
            'email' => 'anggota_lain@isnu.id',
            'phone' => '089912345679',
            'occupation' => 'Guru',
            'birth_place' => 'Surabaya',
            'birth_date' => '1992-02-02',
            'gender' => 'L',
            'address' => 'Jl. Ngagel No. 1',
            'kelurahan' => 'Ngagel',
            'kecamatan' => 'Wonokromo',
            'membership_status' => 'terverifikasi',
        ]);

        $this->actingAs($user);

        // 1. Dashboard: restricted menus are not visible
        $dashboardResponse = $this->get(route('admin.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee('Verifikasi Pendaftar');
        $dashboardResponse->assertDontSee('Database Potensi Kader');
        $dashboardResponse->assertDontSee('Master Kelompok Kerja');
        $dashboardResponse->assertDontSee('Export Laporan');
        $dashboardResponse->assertDontSee('Audit Log System');
        $dashboardResponse->assertDontSee('Antrean Verifikasi Pendaftar Baru');
        // Allowed menus are visible
        $dashboardResponse->assertSee('Master Program Kerja');
        $dashboardResponse->assertSee('Kegiatan & Presensi', false);
        $dashboardResponse->assertSee('Pengurus ISNU');

        // 2. Direct access to forbidden routes returns 403 Forbidden
        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.anggota.index'))->assertStatus(403);
        $this->get(route('admin.workgroups.index'))->assertStatus(403);
        $this->post(route('admin.workgroups.store'), [
            'level' => 'PC ISNU',
            'name' => 'Pokja Baru Tidak Boleh',
        ])->assertStatus(403);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.audit.index'))->assertStatus(403);

        // 3. Pengurus ISNU: can view list, but CANNOT view detail of other members
        $pengurusResponse = $this->get(route('admin.pengurus.index'));
        $pengurusResponse->assertStatus(200);
        $pengurusResponse->assertDontSee('KARTU PENGURUS');
        $pengurusResponse->assertDontSee('Detail & Kelola');

        // Cannot view other member's detail (403)
        $this->get(route('admin.anggota.show', $otherMember->id))->assertStatus(403);

        // 4. Personal Account & History Management
        // Can access member profile edit
        $this->get(route('member.profile.edit'))->assertStatus(200);

        // Add, update, delete Organization History
        $orgResponse = $this->post(route('member.profile.organization.add'), [
            'organization_name' => 'IPNU Surabaya',
            'position' => 'Ketua PAC',
            'period' => '2015 - 2017',
        ]);
        $orgResponse->assertSessionHas('success');
        $org = $member->organizations()->first();
        $this->assertNotNull($org);
        $this->assertEquals('IPNU Surabaya', $org->organization_name);

        $this->put(route('member.profile.organization.update', $org->id), [
            'organization_name' => 'IPNU Surabaya Cabang',
            'position' => 'Ketua PC',
            'period' => '2015 - 2017',
        ])->assertSessionHas('success');
        $this->assertEquals('IPNU Surabaya Cabang', $org->fresh()->organization_name);

        $this->delete(route('member.profile.organization.delete', $org->id))->assertSessionHas('success');
        $this->assertDatabaseMissing('member_organizations', ['id' => $org->id]);

        // Add Employment History
        $this->post(route('member.profile.employment.add'), [
            'company_name' => 'Pusat Riset Kebijakan',
            'position' => 'Peneliti Senior',
            'start_year' => 2020,
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('member_employments', [
            'member_id' => $member->id,
            'company_name' => 'Pusat Riset Kebijakan',
        ]);

        // Add Certification
        $this->post(route('member.profile.certification.add'), [
            'certification_name' => 'Sertifikasi Peneliti Utama',
            'field' => 'Riset Kebijakan Publik',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('member_certifications', [
            'member_id' => $member->id,
            'certification_name' => 'Sertifikasi Peneliti Utama',
        ]);

        // Add NU Cadre Training
        $this->post(route('member.profile.nu_training.add'), [
            'training_type' => 'PKPNU',
            'organizer' => 'PCNU Kota Surabaya',
            'year' => 2021,
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('member_nu_trainings', [
            'member_id' => $member->id,
            'training_type' => 'PKPNU',
        ]);
    }

    public function test_wakil_ketua_and_wakil_sekretaris_access_restrictions_and_left_menu_retained()
    {
        $section = Section::create(['name' => 'Seksi Advokasi & Hukum', 'level' => 'PC ISNU']);

        // 1. Setup Wakil Ketua user
        $userWakilKetua = User::factory()->create([
            'role' => 'member',
            'email' => 'wakil_ketua@isnu.id',
            'phone' => '089912345688',
            'is_active' => true,
        ]);

        $memberWakilKetua = Member::create([
            'user_id' => $userWakilKetua->id,
            'full_name' => 'Dr. H. Wakil Ketua ISNU',
            'nik' => '3578010101017777',
            'email' => 'wakil_ketua@isnu.id',
            'phone' => '089912345688',
            'occupation' => 'Advokat Senior',
            'birth_place' => 'Surabaya',
            'birth_date' => '1985-04-10',
            'gender' => 'L',
            'address' => 'Jl. Ketintang No. 10',
            'kelurahan' => 'Ketintang',
            'kecamatan' => 'Gayungan',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $memberWakilKetua->id,
            'position_title' => 'Wakil Ketua',
            'level' => 'Kota',
            'section_id' => $section->id,
            'period' => '2026 - 2030',
            'is_active' => true,
        ]);

        // 2. Setup Wakil Sekretaris user
        $userWakilSekretaris = User::factory()->create([
            'role' => 'member',
            'email' => 'wakil_sekretaris@isnu.id',
            'phone' => '089912345689',
            'is_active' => true,
        ]);

        $memberWakilSekretaris = Member::create([
            'user_id' => $userWakilSekretaris->id,
            'full_name' => 'M. Wakil Sekretaris ISNU',
            'nik' => '3578010101017778',
            'email' => 'wakil_sekretaris@isnu.id',
            'phone' => '089912345689',
            'occupation' => 'Notaris',
            'birth_place' => 'Surabaya',
            'birth_date' => '1987-07-20',
            'gender' => 'L',
            'address' => 'Jl. Wonokromo No. 25',
            'kelurahan' => 'Wonokromo',
            'kecamatan' => 'Wonokromo',
            'membership_status' => 'pengurus',
        ]);

        Position::create([
            'member_id' => $memberWakilSekretaris->id,
            'position_title' => 'Wakil Sekretaris',
            'level' => 'Kota',
            'section_id' => $section->id,
            'period' => '2026 - 2030',
            'is_active' => true,
        ]);

        $otherMember = Member::create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
            'full_name' => 'Kader Biasa Lainnya',
            'nik' => '3578010101017779',
            'email' => 'kader_biasa@isnu.id',
            'phone' => '089912345690',
            'occupation' => 'Wirausaha',
            'birth_place' => 'Surabaya',
            'birth_date' => '1995-01-01',
            'gender' => 'L',
            'address' => 'Jl. Barata Jaya No. 7',
            'kelurahan' => 'Baratajaya',
            'kecamatan' => 'Gubeng',
            'membership_status' => 'terverifikasi',
        ]);

        // TEST FOR WAKIL KETUA
        $this->actingAs($userWakilKetua);

        // Sidebar & Dashboard: Restricted menus are hidden, allowed menus are shown
        $dashResponse = $this->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertDontSee('Verifikasi Pendaftar');
        $dashResponse->assertDontSee('Database Potensi Kader');
        $dashResponse->assertDontSee('Master Kelompok Kerja');
        $dashResponse->assertDontSee('Export Laporan');
        $dashResponse->assertDontSee('Audit Log System');
        $dashResponse->assertDontSee('Antrean Verifikasi Pendaftar Baru');
        $dashResponse->assertSee('Master Program Kerja');
        $dashResponse->assertSee('Kegiatan & Presensi', false);
        $dashResponse->assertSee('Pengurus ISNU');

        // Direct access to forbidden routes returns 403 Forbidden
        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.anggota.index'))->assertStatus(403);
        $this->get(route('admin.workgroups.index'))->assertStatus(403);
        $this->post(route('admin.workgroups.store'), ['level' => 'PC ISNU', 'name' => 'Pokja Ilegal'])->assertStatus(403);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.audit.index'))->assertStatus(403);

        // Pengurus ISNU: can view list, but cannot see card download or detail actions
        $pengurusResponse = $this->get(route('admin.pengurus.index'));
        $pengurusResponse->assertStatus(200);
        $pengurusResponse->assertDontSee('KARTU PENGURUS');
        $pengurusResponse->assertDontSee('Detail & Kelola');
        $this->get(route('admin.anggota.show', $otherMember->id))->assertStatus(403);

        // Kelola Biodata Riwayat: retains left menu
        $profileEditResponse = $this->get(route('member.profile.edit'));
        $profileEditResponse->assertStatus(200);
        $profileEditResponse->assertSee('sidebar');
        $profileEditResponse->assertSee('Kelola Biodata & Riwayat');
        $profileEditResponse->assertSee('Pengurus ISNU');

        // TEST FOR WAKIL SEKRETARIS
        $this->actingAs($userWakilSekretaris);

        $dashSekResponse = $this->get(route('admin.dashboard'));
        $dashSekResponse->assertStatus(200);
        $dashSekResponse->assertDontSee('Verifikasi Pendaftar');
        $dashSekResponse->assertDontSee('Database Potensi Kader');
        $dashSekResponse->assertDontSee('Master Kelompok Kerja');
        $dashSekResponse->assertDontSee('Export Laporan');
        $dashSekResponse->assertDontSee('Audit Log System');

        $this->get(route('admin.verifikasi.index'))->assertStatus(403);
        $this->get(route('admin.anggota.index'))->assertStatus(403);
        $this->get(route('admin.workgroups.index'))->assertStatus(403);
        $this->get(route('admin.laporan.index'))->assertStatus(403);
        $this->get(route('admin.audit.index'))->assertStatus(403);
        $this->get(route('admin.anggota.show', $otherMember->id))->assertStatus(403);

        // Kelola Biodata Riwayat: retains left menu
        $profileEditSekResponse = $this->get(route('member.profile.edit'));
        $profileEditSekResponse->assertStatus(200);
        $profileEditSekResponse->assertSee('sidebar');
        $profileEditSekResponse->assertSee('Kelola Biodata & Riwayat');
    }

    /**
     * Test pengajuan konsep surat, aksesibilitas role (admin, ketua, sekretaris, wakil ketua, wakil sekretaris),
     * injeksi template Word template_surat.docx, dan alur penandatanganan digital bertahap (Sekretaris lalu Ketua).
     */
    public function test_letter_concept_and_two_step_digital_signature_workflow(): void
    {
        Storage::fake('public');

        $section = Section::create([
            'name' => 'Seksi Kaderisasi Surat',
            'code' => 'SKS',
            'level' => 'PC ISNU',
            'description' => 'Seksi Kaderisasi',
        ]);

        // 1. Setup Admin
        $admin = User::factory()->create(['role' => 'admin_kota']);

        $createTestMember = function (User $user, string $name, string $positionTitle, string $memberNumber, ?int $secId = null) {
            $member = Member::create([
                'user_id' => $user->id,
                'member_number' => $memberNumber,
                'nik' => '3578'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'email' => $user->email,
                'phone' => '081234567890',
                'occupation' => 'Dosen / Akademisi',
                'birth_place' => 'Surabaya',
                'birth_date' => '1985-01-01',
                'gender' => 'L',
                'address' => 'Jl. Bubutan No. 20',
                'kelurahan' => 'Bubutan',
                'kecamatan' => 'Bubutan',
                'membership_status' => 'pengurus',
            ]);

            Position::create([
                'member_id' => $member->id,
                'position_title' => $positionTitle,
                'section_id' => $secId,
                'level' => 'PC ISNU',
                'start_date' => now()->subMonth(),
                'is_active' => true,
            ]);

            return $member;
        };

        // 2. Setup Ketua
        $userKetua = User::factory()->create(['name' => 'Prof. Dr. H. M. Mas\'ud Said, M.Si.']);
        $memberKetua = $createTestMember($userKetua, 'Prof. Dr. H. M. Mas\'ud Said, M.Si.', 'Ketua', '13.01.2024.0001');

        // 3. Setup Sekretaris
        $userSekretaris = User::factory()->create(['name' => 'M. Dawud, M.Pd.']);
        $memberSekretaris = $createTestMember($userSekretaris, 'M. Dawud, M.Pd.', 'Sekretaris', '13.01.2024.0002');

        // 4. Setup Wakil Ketua
        $userWakilKetua = User::factory()->create(['name' => 'Dr. H. Ahmad Wakil Ketua']);
        $memberWakilKetua = $createTestMember($userWakilKetua, 'Dr. H. Ahmad Wakil Ketua', 'Wakil Ketua', '13.01.2024.0003');

        // 5. Setup Wakil Sekretaris
        $userWakilSekretaris = User::factory()->create(['name' => 'Ahmad Wakil Sekretaris']);
        $memberWakilSekretaris = $createTestMember($userWakilSekretaris, 'Ahmad Wakil Sekretaris', 'Wakil Sekretaris', '13.01.2024.0004');

        // 6. Setup Koordinator Seksi (tidak berhak)
        $userKoordinator = User::factory()->create(['name' => 'Koordinator Seksi Test']);
        $memberKoordinator = $createTestMember($userKoordinator, 'Koordinator Seksi Test', 'Koordinator Seksi', '13.01.2024.0005', $section->id);

        // 7. Setup Anggota Seksi (berhak mengajukan konsep surat)
        $userAnggotaSeksi = User::factory()->create(['name' => 'Anggota Seksi Test']);
        $memberAnggotaSeksi = $createTestMember($userAnggotaSeksi, 'Anggota Seksi Test', 'Anggota Seksi', '13.01.2024.0006', $section->id);

        // 8. Setup Member Biasa (non-pengurus / tidak berhak mengajukan konsep surat)
        $userMemberBiasa = User::factory()->create(['name' => 'Member Biasa Test', 'role' => 'member']);
        $memberBiasa = Member::create([
            'user_id' => $userMemberBiasa->id,
            'full_name' => 'Member Biasa Test',
            'nik' => '3578010101900099',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Wonokromo No. 1',
            'kelurahan' => 'Wonokromo',
            'kecamatan' => 'Wonokromo',
            'phone' => '081234567899',
            'email' => $userMemberBiasa->email,
            'occupation' => 'Dosen',
            'membership_status' => 'terverifikasi',
        ]);

        // A. CEK HAK AKSES MENU DAN ROUTE
        // 1. Pengguna Berhak: Admin, Ketua, Sekretaris, Wakil Ketua, Wakil Sekretaris, Koordinator Seksi, Anggota Seksi
        $allowedUsers = [$admin, $userKetua, $userSekretaris, $userWakilKetua, $userWakilSekretaris, $userKoordinator, $userAnggotaSeksi];
        foreach ($allowedUsers as $u) {
            $this->actingAs($u);
            $indexRes = $this->get(route('admin.letters.index'));
            $indexRes->assertStatus(200);
            $indexRes->assertSee('Konsep Surat & E-TTD');
            $this->get(route('admin.letters.create'))->assertStatus(200);
        }

        // 2. Pengguna Tidak Berhak: Member biasa (non-pengurus)
        $forbiddenUsers = [$userMemberBiasa];
        foreach ($forbiddenUsers as $u) {
            $this->actingAs($u);
            $this->get(route('admin.letters.index'))->assertStatus(403);
            $this->get(route('admin.letters.create'))->assertStatus(403);
        }

        // B. BUAT KONSEP SURAT DRAFT OLEH WAKIL SEKRETARIS
        $this->actingAs($userWakilSekretaris);
        $postData = [
            'level' => 'PC ISNU',
            'letter_number' => '045/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'lampiran' => '1 (Satu) Berkas',
            'perihal' => 'Permohonan Narasumber Seminar Nasional',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Ketua PW ISNU Jawa Timur',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Dengan <strong>hormat</strong>, sehubungan dengan pelaksanaan <em>Seminar Nasional</em> Keumatan, kami mengundang:</p><ul><li>Sesi Utama</li><li>Diskusi Panel</li></ul><p>Demikian surat permohonan ini kami sampaikan.</p>',
            'action' => 'draft',
        ];

        $storeResponse = $this->post(route('admin.letters.store'), $postData);
        $storeResponse->assertRedirect();

        $letter = Letter::where('letter_number', '045/PC-VI/ISNU-SBY/XI/2026')->first();
        $this->assertNotNull($letter);
        $this->assertEquals(Letter::STATUS_DRAFT, $letter->status);
        $this->assertEquals('13.01.2024.0004', $userWakilSekretaris->member->member_number);

        // Dokumen DOCX dan PDF awal ter-generate
        $this->assertNotNull($letter->docx_path);
        $this->assertNotNull($letter->pdf_path);
        Storage::disk('public')->assertExists($letter->docx_path);
        Storage::disk('public')->assertExists($letter->pdf_path);

        // KETENTUAN: File PDF baru dapat diunduh ketika surat sudah ditandatangani secara lengkap (403 Forbidden)
        $this->get(route('admin.letters.download-pdf', $letter->id))->assertStatus(403);
        $this->get(route('surat.download-pdf', $letter->letter_code))->assertStatus(403);

        // C. AJUKAN KE SEKRETARIS
        $this->post(route('admin.letters.submit-secretary', $letter->id))
            ->assertRedirect(route('admin.letters.show', $letter->id));
        $letter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $letter->status);

        // D. ADMIN MENCOBA MENANDATANGANI SURAT -> HARUS DITOLAK (403 Forbidden)
        $this->actingAs($admin);
        $this->post(route('admin.letters.sign-secretary', $letter->id))->assertStatus(403);
        $this->post(route('admin.letters.sign-chairman', $letter->id))->assertStatus(403);

        // E. KETUA MENCOBA MENANDATANGANI SEBELUM SEKRETARIS -> HARUS DITOLAK
        $this->actingAs($userKetua);
        $chairEarlySign = $this->post(route('admin.letters.sign-chairman', $letter->id));
        $chairEarlySign->assertRedirect();
        $chairEarlySign->assertSessionHas('error');
        $letter->refresh();
        $this->assertNull($letter->chairman_signed_at);
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $letter->status);

        // E. SEKRETARIS MENANDATANGANI SECARA DIGITAL (TAHAP 1)
        $this->actingAs($userSekretaris);
        $secShowRes = $this->get(route('admin.letters.show', $letter->id));
        $secShowRes->assertStatus(200);
        $secShowRes->assertSee('Konfirmasi E-TTD Sekretaris');
        $secShowRes->assertSee('Ya, Tandatangani');

        $secSignRes = $this->post(route('admin.letters.sign-secretary', $letter->id));
        $secSignRes->assertRedirect(route('admin.letters.show', $letter->id));
        $secSignRes->assertSessionHas('success');

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_KETUA, $letter->status);
        $this->assertNotNull($letter->secretary_signed_at);
        $this->assertEquals('M. Dawud, M.Pd.', $letter->secretary_name);
        $this->assertEquals('13.01.2024.0002', $letter->secretary_registration_number);
        $this->assertEquals('Sekretaris', $letter->secretary_position);
        $this->assertNotNull($letter->secretary_qr_path);
        Storage::disk('public')->assertExists($letter->secretary_qr_path);

        // F. KETUA MENANDATANGANI SECARA DIGITAL (TAHAP 2)
        $this->actingAs($userKetua);
        $chairShowRes = $this->get(route('admin.letters.show', $letter->id));
        $chairShowRes->assertStatus(200);
        $chairShowRes->assertSee('Konfirmasi E-TTD Ketua');
        $chairShowRes->assertSee('Ya, Sahkan dan Terbitkan');

        $chairSignRes = $this->post(route('admin.letters.sign-chairman', $letter->id));
        $chairSignRes->assertRedirect(route('admin.letters.show', $letter->id));
        $chairSignRes->assertSessionHas('success');

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_SELESAI, $letter->status);
        $this->assertNotNull($letter->chairman_signed_at);
        $this->assertEquals('Prof. Dr. H. M. Mas\'ud Said, M.Si.', $letter->chairman_name);
        $this->assertEquals('13.01.2024.0001', $letter->chairman_registration_number);
        $this->assertEquals('Ketua', $letter->chairman_position);
        $this->assertNotNull($letter->chairman_qr_path);
        Storage::disk('public')->assertExists($letter->chairman_qr_path);

        // Final DOCX and PDF exist
        Storage::disk('public')->assertExists($letter->docx_path);
        Storage::disk('public')->assertExists($letter->pdf_path);

        // G. VERIFIKASI PUBLIK VIA QR CODE URL
        $verifyRes = $this->get(route('surat.verifikasi', $letter->letter_code));
        $verifyRes->assertStatus(200);
        $verifyRes->assertSee('DOKUMEN RESMI TERVERIFIKASI');
        $verifyRes->assertSee('045/PC-VI/ISNU-SBY/XI/2026');
        $verifyRes->assertSee('IKATAN SARJANA NAHDLATUL');
        $verifyRes->assertSee('ULAMA KOTA SURABAYA');
        $verifyRes->assertSee('<strong>hormat</strong>', false);
        $verifyRes->assertSee('M. Dawud, M.Pd.');
        $verifyRes->assertSee('Prof. Dr. H. M. Mas\'ud Said, M.Si.');
        $verifyRes->assertSee('13.01.2024.0001');
        $verifyRes->assertSee('13.01.2024.0002');

        // H. UNDUH DOKUMEN PDF (ADMIN / PENGURUS)
        $downloadPdf = $this->get(route('admin.letters.download-pdf', $letter->id));
        $downloadPdf->assertStatus(200);

        // I. UNDUH DOKUMEN RESMI DARI HALAMAN VERIFIKASI PUBLIK (TANPA LOGIN / GUEST)
        auth()->logout();
        $this->get(route('surat.download-pdf', $letter->letter_code))->assertStatus(200);

        // J. VERIFIKASI ANGGOTA SEKSI & KOORDINATOR SEKSI DAPAT MENGAJUKAN KONSEP SURAT
        $this->actingAs($userAnggotaSeksi);
        $postAnggota = [
            'level' => 'PC ISNU',
            'letter_number' => '077/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'lampiran' => '-',
            'perihal' => 'Pengajuan Konsep Surat oleh Anggota Seksi',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Sekretariat PC ISNU',
            'isi_surat' => '<p>Konsep surat dari Anggota Seksi</p>',
            'action' => 'draft',
        ];
        $storeAnggotaRes = $this->post(route('admin.letters.store'), $postAnggota);
        $storeAnggotaRes->assertRedirect();
        $letterAnggota = Letter::where('letter_number', '077/PC-VI/ISNU-SBY/XI/2026')->first();
        $this->assertNotNull($letterAnggota);
        $this->assertEquals($userAnggotaSeksi->id, $letterAnggota->user_id);

        // Anggota Seksi & Koordinator Seksi TIDAK BISA menandatangani surat (403)
        $this->post(route('admin.letters.sign-secretary', $letterAnggota->id))->assertStatus(403);
        $this->post(route('admin.letters.sign-chairman', $letterAnggota->id))->assertStatus(403);

        $this->actingAs($userKoordinator);
        $this->post(route('admin.letters.sign-secretary', $letterAnggota->id))->assertStatus(403);
        $this->post(route('admin.letters.sign-chairman', $letterAnggota->id))->assertStatus(403);

        // Verifikasi menu Konsep Surat muncul di Dashboard Member bagi Anggota Seksi
        $this->actingAs($userAnggotaSeksi);
        $memberDashRes = $this->get(route('member.dashboard'));
        $memberDashRes->assertStatus(200);
        $memberDashRes->assertSee('Pengajuan Konsep Surat');
        $memberDashRes->assertSee(route('admin.letters.index'));
    }

    /**
     * Test alur penolakan / revisi konsep surat dan penyuntingan ulang.
     */
    public function test_letter_rejection_and_edit_cycle(): void
    {
        Storage::fake('public');

        $userSekretaris = User::factory()->create(['name' => 'Sekretaris Test']);
        Member::create([
            'user_id' => $userSekretaris->id,
            'member_number' => '13.01.2024.0099',
            'nik' => '3578000000000099',
            'full_name' => 'Sekretaris Test',
            'email' => $userSekretaris->email,
            'phone' => '081234567899',
            'occupation' => 'Akademisi',
            'birth_place' => 'Surabaya',
            'birth_date' => '1985-01-01',
            'gender' => 'L',
            'address' => 'Jl. Bubutan',
            'kelurahan' => 'Bubutan',
            'kecamatan' => 'Bubutan',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $userSekretaris->member->id,
            'position_title' => 'Sekretaris',
            'level' => 'PC ISNU',
            'start_date' => now()->subMonth(),
            'is_active' => true,
        ]);

        $letter = Letter::create([
            'user_id' => $userSekretaris->id,
            'letter_code' => 'SRT-20261109-TEST01',
            'letter_number' => '099/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'lampiran' => '-',
            'perihal' => 'Undangan Rapat Konsep Awal',
            'tujuan_surat' => 'Seluruh Pengurus Harian',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => 'Harap hadir dalam rapat koordinasi.',
            'status' => Letter::STATUS_MENUNGGU_SEKRETARIS,
        ]);

        // 1. Sekretaris menolak dengan catatan
        $this->actingAs($userSekretaris);
        $rejectRes = $this->post(route('admin.letters.reject', $letter->id), [
            'rejection_note' => 'Mohon tambahkan rincian agenda dan tempat pelaksanaan pada isi surat.',
        ]);
        $rejectRes->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_DITOLAK, $letter->status);
        $this->assertEquals('Mohon tambahkan rincian agenda dan tempat pelaksanaan pada isi surat.', $letter->rejection_note);

        // 2. Tampilkan halaman show dan edit: catatan terlihat
        $showRes = $this->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Mohon tambahkan rincian agenda dan tempat pelaksanaan pada isi surat.');

        $editRes = $this->get(route('admin.letters.edit', $letter->id));
        $editRes->assertStatus(200);
        $editRes->assertSee('Mohon tambahkan rincian agenda dan tempat pelaksanaan pada isi surat.');

        // 3. Pengaju menyunting dan mengajukan kembali
        $updateRes = $this->put(route('admin.letters.update', $letter->id), [
            'level' => 'PC ISNU',
            'letter_number' => '099/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'lampiran' => '-',
            'perihal' => 'Undangan Rapat Koordinasi PC ISNU',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Seluruh Pengurus Harian',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => "Harap hadir dalam rapat koordinasi.\nWaktu: 19.00 WIB\nTempat: Graha NU Bubutan.",
            'action' => 'submit_secretary',
        ]);
        $updateRes->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $letter->status);
        $this->assertNull($letter->rejection_note);
        $this->assertStringContainsString('Graha NU Bubutan', $letter->isi_surat);

        // 4. Hapus konsep surat
        $delRes = $this->delete(route('admin.letters.destroy', $letter->id));
        $delRes->assertRedirect(route('admin.letters.index'));
        $this->assertDatabaseMissing('letters', ['id' => $letter->id]);
    }

    /**
     * Test pembuatan dan penampilan surat dengan tabel HTML (bergaris maupun tanpa border).
     */
    public function test_letter_with_html_tables_bordered_and_borderless(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['name' => 'Admin PC Surat', 'role' => 'admin_kota']);

        $this->actingAs($admin);

        // Verifikasi fitur lebar kolom tersedia pada form create
        // serta verifikasi header banner kop surat TIDAK dimunculkan di form input
        $createRes = $this->get(route('admin.letters.create'));
        $createRes->assertStatus(200);
        $createRes->assertSee('Lebar Kolom');
        $createRes->assertSee('columnWidthModal');
        $createRes->assertSee('Atur Semua Kolom...');
        $createRes->assertDontSee('Kop Surat Resmi (Header)');
        $createRes->assertSee('Kota Asal Surat');
        $createRes->assertSee('Kota / Lokasi Tujuan');

        // Konten isi surat memuat tabel tanpa border (rincian) dengan lebar kolom dan tabel bergaris (agenda)
        $htmlIsiSurat = '<p>Sehubungan dengan pelaksanaan program kerja, kami mengundang kehadiran Bapak/Ibu pada:</p>'.
            '<table class="table-borderless" border="0" style="width: 100%; border: none;">'.
            '<tbody>'.
            '<tr><td style="width: 25%;">Hari / Tanggal</td><td style="width: 5%;">:</td><td style="width: 70%;">Sabtu, 14 November 2026</td></tr>'.
            '<tr><td style="width: 25%;">Waktu</td><td style="width: 5%;">:</td><td style="width: 70%;">08.30 WIB - Selesai</td></tr>'.
            '<tr><td style="width: 25%;">Tempat</td><td style="width: 5%;">:</td><td style="width: 70%;">Aula PCNU Kota Surabaya</td></tr>'.
            '</tbody></table>'.
            '<p>Adapun susunan agenda kegiatan adalah sebagai berikut:</p>'.
            '<table class="table-bordered" border="1" style="width: 100%; border: 1px solid #333;">'.
            '<thead><tr style="background-color: #f1f5f9;"><th style="width: 10%;">No</th><th style="width: 25%;">Waktu</th><th style="width: 45%;">Agenda</th><th style="width: 20%;">PJ</th></tr></thead>'.
            '<tbody>'.
            '<tr><td style="width: 10%;">1</td><td style="width: 25%;">08.30 - 09.30</td><td style="width: 45%;">Registrasi &amp; Pembukaan</td><td style="width: 20%;">Panitia</td></tr>'.
            '<tr><td style="width: 10%;">2</td><td style="width: 25%;">09.30 - 11.30</td><td style="width: 45%;">Rapat Kerja PC ISNU</td><td style="width: 20%;">Sekretaris</td></tr>'.
            '</tbody></table>'.
            '<p>Demikian surat undangan ini kami sampaikan, atas perhatian dan kehadirannya diucapkan terima kasih.</p>';

        $postRes = $this->post(route('admin.letters.store'), [
            'level' => 'PC ISNU',
            'letter_number' => '101/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'lampiran' => '1 Lembar',
            'perihal' => 'Undangan Rapat Kerja dengan Tabel Agenda',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Seluruh Anggota Pleno',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => $htmlIsiSurat,
            'action' => 'draft',
        ]);

        $letter = Letter::where('letter_number', '101/PC-VI/ISNU-SBY/XI/2026')->first();
        $this->assertNotNull($letter);
        $postRes->assertRedirect(route('admin.letters.show', $letter->id));

        // Verifikasi tabel dan lebar kolom tersimpan utuh di database
        $this->assertStringContainsString('table-borderless', $letter->isi_surat);
        $this->assertStringContainsString('width: 25%', $letter->isi_surat);
        $this->assertStringContainsString('width: 70%', $letter->isi_surat);
        $this->assertStringContainsString('table-bordered', $letter->isi_surat);
        $this->assertStringContainsString('width: 45%', $letter->isi_surat);
        $this->assertStringContainsString('Aula PCNU Kota Surabaya', $letter->isi_surat);
        $this->assertStringContainsString('Rapat Kerja PC ISNU', $letter->isi_surat);

        // Verifikasi tabel dan penyesuaian lebar kolom tampil pada halaman edit
        // serta header banner kop surat TIDAK dimunculkan di form edit
        $editRes = $this->get(route('admin.letters.edit', $letter->id));
        $editRes->assertStatus(200);
        $editRes->assertSee('Lebar Kolom');
        $editRes->assertSee('columnWidthModal');
        $editRes->assertSee('Atur Semua Kolom...');
        $editRes->assertSee('width: 25%', false);
        $editRes->assertDontSee('Kop Surat Resmi (Header)');
        $editRes->assertSee('Kota Asal Surat');
        $editRes->assertSee('Kota / Lokasi Tujuan');

        // Verifikasi tidak ada duplikasi navbar atas (header title hanya muncul tepat 1 kali pada output HTML)
        $this->assertEquals(1, substr_count($createRes->getContent(), 'Pengajuan Konsep Surat Resmi'));
        $this->assertEquals(1, substr_count($editRes->getContent(), 'Sunting Konsep Surat Resmi'));

        // Verifikasi tabel tampil pada halaman preview (show)
        $showRes = $this->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('table-borderless', false);
        $showRes->assertSee('table-bordered', false);
        $showRes->assertSee('width: 25%', false);
        $showRes->assertSee('width: 70%', false);
        $showRes->assertSee('Aula PCNU Kota Surabaya');
        $showRes->assertSee('Rapat Kerja PC ISNU');

        // Verifikasi bagian atas tanda tangan tidak ada tulisan jabatan (hanya judul PC ISNU lalu box QR/TTD)
        $showRes->assertDontSee('<div class="signer-title">', false);
        $showRes->assertSee('PIMPINAN CABANG');
        $showRes->assertSee('IKATAN SARJANA NAHDLATUL ULAMA KOTA SURABAYA');

        // Verifikasi footer slogan pada halaman preview
        $showRes->assertSee('Dari Kontemplasi Menjadi Aksi [] Menyapa Semua [] Merangkul Umat [] Menanam untuk Bangsa');

        // Verifikasi PDF ter-generate dan terkunci sebelum status selesai
        $this->assertNotNull($letter->pdf_path);
        Storage::disk('public')->assertExists($letter->pdf_path);

        $this->get(route('admin.letters.download-pdf', $letter->id))->assertStatus(403);
        $letter->status = Letter::STATUS_SELESAI;
        $letter->save();

        $downloadPdf = $this->get(route('admin.letters.download-pdf', $letter->id));
        $downloadPdf->assertStatus(200);

        // Reload $showRes with completed letter status to check un-disabled download button
        $showRes = $this->get(route('admin.letters.show', $letter->id));

        // Verifikasi teks keamanan yang telah dipersingkat pada preview dan PDF
        $showRes->assertSee('Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode di atas untuk verifikasi otentisitas dokumen.');
        $showRes->assertSee('Kode Surat: '.$letter->letter_code);
        $showRes->assertSee(':disabled="isDownloadingPdf"', false);
        $showRes->assertSee('downloadPdfWithSpinner', false);

        // Verifikasi pada daftar surat admin terdapat button download berspinner & disabled
        $indexRes = $this->get(route('admin.letters.index'));
        $indexRes->assertSee(':disabled="downloadingId === '.$letter->id.'"', false);
        $indexRes->assertSee('downloadPdfWithSpinner', false);

        // Verifikasi pada preview (show) header banner kop surat TETAP dimunculkan
        // serta memuat kota asal surat sebelum tanggal
        $showRes->assertSee('IKATAN SARJANA NAHDLATUL');
        $showRes->assertSee('ULAMA KOTA SURABAYA');
        $showRes->assertSee($letter->kota_asal.', '.$letter->formatted_date_letter);
        $showRes->assertSee('di - '.$letter->kota_tujuan);

        // Verifikasi template PDF dan dokumen fisik memuat header banner, footer slogan, serta teks keamanan ringkas
        $renderedPdfView = view('admin.letters.pdf', [
            'letter' => $letter,
            'secQrBase64' => null,
            'chairQrBase64' => null,
            'kopLogoBase64' => null,
        ])->render();
        $this->assertStringContainsString('Dari Kontemplasi Menjadi Aksi', $renderedPdfView);
        $this->assertStringContainsString('header-bar', $renderedPdfView);
        $this->assertStringContainsString('footer-bar', $renderedPdfView);
        $this->assertStringContainsString($letter->kota_asal.', '.$letter->formatted_date_letter, $renderedPdfView);
        $this->assertStringContainsString('<u>'.$letter->kota_tujuan.'</u>', $renderedPdfView);
        $this->assertStringContainsString('Dokumen ini diterbitkan secara resmi melalui SIKAP-ISNU. Scan QRCode di atas untuk verifikasi otentisitas dokumen. Kode Surat: '.$letter->letter_code, $renderedPdfView);

        $pdfFullPath = Storage::disk('public')->path($letter->pdf_path);
        $fitzProcess = new Process(['python3', '-c', 'import fitz; doc = fitz.open("'.$pdfFullPath.'"); print(doc[0].get_text())']);
        $fitzProcess->run();
        $this->assertStringContainsString('Dari Kontemplasi Menjadi Aksi', $fitzProcess->getOutput());
        $this->assertStringContainsString($letter->kota_asal, $fitzProcess->getOutput());
    }

    /**
     * Test pemilihan jenis surat pada daftar surat, pembuatan Surat Mandat,
     * serta verifikasi template Word template_mandat.docx, pratinjau, dan PDF.
     */
    public function test_letter_mandat_creation_and_generation(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['name' => 'Admin PC Surat', 'role' => 'admin_kota']);
        $this->actingAs($admin);

        // 1. Verifikasi modal pemilihan jenis surat di index
        $indexRes = $this->get(route('admin.letters.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('chooseLetterTypeModal');
        $indexRes->assertSee('Surat Mandat');
        $indexRes->assertSee('Surat Rekomendasi');
        $indexRes->assertSee('Surat Dinas / Biasa');
        $indexRes->assertSee(route('admin.letters.create', ['type' => Letter::TYPE_SURAT_MANDAT]));
        $indexRes->assertSee(route('admin.letters.create', ['type' => Letter::TYPE_SURAT_REKOMENDASI]));

        // 2. Akses form create dengan parameter type=surat_mandat
        $createRes = $this->get(route('admin.letters.create', ['type' => Letter::TYPE_SURAT_MANDAT]));
        $createRes->assertStatus(200);
        $createRes->assertSee('Surat Mandat');
        $createRes->assertSee('Informasi Kegiatan yang Dimandatkan');
        $createRes->assertSee('Nama Kegiatan');
        $createRes->assertSee('Tanggal Pelaksanaan Kegiatan');
        $createRes->assertSee('Lokasi / Tempat Pelaksanaan');
        $createRes->assertSee('Tabel Penerima Mandat');

        // 3. Simpan Surat Mandat
        $tabelMandat = '<table class="table-bordered" border="1" style="width: 100%;">'.
            '<thead><tr><th>No</th><th>Nama Lengkap</th><th>NIRA / NIK</th><th>Jabatan</th></tr></thead>'.
            '<tbody>'.
            '<tr><td>1</td><td>Dr. Ahmad Fauzi, M.Pd.</td><td>3578012345670001</td><td>Wakil Sekretaris</td></tr>'.
            '<tr><td>2</td><td>Siti Aminah, M.Ag.</td><td>3578012345670002</td><td>Departemen Pendidikan</td></tr>'.
            '</tbody></table>';

        $postRes = $this->post(route('admin.letters.store'), [
            'level' => 'PC ISNU',
            'letter_type' => Letter::TYPE_SURAT_MANDAT,
            'letter_number' => '055/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'kota_asal' => 'Surabaya',
            'kegiatan' => 'Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim',
            'tanggal_pelaksanaan' => '14 - 16 November 2026',
            'lokasi_pelaksanaan' => 'Balai Diklat Keagamaan Surabaya',
            'isi_surat' => $tabelMandat,
            'action' => 'draft',
        ]);

        $letter = Letter::where('letter_number', '055/PC-VI/ISNU-SBY/XI/2026')->first();
        $this->assertNotNull($letter);
        $postRes->assertRedirect(route('admin.letters.show', $letter->id));

        $this->assertEquals(Letter::TYPE_SURAT_MANDAT, $letter->letter_type);
        $this->assertTrue($letter->isMandat());
        $this->assertFalse($letter->isBiasa());
        $this->assertFalse($letter->isRekomendasi());
        $this->assertEquals('Surat Mandat', $letter->letter_type_label);
        $this->assertEquals('Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim', $letter->kegiatan);
        $this->assertEquals('14 - 16 November 2026', $letter->tanggal_pelaksanaan);
        $this->assertEquals('Balai Diklat Keagamaan Surabaya', $letter->lokasi_pelaksanaan);

        // 4. Verifikasi file Word DOCX dihasilkan menggunakan template template_mandat.docx
        $this->assertNotNull($letter->docx_path);
        Storage::disk('public')->assertExists($letter->docx_path);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public')->path($letter->docx_path)));
        $docxXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('MANDAT', $docxXml);
        $this->assertStringContainsString('Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim', $docxXml);
        $this->assertStringContainsString('Balai Diklat Keagamaan Surabaya', $docxXml);
        $this->assertStringContainsString('Dr. Ahmad Fauzi, M.Pd.', $docxXml);

        // 5. Verifikasi halaman show menampilkan layout Surat Mandat
        $showRes = $this->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('SURAT MANDAT');
        $showRes->assertSee('Nomor : 055/PC-VI/ISNU-SBY/XI/2026');
        $showRes->assertDontSee('055/PC-VI/ISNU-SBY/XI/2026/PC-VI/ISNU-SBY');
        $showRes->assertSee('Yang bertandatangan dibawah ini adalah Pengurus Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya merekomendasikan kepada:');
        $showRes->assertSee('Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim');
        $showRes->assertSee('Balai Diklat Keagamaan Surabaya');
        $showRes->assertSee('Dr. Ahmad Fauzi, M.Pd.');
        $showRes->assertSee('Demikian surat mandat ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.');

        // 6. Verifikasi halaman edit saat draft memuat field kegiatan Surat Mandat
        $editRes = $this->get(route('admin.letters.edit', $letter->id));
        $editRes->assertStatus(200);
        $editRes->assertSee('Surat Mandat');
        $editRes->assertSee('Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim');
        $editRes->assertSee('Balai Diklat Keagamaan Surabaya');

        // 7. Verifikasi cetak PDF memuat format Surat Mandat
        $this->get(route('admin.letters.download-pdf', $letter->id))->assertStatus(403);
        $letter->status = Letter::STATUS_SELESAI;
        $letter->save();

        $downloadPdf = $this->get(route('admin.letters.download-pdf', $letter->id));
        $downloadPdf->assertStatus(200);

        $renderedPdfView = view('admin.letters.pdf', [
            'letter' => $letter,
            'secQrBase64' => null,
            'chairQrBase64' => null,
            'kopLogoBase64' => null,
        ])->render();
        $this->assertStringContainsString('SURAT MANDAT', $renderedPdfView);
        $this->assertStringContainsString('Nomor : 055/PC-VI/ISNU-SBY/XI/2026', $renderedPdfView);
        $this->assertStringNotContainsString('055/PC-VI/ISNU-SBY/XI/2026/PC-VI/ISNU-SBY', $renderedPdfView);
        $this->assertStringContainsString('Pelatihan Kader Tingkat Lanjut (PKTL) PW ISNU Jatim', $renderedPdfView);

        // 8. Verifikasi surat berstatus selesai sudah tidak bisa diedit kembali oleh siapapun (termasuk admin)
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);
    }

    /**
     * Test pembuatan Surat Rekomendasi, verifikasi template Word template_rekom.docx,
     * pratinjau, dan PDF.
     */
    public function test_letter_rekomendasi_creation_and_generation(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['name' => 'Admin PC Surat', 'role' => 'admin_kota']);
        $this->actingAs($admin);

        // 1. Akses form create type=surat_rekomendasi
        $createRes = $this->get(route('admin.letters.create', ['type' => Letter::TYPE_SURAT_REKOMENDASI]));
        $createRes->assertStatus(200);
        $createRes->assertSee('Surat Rekomendasi');
        $createRes->assertSee('Format Biodata Rekomendasi');

        // 2. Simpan Surat Rekomendasi
        $biodataRekom = '<table class="table-borderless" border="0" style="width: 100%;">'.
            '<tbody>'.
            '<tr><td>Nama Lengkap</td><td>:</td><td>Dr. H. Muhammad Ilyas, M.Si.</td></tr>'.
            '<tr><td>NIRA / NIA ISNU</td><td>:</td><td>ISNU-3578-2024-0012</td></tr>'.
            '<tr><td>Instansi</td><td>:</td><td>UIN Sunan Ampel Surabaya</td></tr>'.
            '</tbody></table>'.
            '<p>Adalah benar merupakan pengurus aktif PC ISNU Kota Surabaya dengan integritas tinggi dan kami rekomendasikan untuk program beasiswa doktoral.</p>';

        $postRes = $this->post(route('admin.letters.store'), [
            'level' => 'PC ISNU',
            'letter_type' => Letter::TYPE_SURAT_REKOMENDASI,
            'letter_number' => '077/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-09',
            'kota_asal' => 'Surabaya',
            'perihal' => 'Rekomendasi Studi Doktoral S3 Luar Negeri',
            'isi_surat' => $biodataRekom,
            'action' => 'draft',
        ]);

        $letter = Letter::where('letter_number', '077/PC-VI/ISNU-SBY/XI/2026')->first();
        $this->assertNotNull($letter);
        $postRes->assertRedirect(route('admin.letters.show', $letter->id));

        $this->assertEquals(Letter::TYPE_SURAT_REKOMENDASI, $letter->letter_type);
        $this->assertTrue($letter->isRekomendasi());
        $this->assertEquals('Surat Rekomendasi', $letter->letter_type_label);

        // 3. Verifikasi file Word DOCX dihasilkan menggunakan template template_rekom.docx
        $this->assertNotNull($letter->docx_path);
        Storage::disk('public')->assertExists($letter->docx_path);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public')->path($letter->docx_path)));
        $docxXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('SURAT REKOMENDASI', $docxXml);
        $this->assertStringContainsString('Dr. H. Muhammad Ilyas, M.Si.', $docxXml);
        $this->assertStringContainsString('ISNU-3578-2024-0012', $docxXml);

        // 4. Verifikasi halaman show menampilkan layout Surat Rekomendasi
        $showRes = $this->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('SURAT REKOMENDASI');
        $showRes->assertSee('Nomor : 077/PC-VI/ISNU-SBY/XI/2026');
        $showRes->assertDontSee('077/PC-VI/ISNU-SBY/XI/2026/PC-VI/ISNU-SBY');
        $showRes->assertSee('Yang bertandatangan dibawah ini adalah Pengurus Pimpinan Cabang Ikatan Sarjana Nahdlatul Ulama (PC ISNU) Kota Surabaya merekomendasikan kepada:');
        $showRes->assertSee('Dr. H. Muhammad Ilyas, M.Si.');
        $showRes->assertSee('Demikian surat rekomendasi ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.');

        // 5. Verifikasi cetak PDF terkunci saat status draft dan dapat diunduh saat selesai
        $this->get(route('admin.letters.download-pdf', $letter->id))->assertStatus(403);
        $letter->status = Letter::STATUS_SELESAI;
        $letter->save();

        $downloadPdf = $this->get(route('admin.letters.download-pdf', $letter->id));
        $downloadPdf->assertStatus(200);

        $renderedPdfView = view('admin.letters.pdf', [
            'letter' => $letter,
            'secQrBase64' => null,
            'chairQrBase64' => null,
            'kopLogoBase64' => null,
        ])->render();
        $this->assertStringContainsString('SURAT REKOMENDASI', $renderedPdfView);
        $this->assertStringContainsString('Nomor : 077/PC-VI/ISNU-SBY/XI/2026', $renderedPdfView);
        $this->assertStringNotContainsString('077/PC-VI/ISNU-SBY/XI/2026/PC-VI/ISNU-SBY', $renderedPdfView);
        $this->assertStringContainsString('Dr. H. Muhammad Ilyas, M.Si.', $renderedPdfView);
    }

    /**
     * Test hak akses penandatanganan:
     * - Hanya akun yang bertindak sebagai Sekretaris & Ketua (untuk surat level PC ISNU).
     * - Atau Sekretaris PAC & Ketua PAC (untuk surat level PAC ISNU di masing-masing wilayah).
     * - Admin tidak bisa menandatangani surat.
     * - File PDF baru dapat diunduh ketika surat sudah ditandatangani secara lengkap.
     */
    public function test_letter_signing_authorization_and_pac_level_restrictions(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin_kota']);

        // PAC 1 (Bubutan) & PAC 2 (Gubeng)
        $mwc = Mwc::first() ?? Mwc::create(['code' => 'MWC-TEST', 'name' => 'MWC Surabaya']);
        $pac1 = Pac::firstOrCreate(['name' => 'PAC ISNU Bubutan'], [
            'code' => 'PAC-TEST-01',
            'mwc_id' => $mwc->id,
            'kecamatan' => 'Bubutan',
            'city' => 'Kota Surabaya',
        ]);
        $pac2 = Pac::firstOrCreate(['name' => 'PAC ISNU Gubeng'], [
            'code' => 'PAC-TEST-02',
            'mwc_id' => $pac1->mwc_id,
            'kecamatan' => 'Gubeng',
            'city' => 'Kota Surabaya',
        ]);

        $helperCreateOfficer = function (string $name, string $title, string $level, ?int $pacId = null) {
            $user = User::factory()->create(['name' => $name, 'role' => 'member']);
            $member = Member::create([
                'user_id' => $user->id,
                'full_name' => $name,
                'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
                'phone' => '0812'.rand(10000000, 99999999),
                'nik' => '3578'.rand(100000000000, 999999999999),
                'gender' => 'L',
                'birth_place' => 'Surabaya',
                'birth_date' => '1985-01-01',
                'address' => 'Surabaya',
                'kelurahan' => 'Bubutan',
                'city' => 'Kota Surabaya',
                'kecamatan' => 'Kecamatan',
                'occupation' => 'Akademisi',
                'membership_status' => 'pengurus',
                'pac_id' => $pacId,
            ]);

            Position::create([
                'member_id' => $member->id,
                'position_title' => $title,
                'level' => $level,
                'pac_id' => $pacId,
                'start_date' => now()->subMonth(),
                'is_active' => true,
            ]);

            return $user;
        };

        // Setup PC Officers
        $pcSekretaris = $helperCreateOfficer('Sekretaris PC Test', 'Sekretaris', 'PC ISNU');
        $pcKetua = $helperCreateOfficer('Ketua PC Test', 'Ketua', 'PC ISNU');

        // Setup PAC 1 Officers (Bubutan)
        $pac1Sekretaris = $helperCreateOfficer('Sekretaris PAC Bubutan', 'Sekretaris', 'PAC ISNU', $pac1->id);
        $pac1Ketua = $helperCreateOfficer('Ketua PAC Bubutan', 'Ketua', 'PAC ISNU', $pac1->id);

        // Setup PAC 2 Officers (Gubeng)
        $pac2Sekretaris = $helperCreateOfficer('Sekretaris PAC Gubeng', 'Sekretaris', 'PAC ISNU', $pac2->id);
        $pac2Ketua = $helperCreateOfficer('Ketua PAC Gubeng', 'Ketua', 'PAC ISNU', $pac2->id);

        // -------------------------------------------------------------
        // BAGIAN 1: SURAT LEVEL PC ISNU
        // -------------------------------------------------------------
        $this->actingAs($pcSekretaris);
        $this->post(route('admin.letters.store'), [
            'level' => 'PC ISNU',
            'letter_number' => '201/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'perihal' => 'Surat Resmi PC ISNU',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Mitra Kerja PC',
            'isi_surat' => '<p>Isi surat PC ISNU</p>',
            'action' => 'submit_secretary',
        ])->assertRedirect();

        $pcLetter = Letter::where('letter_number', '201/PC-VI/ISNU-SBY/XI/2026')->firstOrFail();
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $pcLetter->status);
        $this->assertEquals('PC ISNU', $pcLetter->level);
        $this->assertNull($pcLetter->pac_id);

        // 1. PDF terkunci sebelum ditandatangani lengkap (403)
        $this->get(route('admin.letters.download-pdf', $pcLetter->id))->assertStatus(403);
        $this->get(route('surat.download-pdf', $pcLetter->letter_code))->assertStatus(403);

        // 2. Admin TIDAK BISA menandatangani surat (403)
        $this->actingAs($admin);
        $this->post(route('admin.letters.sign-secretary', $pcLetter->id))->assertStatus(403);
        $this->post(route('admin.letters.sign-chairman', $pcLetter->id))->assertStatus(403);

        // 3. Sekretaris PAC TIDAK BISA menandatangani surat PC (403)
        $this->actingAs($pac1Sekretaris);
        $this->post(route('admin.letters.sign-secretary', $pcLetter->id))->assertStatus(403);

        // 4. Sekretaris PC BISA menandatangani surat PC
        $this->actingAs($pcSekretaris);
        $this->post(route('admin.letters.sign-secretary', $pcLetter->id))->assertRedirect();
        $pcLetter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_KETUA, $pcLetter->status);

        // PDF masih terkunci karena baru tahap 1
        $this->get(route('admin.letters.download-pdf', $pcLetter->id))->assertStatus(403);

        // 5. Ketua PAC TIDAK BISA menandatangani surat PC (403)
        $this->actingAs($pac1Ketua);
        $this->post(route('admin.letters.sign-chairman', $pcLetter->id))->assertStatus(403);

        // 6. Admin juga TIDAK BISA menandatangani tahap Ketua (403)
        $this->actingAs($admin);
        $this->post(route('admin.letters.sign-chairman', $pcLetter->id))->assertStatus(403);

        // 7. Ketua PC BISA menandatangani surat PC
        $this->actingAs($pcKetua);
        $this->post(route('admin.letters.sign-chairman', $pcLetter->id))->assertRedirect();
        $pcLetter->refresh();
        $this->assertEquals(Letter::STATUS_SELESAI, $pcLetter->status);

        // 8. PDF baru dapat diunduh setelah status selesai
        $this->get(route('admin.letters.download-pdf', $pcLetter->id))->assertStatus(200);
        $this->get(route('surat.download-pdf', $pcLetter->letter_code))->assertStatus(200);

        // -------------------------------------------------------------
        // BAGIAN 2: SURAT LEVEL PAC ISNU (PAC BUBUTAN)
        // -------------------------------------------------------------
        $this->actingAs($pac1Sekretaris);
        $this->post(route('admin.letters.store'), [
            'level' => 'PAC ISNU',
            'pac_id' => $pac1->id,
            'letter_number' => '010/PAC-VI/ISNU-BBT/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'perihal' => 'Undangan Rapat PAC ISNU Bubutan',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Anggota PAC Bubutan',
            'isi_surat' => '<p>Undangan rapat anggota PAC Bubutan.</p>',
            'action' => 'submit_secretary',
        ])->assertRedirect();

        $pacLetter = Letter::where('letter_number', '010/PAC-VI/ISNU-BBT/XI/2026')->firstOrFail();
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $pacLetter->status);
        $this->assertEquals('PAC ISNU', $pacLetter->level);
        $this->assertEquals($pac1->id, $pacLetter->pac_id);
        $this->assertTrue($pacLetter->isLevelPac());

        // 1. PDF terkunci sebelum selesai (403)
        $this->get(route('admin.letters.download-pdf', $pacLetter->id))->assertStatus(403);
        $this->get(route('surat.download-pdf', $pacLetter->letter_code))->assertStatus(403);

        // 2. Admin TIDAK BISA menandatangani surat PAC (403)
        $this->actingAs($admin);
        $this->post(route('admin.letters.sign-secretary', $pacLetter->id))->assertStatus(403);

        // 3. Sekretaris PC TIDAK BISA menandatangani surat PAC (403)
        $this->actingAs($pcSekretaris);
        $this->post(route('admin.letters.sign-secretary', $pacLetter->id))->assertStatus(403);

        // 4. Sekretaris PAC Lain (PAC Gubeng) TIDAK BISA menandatangani surat PAC Bubutan (403)
        $this->actingAs($pac2Sekretaris);
        $this->post(route('admin.letters.sign-secretary', $pacLetter->id))->assertStatus(403);

        // 5. Sekretaris PAC Bubutan BISA menandatangani surat PAC Bubutan
        $this->actingAs($pac1Sekretaris);
        $this->post(route('admin.letters.sign-secretary', $pacLetter->id))->assertRedirect();
        $pacLetter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_KETUA, $pacLetter->status);
        $this->assertStringContainsString('PAC ISNU Bubutan', $pacLetter->secretary_position);

        // 6. Ketua PC TIDAK BISA menandatangani surat PAC (403)
        $this->actingAs($pcKetua);
        $this->post(route('admin.letters.sign-chairman', $pacLetter->id))->assertStatus(403);

        // 7. Ketua PAC Lain (PAC Gubeng) TIDAK BISA menandatangani surat PAC Bubutan (403)
        $this->actingAs($pac2Ketua);
        $this->post(route('admin.letters.sign-chairman', $pacLetter->id))->assertStatus(403);

        // 8. Ketua PAC Bubutan BISA menandatangani surat PAC Bubutan
        $this->actingAs($pac1Ketua);
        $this->post(route('admin.letters.sign-chairman', $pacLetter->id))->assertRedirect();
        $pacLetter->refresh();
        $this->assertEquals(Letter::STATUS_SELESAI, $pacLetter->status);
        $this->assertStringContainsString('PAC ISNU Bubutan', $pacLetter->chairman_position);

        // 9. PDF sekarang dapat diunduh (200)
        $this->get(route('admin.letters.download-pdf', $pacLetter->id))->assertStatus(200);
        $this->get(route('surat.download-pdf', $pacLetter->letter_code))->assertStatus(200);

        // 10. Verifikasi tampilan Kop dan Footer mencerminkan PAC ISNU Kecamatan BUBUTAN
        $showRes = $this->get(route('admin.letters.show', $pacLetter->id));
        $showRes->assertSee('PIMPINAN ANAK CABANG');
        $showRes->assertSee('KECAMATAN BUBUTAN');

        $renderedPdf = view('admin.letters.pdf', [
            'letter' => $pacLetter,
            'secQrBase64' => null,
            'chairQrBase64' => null,
            'kopLogoBase64' => null,
        ])->render();
        $this->assertStringContainsString('PIMPINAN ANAK CABANG', $renderedPdf);
        $this->assertStringContainsString('KECAMATAN BUBUTAN', $renderedPdf);
    }

    /**
     * Test live AJAX search pada konsep surat dan rendering cardview mobile:
     * - Tombol filter dihilangkan.
     * - Input search live dengan AJAX (X-Requested-With: XMLHttpRequest).
     * - Menampilkan data sesuai pencarian tanpa reload seluruh halaman.
     * - Terdapat tampilan cardview khusus mobile (d-md-none) dan tabel desktop (d-none d-md-block).
     */
    public function test_letter_ajax_live_search_and_mobile_cardview_rendering(): void
    {
        $admin = User::where('role', 'admin_kota')->first();
        $this->actingAs($admin);

        // Buat 2 surat dengan perihal dan nomor berbeda
        $letterA = Letter::create([
            'letter_number' => '101/PC-ISNU/XI/2026',
            'letter_code' => 'SRT-ALPHA-99',
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'roman_month' => 'XI',
            'year' => '2026',
            'tujuan_surat' => 'Rektor Unair',
            'kota_tujuan' => 'Surabaya',
            'kota_asal' => 'Surabaya',
            'perihal' => 'Undangan Diskusi Ilmiah Kampus',
            'date_letter' => now()->toDateString(),
            'isi_surat' => 'Isi surat A',
            'status' => Letter::STATUS_DRAFT,
            'user_id' => $admin->id,
        ]);

        $letterB = Letter::create([
            'letter_number' => '102/PC-ISNU/XI/2026',
            'letter_code' => 'SRT-BETA-88',
            'letter_type' => Letter::TYPE_SURAT_MANDAT,
            'level' => Letter::LEVEL_PC,
            'roman_month' => 'XI',
            'year' => '2026',
            'tujuan_surat' => 'Peserta Mandat',
            'kota_tujuan' => 'Surabaya',
            'kota_asal' => 'Surabaya',
            'kegiatan' => 'Pelatihan Jurnalistik ISNU',
            'perihal' => 'Surat Mandat Pelatihan',
            'date_letter' => now()->toDateString(),
            'isi_surat' => 'Isi surat B',
            'status' => Letter::STATUS_MENUNGGU_SEKRETARIS,
            'user_id' => $admin->id,
        ]);

        // 1. Akses halaman index non-AJAX
        $indexRes = $this->get(route('admin.letters.index'));
        $indexRes->assertStatus(200);

        // Verifikasi tombol filter lama (bi-funnel) sudah dihilangkan
        $indexRes->assertDontSee('bi-funnel');
        $indexRes->assertDontSee('isFiltering ? \'Memproses...\' : \'Filter\'', false);

        // Verifikasi search input dengan debounce dan fetch AJAX
        $indexRes->assertSee('@input.debounce.300ms="applyFilter()"', false);
        $indexRes->assertSee('lettersContainer', false);

        // Verifikasi filter jenis surat dalam bentuk dropdown
        $indexRes->assertSee('aria-label="Filter Jenis Surat"', false);
        $indexRes->assertSee('x-model="letterType"', false);
        $indexRes->assertSee('Semua Jenis Surat');
        $indexRes->assertSee('Surat Dinas / Biasa');
        $indexRes->assertSee('Surat Mandat');
        $indexRes->assertSee('Surat Rekomendasi');

        // Verifikasi terdapat struktur desktop table (d-none d-md-block) dan mobile cardview (d-md-none)
        $indexRes->assertSee('d-none d-md-block table-responsive', false);
        $indexRes->assertSee('d-md-none p-3 d-flex flex-column gap-3', false);
        $indexRes->assertSee('101/PC-ISNU/XI/2026');
        $indexRes->assertSee('102/PC-ISNU/XI/2026');

        // 2. Akses pencarian via AJAX untuk query 'Diskusi'
        $ajaxResA = $this->getJson(route('admin.letters.index', ['search' => 'Diskusi']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResA->assertStatus(200);
        $ajaxContentA = $ajaxResA->getContent();

        // Surat A harus ada, Surat B tidak ada
        $this->assertStringContainsString('101/PC-ISNU/XI/2026', $ajaxContentA);
        $this->assertStringContainsString('Undangan Diskusi Ilmiah Kampus', $ajaxContentA);
        $this->assertStringNotContainsString('102/PC-ISNU/XI/2026', $ajaxContentA);

        // Verifikasi AJAX mengembalikan partial (tanpa layout induk)
        $this->assertStringNotContainsString('<!DOCTYPE html>', $ajaxContentA);
        $this->assertStringContainsString('d-md-none', $ajaxContentA);
        $this->assertStringContainsString('d-none d-md-block table-responsive', $ajaxContentA);

        // 3. Akses pencarian via AJAX untuk kegiatan 'Jurnalistik' (Surat Mandat)
        $ajaxResB = $this->getJson(route('admin.letters.index', ['search' => 'Jurnalistik']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResB->assertStatus(200);
        $ajaxContentB = $ajaxResB->getContent();

        $this->assertStringContainsString('102/PC-ISNU/XI/2026', $ajaxContentB);
        $this->assertStringContainsString('Pelatihan Jurnalistik ISNU', $ajaxContentB);
        $this->assertStringNotContainsString('101/PC-ISNU/XI/2026', $ajaxContentB);

        // 4. Akses filter jenis surat via AJAX untuk 'surat_mandat'
        $ajaxResMandat = $this->getJson(route('admin.letters.index', ['letter_type' => 'surat_mandat']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResMandat->assertStatus(200);
        $ajaxContentMandat = $ajaxResMandat->getContent();

        $this->assertStringContainsString('102/PC-ISNU/XI/2026', $ajaxContentMandat);
        $this->assertStringNotContainsString('101/PC-ISNU/XI/2026', $ajaxContentMandat);

        // 5. Akses filter jenis surat via AJAX untuk 'surat_biasa'
        $ajaxResBiasa = $this->getJson(route('admin.letters.index', ['letter_type' => 'surat_biasa']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResBiasa->assertStatus(200);
        $ajaxContentBiasa = $ajaxResBiasa->getContent();

        $this->assertStringContainsString('101/PC-ISNU/XI/2026', $ajaxContentBiasa);
        $this->assertStringNotContainsString('102/PC-ISNU/XI/2026', $ajaxContentBiasa);
    }

    /**
     * Test aturan otorisasi edit surat:
     * 1. Surat yang sudah ditandatangani lengkap (status selesai) tidak bisa diedit oleh user apapun.
     * 2. Surat yang sudah di-TTD sekretaris lalu dibatalkan/ditolak ketua (status ditolak) masih bisa diedit kembali.
     * 3. Surat hanya bisa diedit oleh: pembuat surat, sekretaris, ketua, admin.
     * 4. Di luar itu tidak bisa mengedit surat.
     * 5. Wakil sekretaris dan wakil ketua dapat mengedit surat yang diajukan oleh anggota seksinya.
     */
    public function test_letter_edit_authorization_and_rejection_resubmission_rules(): void
    {
        Storage::fake('public');

        // Setup Seksi A dan Seksi B
        $seksiA = Section::create([
            'name' => 'Seksi Pendidikan dan Pengkaderan',
            'code' => 'SPP',
            'level' => 'PC ISNU',
            'description' => 'Seksi Pendidikan',
        ]);
        $seksiB = Section::create([
            'name' => 'Seksi Riset dan Teknologi',
            'code' => 'SRT',
            'level' => 'PC ISNU',
            'description' => 'Seksi Riset',
        ]);

        $helperCreateUserOfficer = function (string $name, string $role, ?string $positionTitle = null, ?int $sectionId = null) {
            $user = User::factory()->create(['name' => $name, 'role' => $role]);
            if ($positionTitle) {
                $member = Member::create([
                    'user_id' => $user->id,
                    'member_number' => '13.01.2024.'.rand(1000, 9999),
                    'nik' => '3578'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
                    'full_name' => $name,
                    'email' => $user->email,
                    'phone' => '0812'.rand(10000000, 99999999),
                    'occupation' => 'Akademisi',
                    'birth_place' => 'Surabaya',
                    'birth_date' => '1988-01-01',
                    'gender' => 'L',
                    'address' => 'Jl. Pengurus No. 10',
                    'kelurahan' => 'Wonokromo',
                    'kecamatan' => 'Wonokromo',
                    'membership_status' => 'pengurus',
                ]);

                Position::create([
                    'member_id' => $member->id,
                    'position_title' => $positionTitle,
                    'section_id' => $sectionId,
                    'level' => 'PC ISNU',
                    'start_date' => now()->subMonth(),
                    'is_active' => true,
                ]);
            }

            return $user;
        };

        // Users:
        // Admin
        $superAdmin = $helperCreateUserOfficer('Super Admin Test', 'super_admin');
        $adminKota = $helperCreateUserOfficer('Admin Kota Test', 'admin_kota');

        // Pimpinan PC
        $ketua = $helperCreateUserOfficer('Ketua PC Test', 'member', 'Ketua');
        $sekretaris = $helperCreateUserOfficer('Sekretaris PC Test', 'member', 'Sekretaris');

        // Pengurus Seksi A
        $creatorAnggotaSeksiA = $helperCreateUserOfficer('Pembuat Anggota Seksi A', 'member', 'Anggota Seksi', $seksiA->id);
        $wakilKetuaSeksiA = $helperCreateUserOfficer('Wakil Ketua Seksi A', 'member', 'Wakil Ketua', $seksiA->id);
        $wakilSekretarisSeksiA = $helperCreateUserOfficer('Wakil Sekretaris Seksi A', 'member', 'Wakil Sekretaris', $seksiA->id);
        $koordinatorSeksiA = $helperCreateUserOfficer('Koordinator Seksi A', 'member', 'Koordinator Seksi', $seksiA->id);

        // Pengurus Seksi B
        $anggotaSeksiB = $helperCreateUserOfficer('Anggota Seksi B', 'member', 'Anggota Seksi', $seksiB->id);
        $wakilKetuaSeksiB = $helperCreateUserOfficer('Wakil Ketua Seksi B', 'member', 'Wakil Ketua', $seksiB->id);
        $wakilSekretarisSeksiB = $helperCreateUserOfficer('Wakil Sekretaris Seksi B', 'member', 'Wakil Sekretaris', $seksiB->id);

        // Surat diajukan oleh creatorAnggotaSeksiA
        $letter = Letter::create([
            'user_id' => $creatorAnggotaSeksiA->id,
            'letter_code' => 'SRT-20261110-EDIT01',
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '088/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'lampiran' => '-',
            'perihal' => 'Pengajuan Permohonan Narasumber Diklat',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Rektor Universitas',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => 'Mohon kesediaan menjadi narasumber.',
            'status' => Letter::STATUS_MENUNGGU_KETUA,
            'secretary_user_id' => $sekretaris->id,
            'secretary_name' => $sekretaris->name,
            'secretary_signed_at' => now(),
            'secretary_qr_path' => 'qr/sec_dummy.png',
        ]);

        // Simpan file dummy QR sekretaris
        Storage::disk('public')->put('qr/sec_dummy.png', 'fake_qr_data');

        // Ketua menolak surat (status berubah menjadi 'ditolak')
        $this->actingAs($ketua);
        $rejectRes = $this->post(route('admin.letters.reject', $letter->id), [
            'rejection_note' => 'Perbaiki perihal surat dan lampiran susunan acara.',
        ]);
        $rejectRes->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_DITOLAK, $letter->status);
        $this->assertEquals('Perbaiki perihal surat dan lampiran susunan acara.', $letter->rejection_note);

        // 1. Verifikasi pihak yang BERHAK mengedit surat ditolak ini:
        // - Pembuat surat (creatorAnggotaSeksiA)
        $this->actingAs($creatorAnggotaSeksiA);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        // - Sekretaris
        $this->actingAs($sekretaris);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        // - Ketua
        $this->actingAs($ketua);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        // - Admin (Admin Kota & Super Admin)
        $this->actingAs($adminKota);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);
        $this->actingAs($superAdmin);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        // - Wakil Ketua dan Wakil Sekretaris dari Seksi yang SAMA (Seksi A)
        $this->actingAs($wakilKetuaSeksiA);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        $this->actingAs($wakilSekretarisSeksiA);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(200);

        // 2. Verifikasi pihak yang TIDAK BERHAK mengedit surat ini (HTTP 403):
        // - Anggota Seksi B (seksi berbeda & bukan pembuat)
        $this->actingAs($anggotaSeksiB);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);
        $this->put(route('admin.letters.update', $letter->id), ['isi_surat' => 'Hacker edit'])->assertStatus(403);

        // - Wakil Ketua dan Wakil Sekretaris dari Seksi B (seksi berbeda dengan pembuat)
        $this->actingAs($wakilKetuaSeksiB);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);

        $this->actingAs($wakilSekretarisSeksiB);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);

        // - Koordinator Seksi A yang bukan pembuat surat
        $this->actingAs($koordinatorSeksiA);
        $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);

        // 3. Wakil Sekretaris Seksi A mengedit surat dan mengajukan ulang ke Sekretaris
        $this->actingAs($wakilSekretarisSeksiA);
        $updateRes = $this->put(route('admin.letters.update', $letter->id), [
            'level' => 'PC ISNU',
            'letter_number' => '088/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'lampiran' => '1 Berkas',
            'perihal' => 'Pengajuan Permohonan Narasumber Diklat Terpadu',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Rektor Universitas',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => 'Mohon kesediaan menjadi narasumber utama dalam diklat terpadu.',
            'action' => 'submit_secretary',
        ]);
        $updateRes->assertRedirect(route('admin.letters.show', $letter->id));

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_MENUNGGU_SEKRETARIS, $letter->status);
        $this->assertNull($letter->rejection_note);
        $this->assertNull($letter->secretary_user_id);
        $this->assertNull($letter->secretary_signed_at);
        $this->assertNull($letter->secretary_qr_path);
        Storage::disk('public')->assertMissing('qr/sec_dummy.png');

        // 4. Sekretaris & Ketua menandatangani surat hingga berstatus selesai
        $this->actingAs($sekretaris);
        $this->post(route('admin.letters.sign-secretary', $letter->id))->assertRedirect();

        $this->actingAs($ketua);
        $this->post(route('admin.letters.sign-chairman', $letter->id))->assertRedirect();

        $letter->refresh();
        $this->assertEquals(Letter::STATUS_SELESAI, $letter->status);
        $this->assertNotNull($letter->secretary_signed_at);
        $this->assertNotNull($letter->chairman_signed_at);

        // 5. Verifikasi bahwa surat yang SUDAH SELESAI TIDAK BISA DIEDIT OLEH USER APAPUN
        $allUsers = [
            $creatorAnggotaSeksiA,
            $sekretaris,
            $ketua,
            $wakilSekretarisSeksiA,
            $wakilKetuaSeksiA,
            $adminKota,
            $superAdmin,
        ];

        foreach ($allUsers as $u) {
            $this->actingAs($u);
            $this->get(route('admin.letters.edit', $letter->id))->assertStatus(403);
            $this->put(route('admin.letters.update', $letter->id), [
                'level' => 'PC ISNU',
                'letter_number' => '088/PC-VI/ISNU-SBY/XI/2026',
                'roman_month' => 'XI',
                'year' => '2026',
                'date_letter' => '2026-11-10',
                'kota_asal' => 'Surabaya',
                'isi_surat' => 'Percobaan edit terlarang.',
            ])->assertStatus(403);
        }
    }
}
