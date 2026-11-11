<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\Letter;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceAndSuperAdminLetterDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(string $name, string $role, ?string $positionTitle = null, ?int $sectionId = null): User
    {
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
    }

    /**
     * 1. Test fasilitas hapus surat meski surat sudah di-ttd (selesai), khusus untuk superadmin.
     */
    public function test_superadmin_can_delete_signed_letter_while_others_are_forbidden(): void
    {
        Storage::fake('public');

        $superAdmin = $this->createOfficer('Super Admin', 'super_admin');
        $adminKota = $this->createOfficer('Admin Kota', 'admin_kota');
        $ketua = $this->createOfficer('Ketua PC', 'member', 'Ketua');
        $sekretaris = $this->createOfficer('Sekretaris PC', 'member', 'Sekretaris');
        $creator = $this->createOfficer('Pembuat Surat', 'member', 'Anggota Seksi');

        // Buat surat yang sudah berstatus selesai (sudah di-TTD lengkap)
        $letter = Letter::create([
            'user_id' => $creator->id,
            'letter_code' => 'SRT-20261110-DEL01',
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '099/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => '2026-11-10',
            'lampiran' => '-',
            'perihal' => 'Undangan Silaturahim Selesai TTD',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Rektor ITS',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => 'Surat resmi yang sudah ditandatangani lengkap.',
            'status' => Letter::STATUS_SELESAI,
            'secretary_user_id' => $sekretaris->id,
            'secretary_name' => $sekretaris->name,
            'secretary_signed_at' => now(),
            'secretary_qr_path' => 'qr/sec_test.png',
            'chairman_user_id' => $ketua->id,
            'chairman_name' => $ketua->name,
            'chairman_signed_at' => now(),
            'chairman_qr_path' => 'qr/chair_test.png',
            'docx_path' => 'letters/test.docx',
            'pdf_path' => 'letters/test.pdf',
        ]);

        Storage::disk('public')->put('qr/sec_test.png', 'qr_sec_data');
        Storage::disk('public')->put('qr/chair_test.png', 'qr_chair_data');
        Storage::disk('public')->put('letters/test.docx', 'docx_data');
        Storage::disk('public')->put('letters/test.pdf', 'pdf_data');

        // A. Coba hapus oleh pihak selain Super Admin -> WAJIB 403 Forbidden
        $forbiddenUsers = [$creator, $adminKota, $ketua, $sekretaris];
        foreach ($forbiddenUsers as $user) {
            $this->actingAs($user);
            $res = $this->delete(route('admin.letters.destroy', $letter->id));
            $res->assertStatus(403);
            $this->assertDatabaseHas('letters', ['id' => $letter->id]);
        }

        // B. Verifikasi halaman show bagi Super Admin menampilkan tombol Hapus
        $this->actingAs($superAdmin);
        $showRes = $this->get(route('admin.letters.show', $letter->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('data-bs-target="#deleteLetterModal"', false);
        $showRes->assertSee('Peringatan Khusus Super Admin');

        // C. Super Admin melakukan penghapusan surat selesai
        $delRes = $this->delete(route('admin.letters.destroy', $letter->id));
        $delRes->assertRedirect(route('admin.letters.index'));
        $delRes->assertSessionHas('success');

        // Verifikasi database dan berkas terhapus
        $this->assertDatabaseMissing('letters', ['id' => $letter->id]);
        Storage::disk('public')->assertMissing('qr/sec_test.png');
        Storage::disk('public')->assertMissing('qr/chair_test.png');
        Storage::disk('public')->assertMissing('letters/test.docx');
        Storage::disk('public')->assertMissing('letters/test.pdf');
    }

    /**
     * 2. Test otorisasi akses menu keuangan: hanya Bendahara dan Admin.
     */
    public function test_finance_management_access_authorization(): void
    {
        $superAdmin = $this->createOfficer('Super Admin', 'super_admin');
        $adminKota = $this->createOfficer('Admin Kota', 'admin_kota');
        $bendahara = $this->createOfficer('Bendahara PC', 'member', 'Bendahara');
        $wakilBendahara = $this->createOfficer('Wakil Bendahara PC', 'member', 'Wakil Bendahara');

        $anggotaSeksi = $this->createOfficer('Anggota Seksi', 'member', 'Anggota Seksi');
        $koordinatorSeksi = $this->createOfficer('Koordinator Seksi', 'member', 'Koordinator Seksi');

        $regularMember = User::factory()->create(['name' => 'Member Biasa', 'role' => 'member']);
        Member::create([
            'user_id' => $regularMember->id,
            'member_number' => '13.01.2024.9991',
            'nik' => '3578999900000001',
            'full_name' => 'Member Biasa',
            'email' => $regularMember->email,
            'phone' => '081299998888',
            'occupation' => 'Akademisi',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Regular',
            'kelurahan' => 'Wonokromo',
            'kecamatan' => 'Wonokromo',
            'membership_status' => 'terverifikasi',
        ]);

        // Pengguna Berhak: Bendahara, Wakil Bendahara, Admin Kota, Super Admin
        $allowedUsers = [$bendahara, $wakilBendahara, $adminKota, $superAdmin];
        foreach ($allowedUsers as $u) {
            $this->actingAs($u);
            $this->get(route('admin.finances.index'))->assertStatus(200);
            $this->get(route('admin.finances.create'))->assertStatus(200);
            $this->get(route('admin.finances.export'))->assertStatus(200);
        }

        // Pengguna Tidak Berhak: Anggota Seksi, Koordinator Seksi, Member Biasa -> 403
        $forbiddenUsers = [$anggotaSeksi, $koordinatorSeksi, $regularMember];
        foreach ($forbiddenUsers as $u) {
            $this->actingAs($u);
            $this->get(route('admin.finances.index'))->assertStatus(403);
            $this->get(route('admin.finances.create'))->assertStatus(403);
            $this->get(route('admin.finances.export'))->assertStatus(403);
        }
    }

    /**
     * 3. Test pencatatan Uang Masuk dan Uang Keluar serta kalkulasi saldo kas.
     */
    public function test_bendahara_and_admin_can_record_income_and_expense_with_balance_calculations(): void
    {
        Storage::fake('public');

        $bendahara = $this->createOfficer('Bendahara Kas', 'member', 'Bendahara');
        $this->actingAs($bendahara);

        $fakeProofIn = UploadedFile::fake()->create('bukti_transfer_iuran.jpg', 300, 'image/jpeg');

        // 1. Catat Uang Masuk (Iuran Kas Anggota) Rp 5.000.000
        $resIn = $this->post(route('admin.finances.store'), [
            'type' => FinancialTransaction::TYPE_IN,
            'category' => 'Iuran Kas Anggota',
            'amount' => 5000000,
            'transaction_date' => '2026-11-10',
            'title' => 'Iuran Kas Wajib Pengurus Periode November',
            'description' => 'Iuran dari 50 pengurus aktif masing-masing Rp 100.000 via transfer BCA',
            'payment_method' => 'Transfer Bank',
            'proof_file' => $fakeProofIn,
            'level' => 'PC ISNU',
        ]);
        $resIn->assertRedirect(route('admin.finances.index'));
        $resIn->assertSessionHas('success');

        $trxIn = FinancialTransaction::where('type', FinancialTransaction::TYPE_IN)->first();
        $this->assertNotNull($trxIn);
        $this->assertStringStartsWith('KM-', $trxIn->transaction_code);
        $this->assertEquals(5000000, (float) $trxIn->amount);
        $this->assertEquals('Rp 5.000.000', $trxIn->formatted_amount);
        $this->assertTrue($trxIn->isIncome());
        $this->assertFalse($trxIn->isExpense());
        $this->assertNotNull($trxIn->proof_path);
        Storage::disk('public')->assertExists($trxIn->proof_path);

        // 2. Catat Uang Keluar (Konsumsi Rapat) Rp 1.500.000
        $fakeProofOut = UploadedFile::fake()->create('nota_konsumsi.png', 200, 'image/png');
        $resOut = $this->post(route('admin.finances.store'), [
            'type' => FinancialTransaction::TYPE_OUT,
            'category' => 'Konsumsi Rapat & Koordinasi',
            'amount' => 1500000,
            'transaction_date' => '2026-11-10',
            'title' => 'Pembelian Konsumsi Rapat Kerja PC ISNU',
            'description' => 'Nasi kotak dan snack rapat koordinasi di Graha NU',
            'payment_method' => 'Tunai / Kas',
            'proof_file' => $fakeProofOut,
            'level' => 'PC ISNU',
        ]);
        $resOut->assertRedirect(route('admin.finances.index'));

        $trxOut = FinancialTransaction::where('type', FinancialTransaction::TYPE_OUT)->first();
        $this->assertNotNull($trxOut);
        $this->assertStringStartsWith('KK-', $trxOut->transaction_code);
        $this->assertEquals(1500000, (float) $trxOut->amount);
        $this->assertTrue($trxOut->isExpense());
        Storage::disk('public')->assertExists($trxOut->proof_path);

        // 3. Verifikasi Saldo Kas di Halaman Index:
        // Pemasukan: 5.000.000, Pengeluaran: 1.500.000 => Saldo Bersih: 3.500.000
        $indexRes = $this->get(route('admin.finances.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Rp 3.500.000');
        $indexRes->assertSee('Rp 5.000.000');
        $indexRes->assertSee('Rp 1.500.000');
        $indexRes->assertSee('Iuran Kas Wajib Pengurus Periode November');
        $indexRes->assertSee('Pembelian Konsumsi Rapat Kerja PC ISNU');
    }

    /**
     * 4. Test pembaruan transaksi, penghapusan, dan export CSV laporan keuangan.
     */
    public function test_finance_update_delete_and_csv_export(): void
    {
        Storage::fake('public');

        $admin = $this->createOfficer('Admin PC Kas', 'admin_kota');
        $this->actingAs($admin);

        $fakeProof = UploadedFile::fake()->create('struk_atk.jpg', 150, 'image/jpeg');

        // Buat transaksi awal
        $trx = FinancialTransaction::create([
            'transaction_code' => 'KK-20261110-TEST',
            'type' => FinancialTransaction::TYPE_OUT,
            'category' => 'ATK, Cetak & Kesekretariatan',
            'amount' => 750000,
            'transaction_date' => '2026-11-10',
            'title' => 'Pembelian Kertas HVS dan Tinta Printer',
            'description' => 'Untuk kebutuhan surat menyurat PC ISNU',
            'payment_method' => 'Tunai / Kas',
            'proof_path' => $fakeProof->store('finances/proofs', 'public'),
            'level' => 'PC ISNU',
            'user_id' => $admin->id,
        ]);

        // A. Lihat detail
        $showRes = $this->get(route('admin.finances.show', $trx->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('KK-20261110-TEST');
        $showRes->assertSee('Rp 750.000');
        $showRes->assertSee('Pembelian Kertas HVS dan Tinta Printer');

        // B. Sunting transaksi
        $editRes = $this->get(route('admin.finances.edit', $trx->id));
        $editRes->assertStatus(200);

        $updateRes = $this->put(route('admin.finances.update', $trx->id), [
            'type' => FinancialTransaction::TYPE_OUT,
            'category' => 'ATK, Cetak & Kesekretariatan',
            'amount' => 800000,
            'transaction_date' => '2026-11-10',
            'title' => 'Pembelian Kertas HVS, Tinta, dan Map Sertifikat',
            'description' => 'Rincian nota tambahan map sertifikat kader',
            'payment_method' => 'Tunai / Kas',
            'level' => 'PC ISNU',
        ]);
        $updateRes->assertRedirect(route('admin.finances.show', $trx->id));

        $trx->refresh();
        $this->assertEquals(800000, (float) $trx->amount);
        $this->assertStringContainsString('Map Sertifikat', $trx->title);

        // C. Export CSV
        $exportRes = $this->get(route('admin.finances.export'));
        $exportRes->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $exportRes->headers->get('content-type'));

        // D. Hapus transaksi
        $delRes = $this->delete(route('admin.finances.destroy', $trx->id));
        $delRes->assertRedirect(route('admin.finances.index'));
        $this->assertSoftDeleted('financial_transactions', ['id' => $trx->id]);
    }

    /**
     * 5. Test AJAX live search dan filtering tipe transaksi.
     */
    public function test_finance_ajax_filtering_by_type_and_search(): void
    {
        $bendahara = $this->createOfficer('Bendahara Live', 'member', 'Bendahara');
        $this->actingAs($bendahara);

        // Buat 1 Uang Masuk dan 1 Uang Keluar
        FinancialTransaction::create([
            'transaction_code' => 'KM-20261110-0001',
            'type' => FinancialTransaction::TYPE_IN,
            'category' => 'Donasi / Infaq',
            'amount' => 10000000,
            'transaction_date' => '2026-11-10',
            'title' => 'Infaq Pembangunan Laboratorium Sains ISNU',
            'payment_method' => 'Transfer Bank',
            'level' => 'PC ISNU',
            'user_id' => $bendahara->id,
        ]);

        FinancialTransaction::create([
            'transaction_code' => 'KK-20261110-0002',
            'type' => FinancialTransaction::TYPE_OUT,
            'category' => 'Pelaksanaan Kegiatan & Acara',
            'amount' => 4000000,
            'transaction_date' => '2026-11-10',
            'title' => 'Sewa Sound System dan Tenda Seminar Nasional',
            'payment_method' => 'Tunai / Kas',
            'level' => 'PC ISNU',
            'user_id' => $bendahara->id,
        ]);

        // A. Filter Tipe = 'in' via AJAX
        $ajaxIn = $this->getJson(route('admin.finances.index', ['type' => 'in']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxIn->assertStatus(200);
        $contentIn = $ajaxIn->getContent();
        $this->assertStringContainsString('Infaq Pembangunan Laboratorium', $contentIn);
        $this->assertStringNotContainsString('Sewa Sound System dan Tenda', $contentIn);

        // B. Filter Tipe = 'out' via AJAX
        $ajaxOut = $this->getJson(route('admin.finances.index', ['type' => 'out']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxOut->assertStatus(200);
        $contentOut = $ajaxOut->getContent();
        $this->assertStringContainsString('Sewa Sound System dan Tenda', $contentOut);
        $this->assertStringNotContainsString('Infaq Pembangunan Laboratorium', $contentOut);

        // C. Live Search Query 'Seminar' via AJAX
        $ajaxSearch = $this->getJson(route('admin.finances.index', ['search' => 'Seminar']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxSearch->assertStatus(200);
        $contentSearch = $ajaxSearch->getContent();
        $this->assertStringContainsString('Sewa Sound System dan Tenda', $contentSearch);
        $this->assertStringNotContainsString('Infaq Pembangunan Laboratorium', $contentSearch);
    }

    /**
     * 7. Test preview popup modal untuk Konsep Surat dan Transaksi Keuangan.
     */
    public function test_letter_preview_modal_content_endpoint(): void
    {
        $superadmin = $this->createOfficer('Super Admin', 'super_admin');
        $this->actingAs($superadmin);

        $letter = Letter::create([
            'user_id' => $superadmin->id,
            'letter_code' => 'SRT-20261111-TEST01',
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '001/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => 2026,
            'date_letter' => '2026-11-11',
            'perihal' => 'Undangan Rapat Koordinasi',
            'tujuan_surat' => 'Seluruh Pengurus Harian',
            'isi_surat' => '<p>Diharapkan kehadirannya dalam rapat koordinasi bulanan.</p>',
            'status' => Letter::STATUS_DRAFT,
        ]);

        $response = $this->get(route('admin.letters.preview', $letter->id), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Undangan Rapat Koordinasi');
        $response->assertSee('001/PC-VI/ISNU-SBY/XI/2026');
        $response->assertSee('Seluruh Pengurus Harian');
        $response->assertSee('IKATAN SARJANA NAHDLATUL');
    }

    public function test_financial_transaction_preview_modal_content_endpoint(): void
    {
        $bendahara = $this->createOfficer('Bendahara Kas', 'member', 'Bendahara');
        $this->actingAs($bendahara);

        $transaction = FinancialTransaction::create([
            'transaction_code' => 'KAS-IN-20261111-PRVW',
            'type' => FinancialTransaction::TYPE_IN,
            'category' => 'Infaq & Donasi',
            'amount' => 2500000,
            'transaction_date' => '2026-11-11',
            'title' => 'Infaq Sukarela Pengurus',
            'description' => 'Diserahkan langsung secara tunai.',
            'payment_method' => 'Tunai / Kas',
            'level' => 'PC ISNU',
            'user_id' => $bendahara->id,
        ]);

        $response = $this->get(route('admin.finances.preview', $transaction->id), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertSee('KAS-IN-20261111-PRVW');
        $response->assertSee('Infaq Sukarela Pengurus');
        $response->assertSee('Rp 2.500.000');
        $response->assertSee('Diserahkan langsung secara tunai.');
    }
}
