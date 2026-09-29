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
        Schema::table('card_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('card_orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('printed_at');
            }
            if (! Schema::hasColumn('card_orders', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('delivered_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_orders', function (Blueprint $table) {
            if (Schema::hasColumn('card_orders', 'shipped_at')) {
                $table->dropColumn('shipped_at');
            }
            if (Schema::hasColumn('card_orders', 'received_at')) {
                $table->dropColumn('received_at');
            }
        });
    }
};
