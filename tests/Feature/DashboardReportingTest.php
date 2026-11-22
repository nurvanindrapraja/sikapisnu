<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPresence;
use App\Models\FinancialTransaction;
use App\Models\Letter;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_kegiatan_keuangan_persuratan_and_merchandise_reporting_data_and_charts(): void
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin Dashboard',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $buyer = User::factory()->create([
            'name' => 'Anggota Pembeli',
            'role' => 'member',
            'is_active' => true,
        ]);

        $member = Member::create([
            'user_id' => $buyer->id,
            'member_number' => 'ISNU-SBY-26-000101',
            'nik' => '3578010101900101',
            'full_name' => 'Anggota Pembeli',
            'email' => $buyer->email,
            'phone' => '081234560101',
            'occupation' => 'Dosen',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Bubutan VI/2',
            'kelurahan' => 'Bubutan',
            'kecamatan' => 'Bubutan',
            'membership_status' => 'terverifikasi',
        ]);

        // 1. Kegiatan & Presensi
        $event = Event::create([
            'title' => 'Rapat Kerja Cabang ISNU 2026',
            'unique_code' => 'EVT-RAKER-2026',
            'location' => 'Kantor PCNU Surabaya',
            'method' => 'luring',
            'event_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'presence_start_at' => now()->subHour(),
            'presence_end_at' => now()->addDays(4),
            'budget' => 5000000,
            'created_by' => $admin->id,
        ]);

        EventPresence::create([
            'event_id' => $event->id,
            'user_id' => $buyer->id,
            'member_id' => $member->id,
            'name' => $member->full_name,
            'attended_at' => now(),
        ]);

        // 2. Keuangan
        FinancialTransaction::create([
            'transaction_code' => 'KM-20261122-0001',
            'type' => FinancialTransaction::TYPE_IN,
            'category' => 'Donasi / Infaq',
            'amount' => 3500000,
            'transaction_date' => now()->toDateString(),
            'title' => 'Infaq Donatur PC ISNU',
            'payment_method' => 'Transfer Bank',
            'level' => 'PC ISNU',
            'user_id' => $admin->id,
        ]);

        FinancialTransaction::create([
            'transaction_code' => 'KK-20261122-0002',
            'type' => FinancialTransaction::TYPE_OUT,
            'category' => 'Konsumsi Rapat & Koordinasi',
            'amount' => 1000000,
            'transaction_date' => now()->toDateString(),
            'title' => 'Konsumsi Rapat Persiapan',
            'payment_method' => 'Tunai / Kas',
            'level' => 'PC ISNU',
            'user_id' => $admin->id,
        ]);

        // 3. Persuratan
        Letter::create([
            'user_id' => $admin->id,
            'letter_code' => 'SRT-DASH-001',
            'letter_type' => Letter::TYPE_SURAT_BIASA,
            'level' => Letter::LEVEL_PC,
            'letter_number' => '005/PC-VI/ISNU-SBY/XI/2026',
            'roman_month' => 'XI',
            'year' => '2026',
            'date_letter' => now()->toDateString(),
            'perihal' => 'Undangan Rakercab 2026',
            'kota_asal' => 'Surabaya',
            'tujuan_surat' => 'Ketua PAC ISNU Se-Surabaya',
            'kota_tujuan' => 'Surabaya',
            'isi_surat' => '<p>Undangan resmi rapat kerja.</p>',
            'status' => Letter::STATUS_SELESAI,
        ]);

        // 4. Merchandise
        $product = Product::create([
            'name' => 'Batik Resmi ISNU Surabaya',
            'slug' => 'batik-resmi-isnu-surabaya',
            'category' => 'batik',
            'price' => 250000,
            'stock' => 20,
            'is_active' => true,
        ]);

        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $buyer->id,
            'member_id' => $member->id,
            'total_amount' => 500000,
            'shipping_cost' => 0,
            'grand_total' => 500000,
            'recipient_name' => $member->full_name,
            'recipient_phone' => $member->phone,
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PAID,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 250000,
            'quantity' => 2,
            'subtotal' => 500000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // KPI & Data checks
        $response->assertSee('Reporting Organisasi (Kegiatan, Keuangan, Persuratan &amp; Merchandise)', false);
        $response->assertSee('Rapat Kerja Cabang ISNU 2026');
        $response->assertSee('Rp 2.500.000'); // Saldo kas: 3.500.000 - 1.000.000
        $response->assertSee('Infaq Donatur PC ISNU');
        $response->assertSee('005/PC-VI/ISNU-SBY/XI/2026');
        $response->assertSee('Batik Resmi ISNU Surabaya');
        $response->assertSee('Rp 500.000');

        // Chart canvases
        $response->assertSee('id="chartEvents"', false);
        $response->assertSee('id="chartFinances"', false);
        $response->assertSee('id="chartLettersTrend"', false);
        $response->assertSee('id="chartLettersStatus"', false);
        $response->assertSee('id="chartMerchandise"', false);
    }
}
