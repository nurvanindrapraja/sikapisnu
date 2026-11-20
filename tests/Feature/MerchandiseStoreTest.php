<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Position;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchandiseStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createMember(User $user, array $overrides = []): Member
    {
        return Member::create(array_merge([
            'user_id' => $user->id,
            'full_name' => $user->name ?? 'Anggota ISNU',
            'nik' => '3578'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
            'email' => $user->email,
            'phone' => '0812345'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
            'occupation' => 'Akademisi',
            'birth_place' => 'Surabaya',
            'birth_date' => '1990-01-01',
            'gender' => 'L',
            'address' => 'Jl. Bubutan No. 2, Surabaya',
            'kelurahan' => 'Bubutan',
            'kecamatan' => 'Bubutan',
            'membership_status' => 'terverifikasi',
        ], $overrides));
    }

    public function test_public_store_catalog_is_accessible(): void
    {
        $response = $this->get(route('store.index'));

        $response->assertStatus(200);
        $response->assertSee('Merchandise Resmi');
        $response->assertSee('Jas Resmi ISNU Nasional');
        $response->assertSee('Batik Premium Khas ISNU Surabaya');
    }

    public function test_public_store_filter_by_category(): void
    {
        $response = $this->get(route('store.index', ['category' => 'seragam']));

        $response->assertStatus(200);
        $response->assertSee('Jas Resmi ISNU Nasional');
    }

    public function test_public_product_detail_is_accessible(): void
    {
        $product = Product::where('slug', 'jas-resmi-isnu-nasional')->first();
        $this->assertNotNull($product);

        $response = $this->get(route('store.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($product->formatted_price);
        $response->assertSee('Khusus Anggota ISNU');
    }

    public function test_logged_in_member_can_access_store_catalog_and_detail_without_error(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createMember($user, ['membership_status' => 'terverifikasi']);

        // Catalog
        $catalogResponse = $this->actingAs($user)->get(route('store.index'));
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Anda terverifikasi sebagai Anggota Aktif ISNU');

        // Product Detail
        $product = Product::where('slug', 'jas-resmi-isnu-nasional')->first();
        $detailResponse = $this->actingAs($user)->get(route('store.show', $product->slug));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Buat Pesanan (Non-Tunai)');
    }

    public function test_member_dashboard_has_left_sidebar_navigation(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createMember($user, ['membership_status' => 'terverifikasi']);

        $response = $this->actingAs($user)->get(route('member.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('PORTAL ANGGOTA');
        $response->assertSee('sidebar');
        $response->assertSee('Toko Merchandise');
        $response->assertSee('Pesanan Saya');
        $response->assertSee('Katalog Anggota');
    }

    public function test_guest_is_prompted_to_register_or_login(): void
    {
        $product = Product::first();

        $response = $this->get(route('store.show', $product->slug));
        $response->assertStatus(200);
        $response->assertSee(route('register'));
        $response->assertSee(route('login'));

        // Checkout without login redirects to login
        $checkoutResponse = $this->post(route('member.orders.checkout'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'recipient_name' => 'Tamu',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Jl. Surabaya',
        ]);

        $checkoutResponse->assertRedirect(route('login'));
    }

    public function test_unverified_member_cannot_checkout(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createMember($user, [
            'full_name' => 'Calon Anggota',
            'membership_status' => 'menunggu_verifikasi',
        ]);

        $product = Product::first();

        $response = $this->actingAs($user)->post(route('member.orders.checkout'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'recipient_name' => 'Calon Anggota',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Surabaya',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_verified_active_member_can_checkout_product(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $member = $this->createMember($user, [
            'full_name' => 'Achmad Sholeh, M.Pd.',
            'phone' => '081298765432',
            'address' => 'Jl. Darmo No. 45, Wonokromo, Surabaya',
        ]);

        $product = Product::first();
        $initialStock = $product->stock;

        $response = $this->actingAs($user)->post(route('member.orders.checkout'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'variant_note' => 'Ukuran XL',
            'recipient_name' => 'Achmad Sholeh',
            'recipient_phone' => '081298765432',
            'shipping_address' => 'Jl. Darmo No. 45, Wonokromo, Surabaya',
            'notes' => 'Tolong dibungkus rapi.',
        ]);

        $order = ProductOrder::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(ProductOrder::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertEquals($product->price * 2, $order->grand_total);

        // Check stock decremented
        $this->assertEquals($initialStock - 2, $product->fresh()->stock);

        $response->assertRedirect(route('member.orders.show', $order->id));
        $response->assertSessionHas('success');
    }

    public function test_member_can_upload_payment_proof(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_active' => true]);
        $member = $this->createMember($user, [
            'full_name' => 'Siti Nurhaliza, S.E.',
        ]);

        $product = Product::first();
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $user->id,
            'member_id' => $member->id,
            'total_amount' => $product->price,
            'grand_total' => $product->price,
            'recipient_name' => 'Siti Nurhaliza',
            'recipient_phone' => '081211112222',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PENDING_PAYMENT,
        ]);

        $file = UploadedFile::fake()->image('bukti_transfer.jpg');

        $response = $this->actingAs($user)->post(route('member.orders.upload_payment', $order->id), [
            'payment_proof' => $file,
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(ProductOrder::STATUS_PAYMENT_UPLOADED, $order->status);
        $this->assertNotNull($order->payment_proof_path);
        Storage::disk('public')->assertExists($order->payment_proof_path);
    }

    public function test_bendahara_can_verify_and_approve_payment(): void
    {
        // Setup Bendahara User
        $bendaharaUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $bendaharaMember = $this->createMember($bendaharaUser, [
            'full_name' => 'Bendahara PC ISNU',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $bendaharaMember->id,
            'position_title' => 'Bendahara',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        $this->assertTrue($bendaharaUser->isBendahara());
        $this->assertTrue($bendaharaUser->canManageProductOrders());

        // Create pending payment uploaded order
        $buyer = User::factory()->create(['is_active' => true]);
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $buyer->id,
            'total_amount' => 150000,
            'grand_total' => 150000,
            'recipient_name' => 'Pembeli',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PAYMENT_UPLOADED,
            'payment_proof_path' => 'dummy.jpg',
            'payment_uploaded_at' => now(),
        ]);

        // Bendahara views orders
        $indexResponse = $this->actingAs($bendaharaUser)->get(route('admin.product_orders.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($order->order_number);

        // Bendahara approves payment
        $approveResponse = $this->actingAs($bendaharaUser)->post(route('admin.product_orders.verify_payment', $order->id), [
            'action' => 'approve',
        ]);

        $approveResponse->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(ProductOrder::STATUS_PAID, $order->status);
        $this->assertEquals($bendaharaUser->id, $order->payment_verified_by);
    }

    public function test_bendahara_can_update_shipping(): void
    {
        $bendaharaUser = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $bendaharaMember = $this->createMember($bendaharaUser, [
            'full_name' => 'Wakil Bendahara PC ISNU',
            'membership_status' => 'pengurus',
        ]);
        Position::create([
            'member_id' => $bendaharaMember->id,
            'position_title' => 'Wakil Bendahara',
            'level' => 'PC ISNU',
            'period' => '2026-2030',
            'is_active' => true,
        ]);

        $buyer = User::factory()->create(['is_active' => true]);
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $buyer->id,
            'total_amount' => 150000,
            'grand_total' => 150000,
            'recipient_name' => 'Pembeli',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PAID,
        ]);

        $response = $this->actingAs($bendaharaUser)->post(route('admin.product_orders.update_shipping', $order->id), [
            'courier_name' => 'JNE REG',
            'tracking_number' => 'JNE1234567890SBY',
            'shipping_notes' => 'Estimasi tiba 1-2 hari kerja.',
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(ProductOrder::STATUS_SHIPPED, $order->status);
        $this->assertEquals('JNE REG', $order->courier_name);
        $this->assertEquals('JNE1234567890SBY', $order->tracking_number);
    }

    public function test_member_can_confirm_receipt_with_optional_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_active' => true]);
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $user->id,
            'total_amount' => 150000,
            'grand_total' => 150000,
            'recipient_name' => 'Penerima',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_SHIPPED,
            'courier_name' => 'Kurir PC ISNU',
            'shipped_at' => now(),
        ]);

        $receiptPhoto = UploadedFile::fake()->image('unboxing.jpg');

        $response = $this->actingAs($user)->post(route('member.orders.confirm_received', $order->id), [
            'receipt_proof' => $receiptPhoto,
            'receipt_notes' => 'Barang diterima dalam kondisi baik & lengkap.',
        ]);

        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(ProductOrder::STATUS_RECEIVED, $order->status);
        $this->assertNotNull($order->received_at);
        $this->assertNotNull($order->receipt_proof_path);
        Storage::disk('public')->assertExists($order->receipt_proof_path);
    }

    public function test_buyer_can_submit_5_star_review_and_comment(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $product = Product::first();

        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $user->id,
            'total_amount' => $product->price,
            'grand_total' => $product->price,
            'recipient_name' => 'Penerima',
            'recipient_phone' => '08123456789',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_RECEIVED,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('member.orders.submit_review', $order->id), [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Bahan jas sangat premium, bordir logo ISNU rapi dan elegan!',
        ]);

        $response->assertSessionHas('success');

        $review = ProductReview::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($review);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Bahan jas sangat premium, bordir logo ISNU rapi dan elegan!', $review->comment);

        // Check review appears on public product page
        $publicResponse = $this->get(route('store.show', $product->slug));
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Bahan jas sangat premium, bordir logo ISNU rapi dan elegan!');
    }

    public function test_admin_pcnu_can_manage_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin_kota', 'is_active' => true]);
        $this->assertTrue($admin->canManageProducts());

        // Admin creates product
        $createResponse = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Songkok Hitam Logo Emas ISNU',
            'category' => 'aksesoris',
            'price' => 75000,
            'stock' => 50,
            'weight_grams' => 200,
            'description' => 'Songkok beludru halus dengan bordir logo emas ISNU.',
            'is_active' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.products.index'));

        $newProduct = Product::where('name', 'Songkok Hitam Logo Emas ISNU')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals(75000, $newProduct->price);

        // Toggle status
        $toggleResponse = $this->actingAs($admin)->patch(route('admin.products.toggle_status', $newProduct->id));
        $toggleResponse->assertSessionHas('success');
        $this->assertFalse($newProduct->fresh()->is_active);

        // Regular member cannot manage products
        $regularUser = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $forbiddenResponse = $this->actingAs($regularUser)->get(route('admin.products.index'));
        $forbiddenResponse->assertStatus(403);
    }

    public function test_member_store_and_catalog_render_with_member_sidebar_layout(): void
    {
        $user = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $this->createMember($user, ['membership_status' => 'terverifikasi']);

        // Member accessing Toko Merchandise retains member sidebar
        $storeResponse = $this->actingAs($user)->get(route('member.store.index'));
        $storeResponse->assertStatus(200);
        $storeResponse->assertSee('PORTAL ANGGOTA');
        $storeResponse->assertSee('Profil Saya');
        $storeResponse->assertSee('Toko Merchandise');
        $storeResponse->assertSee('Pesanan Saya');

        // Member accessing /toko directly also retains member sidebar
        $publicStoreResponse = $this->actingAs($user)->get(route('store.index'));
        $publicStoreResponse->assertStatus(200);
        $publicStoreResponse->assertSee('PORTAL ANGGOTA');
        $publicStoreResponse->assertSee('Profil Saya');

        // Member accessing product detail retains member sidebar
        $product = Product::first();
        $detailResponse = $this->actingAs($user)->get(route('member.store.show', $product->slug));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('PORTAL ANGGOTA');
        $detailResponse->assertSee('Profil Saya');

        // Member accessing Katalog Anggota retains member sidebar
        $catalogResponse = $this->actingAs($user)->get(route('member.katalog.index'));
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('PORTAL ANGGOTA');
        $catalogResponse->assertSee('Profil Saya');
        $catalogResponse->assertSee('Katalog Anggota');

        // Member accessing /daftar-anggota directly also retains member sidebar
        $publicCatalogResponse = $this->actingAs($user)->get(route('daftar.anggota'));
        $publicCatalogResponse->assertStatus(200);
        $publicCatalogResponse->assertSee('PORTAL ANGGOTA');
        $publicCatalogResponse->assertSee('Profil Saya');

        // Guest accessing /toko and /daftar-anggota does not see member sidebar
        auth()->logout();
        $guestStoreResponse = $this->get(route('store.index'));
        $guestStoreResponse->assertStatus(200);
        $guestStoreResponse->assertDontSee('PORTAL ANGGOTA');

        $guestCatalogResponse = $this->get(route('daftar.anggota'));
        $guestCatalogResponse->assertStatus(200);
        $guestCatalogResponse->assertDontSee('PORTAL ANGGOTA');
    }

    public function test_member_sidebar_user_card_has_proper_gap_between_photo_and_text(): void
    {
        $user = User::factory()->create(['name' => 'Nurvan Indra Praja', 'role' => 'member', 'is_active' => true]);
        $this->createMember($user, ['membership_status' => 'terverifikasi']);

        $response = $this->actingAs($user)->get(route('member.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('gap-3');
        $response->assertDontSee('gap-2.5');
    }

    public function test_admin_or_bendahara_can_view_product_order_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin_kota', 'is_active' => true]);
        $buyer = User::factory()->create(['name' => 'Nurvan Indra Praja', 'is_active' => true]);
        $this->createMember($buyer, ['membership_status' => 'terverifikasi', 'phone' => '081330278721']);

        $product = Product::first();
        $order = ProductOrder::create([
            'order_number' => 'ORD-20261119-6MTJM',
            'user_id' => $buyer->id,
            'total_amount' => 85000,
            'grand_total' => 85000,
            'recipient_name' => 'Nurvan Indra Praja',
            'recipient_phone' => '081330278721',
            'shipping_address' => 'Surabaya, Jawa Timur',
            'status' => ProductOrder::STATUS_PAYMENT_UPLOADED,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Peci Songkok Beludru Hitam',
            'price' => 85000,
            'quantity' => 1,
            'subtotal' => 85000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.product_orders.show', $order->id));

        $response->assertStatus(200);
        $response->assertSee('ORD-20261119-6MTJM');
        $response->assertSee('Nurvan Indra Praja');
        $response->assertSee('Peci Songkok Beludru Hitam');
        $response->assertSee('Rp 85.000');
        $response->assertSee('Menunggu Konfirmasi Bendahara');
    }

    public function test_ajax_product_management_operations(): void
    {
        $admin = User::factory()->create(['role' => 'admin_kota', 'is_active' => true]);

        // 1. AJAX Filter Products
        $filterResponse = $this->actingAs($admin)->getJson(route('admin.products.index', ['category' => 'seragam']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertJsonStructure(['success', 'html', 'total']);

        // 2. AJAX Create Product
        $createResponse = $this->actingAs($admin)->postJson(route('admin.products.store'), [
            'name' => 'Pin Kuningan Logo ISNU Premium',
            'category' => 'pin',
            'price' => 25000,
            'stock' => 100,
            'weight_grams' => 50,
            'description' => 'Pin peniti kuningan elegan.',
            'is_active' => 1,
        ]);
        $createResponse->assertStatus(200);
        $createResponse->assertJson(['success' => true]);

        $product = Product::where('name', 'Pin Kuningan Logo ISNU Premium')->first();
        $this->assertNotNull($product);

        // 3. AJAX Fetch for Edit Modal
        $editResponse = $this->actingAs($admin)->getJson(route('admin.products.edit', $product->id));
        $editResponse->assertStatus(200);
        $editResponse->assertJson([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => 'Pin Kuningan Logo ISNU Premium',
                'price' => 25000,
            ],
        ]);

        // 4. AJAX Update Product
        $updateResponse = $this->actingAs($admin)->putJson(route('admin.products.update', $product->id), [
            'name' => 'Pin Kuningan Logo ISNU Premium V2',
            'category' => 'pin',
            'price' => 30000,
            'stock' => 80,
            'weight_grams' => 50,
            'description' => 'Pin peniti kuningan diperbarui.',
            'is_active' => 1,
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);
        $this->assertEquals(30000, $product->fresh()->price);

        // 5. AJAX Toggle Status
        $toggleResponse = $this->actingAs($admin)->patchJson(route('admin.products.toggle_status', $product->id));
        $toggleResponse->assertStatus(200);
        $toggleResponse->assertJson(['success' => true, 'is_active' => false]);
        $this->assertFalse($product->fresh()->is_active);

        // 6. AJAX Delete Product
        $deleteResponse = $this->actingAs($admin)->deleteJson(route('admin.products.destroy', $product->id));
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['success' => true]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_ajax_order_management_operations(): void
    {
        $admin = User::factory()->create(['role' => 'admin_kota', 'is_active' => true]);
        $buyer = User::factory()->create(['name' => 'Ahmad Fauzi', 'is_active' => true]);
        $this->createMember($buyer, ['membership_status' => 'terverifikasi']);

        $product = Product::first();
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $buyer->id,
            'total_amount' => 100000,
            'grand_total' => 100000,
            'recipient_name' => 'Ahmad Fauzi',
            'recipient_phone' => '081234567890',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PAYMENT_UPLOADED,
        ]);

        // 1. AJAX Filter Orders
        $filterResponse = $this->actingAs($admin)->getJson(route('admin.product_orders.index'));
        $filterResponse->assertStatus(200);
        $filterResponse->assertJsonStructure(['success', 'html', 'stats', 'total']);

        // 2. AJAX Approve Payment
        $approveResponse = $this->actingAs($admin)->postJson(route('admin.product_orders.verify_payment', $order->id), [
            'action' => 'approve',
        ]);
        $approveResponse->assertStatus(200);
        $approveResponse->assertJson(['success' => true, 'action' => 'approve']);
        $this->assertEquals(ProductOrder::STATUS_PAID, $order->fresh()->status);

        // 3. AJAX Update Shipping
        $shippingResponse = $this->actingAs($admin)->postJson(route('admin.product_orders.update_shipping', $order->id), [
            'courier_name' => 'J&T Express',
            'tracking_number' => 'JT9988776655',
            'shipping_notes' => 'Dikirim hari ini.',
        ]);
        $shippingResponse->assertStatus(200);
        $shippingResponse->assertJson(['success' => true]);
        $this->assertEquals(ProductOrder::STATUS_SHIPPED, $order->fresh()->status);
        $this->assertEquals('JT9988776655', $order->fresh()->tracking_number);

        // 4. AJAX Mark Received
        $receivedResponse = $this->actingAs($admin)->postJson(route('admin.product_orders.mark_received', $order->id), [
            'receipt_notes' => 'Diserahterimakan langsung.',
        ]);
        $receivedResponse->assertStatus(200);
        $receivedResponse->assertJson(['success' => true]);
        $this->assertEquals(ProductOrder::STATUS_RECEIVED, $order->fresh()->status);
    }

    public function test_ajax_member_order_operations(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['name' => 'Dewi Sartika', 'is_active' => true]);
        $this->createMember($buyer, ['membership_status' => 'terverifikasi']);

        $product = Product::first();
        $order = ProductOrder::create([
            'order_number' => ProductOrder::generateOrderNumber(),
            'user_id' => $buyer->id,
            'total_amount' => 120000,
            'grand_total' => 120000,
            'recipient_name' => 'Dewi Sartika',
            'recipient_phone' => '081233445566',
            'shipping_address' => 'Surabaya',
            'status' => ProductOrder::STATUS_PENDING_PAYMENT,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        // 1. AJAX Upload Payment Proof
        $file = UploadedFile::fake()->image('struk_transfer.png');
        $uploadResponse = $this->actingAs($buyer)->postJson(route('member.orders.upload_payment', $order->id), [
            'payment_proof' => $file,
        ]);
        $uploadResponse->assertStatus(200);
        $uploadResponse->assertJson(['success' => true]);
        $this->assertEquals(ProductOrder::STATUS_PAYMENT_UPLOADED, $order->fresh()->status);

        // Simulate admin ship
        $order->update(['status' => ProductOrder::STATUS_SHIPPED]);

        // 2. AJAX Confirm Received
        $confirmResponse = $this->actingAs($buyer)->postJson(route('member.orders.confirm_received', $order->id), [
            'receipt_notes' => 'Barang sudah sampai dengan selamat.',
        ]);
        $confirmResponse->assertStatus(200);
        $confirmResponse->assertJson(['success' => true]);
        $this->assertEquals(ProductOrder::STATUS_RECEIVED, $order->fresh()->status);

        // 3. AJAX Submit Review
        $reviewResponse = $this->actingAs($buyer)->postJson(route('member.orders.submit_review', $order->id), [
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Kualitas produk sangat memuaskan!',
        ]);
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertJson(['success' => true, 'rating' => 5]);
    }
}
