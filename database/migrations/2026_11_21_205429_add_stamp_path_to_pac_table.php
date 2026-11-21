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
        if (! Schema::hasColumn('pac', 'stamp_path')) {
            Schema::table('pac', function (Blueprint $table) {
                $table->string('stamp_path')->nullable()->after('kecamatan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pac', 'stamp_path')) {
            Schema::table('pac', function (Blueprint $table) {
                $table->dropColumn('stamp_path');
            });
        }
    }
};
