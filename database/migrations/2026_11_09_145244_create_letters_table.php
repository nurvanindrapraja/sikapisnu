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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('letter_code')->unique()->index();
            $table->string('letter_number');
            $table->string('roman_month', 10);
            $table->string('year', 4);
            $table->date('date_letter');
            $table->string('lampiran')->default('-');
            $table->string('perihal');
            $table->string('tujuan_surat');
            $table->string('kota_tujuan')->default('Surabaya');
            $table->text('isi_surat');
            $table->enum('status', [
                'draft',
                'menunggu_sekretaris',
                'menunggu_ketua',
                'selesai',
                'ditolak',
            ])->default('draft');
            $table->text('rejection_note')->nullable();

            // Penandatanganan Sekretaris
            $table->foreignId('secretary_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('secretary_name')->nullable();
            $table->string('secretary_registration_number')->nullable();
            $table->string('secretary_position')->nullable();
            $table->timestamp('secretary_signed_at')->nullable();
            $table->string('secretary_qr_path')->nullable();

            // Penandatanganan Ketua
            $table->foreignId('chairman_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('chairman_name')->nullable();
            $table->string('chairman_registration_number')->nullable();
            $table->string('chairman_position')->nullable();
            $table->timestamp('chairman_signed_at')->nullable();
            $table->string('chairman_qr_path')->nullable();

            // File path hasil generate
            $table->string('docx_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
