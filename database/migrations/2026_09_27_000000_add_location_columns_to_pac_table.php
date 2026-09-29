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
        Schema::table('pac', function (Blueprint $table) {
            if (! Schema::hasColumn('pac', 'province')) {
                $table->string('province')->nullable()->after('name');
            }
            if (! Schema::hasColumn('pac', 'city')) {
                $table->string('city')->nullable()->after('province');
            }
            if (! Schema::hasColumn('pac', 'kecamatan')) {
                $table->string('kecamatan')->nullable()->after('city');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pac', function (Blueprint $table) {
            if (Schema::hasColumn('pac', 'province')) {
                $table->dropColumn(['province', 'city', 'kecamatan']);
            }
        });
    }
};
