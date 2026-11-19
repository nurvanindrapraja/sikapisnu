<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('lainnya'); // seragam, batik, kaos, pin, buku, aksesoris, lainnya
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('weight_grams')->default(250);
            $table->integer('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('shipping_address');
            $table->text('notes')->nullable();

            // Status alur pesanan
            $table->string('status')->default('pending_payment'); // pending_payment, payment_uploaded, paid, shipped, received, cancelled

            // Info Pembayaran Non-Tunai
            $table->string('payment_method')->default('transfer_bank');
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_uploaded_at')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Info Pengiriman oleh Bendahara
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('shipping_notes')->nullable();

            // Info Penerimaan (Selesai) oleh Pembeli/Penerima
            $table->timestamp('received_at')->nullable();
            $table->string('receipt_proof_path')->nullable(); // Foto bukti diterima (opsional)
            $table->text('receipt_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('product_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('variant_note')->nullable();
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('product_orders')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->default(5); // 1-5 bintang
            $table->text('comment');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_order_items');
        Schema::dropIfExists('product_orders');
        Schema::dropIfExists('products');
    }
};
