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
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('password');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('photo');
        });

        Schema::table('letters', function (Blueprint $table) {
            $table->string('secretary_signature_path')->nullable()->after('secretary_qr_path');
            $table->string('chairman_signature_path')->nullable()->after('chairman_qr_path');
            $table->string('stamp_path')->nullable()->after('chairman_signature_path');
            $table->string('verification_qr_path')->nullable()->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn([
                'secretary_signature_path',
                'chairman_signature_path',
                'stamp_path',
                'verification_qr_path',
            ]);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
