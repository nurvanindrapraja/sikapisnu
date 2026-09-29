<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');

            Schema::create('card_orders_temp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
                $table->string('status', 30)->default('pending');
                $table->text('shipping_address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('ordered_at')->nullable();
                $table->timestamp('printed_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });

            if (Schema::hasTable('card_orders')) {
                DB::statement('INSERT INTO card_orders_temp (id, member_id, status, shipping_address, phone, notes, ordered_at, printed_at, shipped_at, delivered_at, received_at, created_at, updated_at) SELECT id, member_id, status, shipping_address, phone, notes, ordered_at, printed_at, shipped_at, delivered_at, received_at, created_at, updated_at FROM card_orders;');
                Schema::drop('card_orders');
            }

            Schema::rename('card_orders_temp', 'card_orders');
            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('card_orders', function (Blueprint $table) {
                $table->string('status', 30)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
