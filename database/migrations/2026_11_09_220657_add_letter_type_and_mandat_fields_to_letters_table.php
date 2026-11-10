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
        Schema::table('letters', function (Blueprint $table) {
            $table->string('letter_type', 30)->default('surat_biasa')->after('letter_code')->index();
            $table->string('tujuan_surat')->nullable()->change();
            $table->string('kegiatan')->nullable()->after('perihal');
            $table->string('tanggal_pelaksanaan')->nullable()->after('kegiatan');
            $table->string('lokasi_pelaksanaan')->nullable()->after('tanggal_pelaksanaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn(['letter_type', 'kegiatan', 'tanggal_pelaksanaan', 'lokasi_pelaksanaan']);
        });
    }
};
