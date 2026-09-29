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
        // 1. MWC (Majelis Wakil Cabang - Tingkat Kecamatan)
        Schema::create('mwc', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('city')->default('Kota Surabaya');
            $table->timestamps();
        });

        // 2. PAC (Pimpinan Anak Cabang - Tingkat Kelurahan/Wilayah)
        Schema::create('pac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mwc_id')->constrained('mwc')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // 3. Members Table
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('member_number')->nullable()->unique(); // e.g. ISNU-SBY-26-000001
            $table->string('nik')->nullable()->unique();
            $table->string('full_name');
            $table->string('birth_place');
            $table->date('birth_date');
            $table->enum('gender', ['L', 'P']);
            $table->text('address');
            $table->string('kelurahan');
            $table->string('kecamatan');
            $table->string('city')->default('Kota Surabaya');
            $table->string('phone');
            $table->string('email');
            $table->string('occupation'); // Pekerjaan saat ini
            $table->string('photo')->nullable();
            $table->enum('membership_status', [
                'calon',
                'menunggu_verifikasi',
                'terverifikasi',
                'perbaikan',
                'ditolak',
                'pengurus',
            ])->default('menunggu_verifikasi');

            $table->foreignId('mwc_id')->nullable()->constrained('mwc')->nullOnDelete();
            $table->foreignId('pac_id')->nullable()->constrained('pac')->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_note')->nullable();

            $table->timestamps();
        });

        // 4. Member Educations (Riwayat Pendidikan)
        Schema::create('member_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('level'); // SD, SMP, SMA/SMK/MA, Diploma, S1, S2, S3, Pendidikan lainnya
            $table->string('institution_name');
            $table->string('major')->nullable(); // Program Studi
            $table->integer('start_year')->nullable();
            $table->integer('end_year')->nullable();
            $table->string('degree')->nullable(); // Gelar (misal: S.T., M.Pd.)
            $table->timestamps();
        });

        // 5. Member Organizations (Riwayat Organisasi)
        Schema::create('member_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('organization_name');
            $table->string('position')->nullable();
            $table->string('level')->nullable(); // Kota, MWC, Wilayah, dll
            $table->string('period')->nullable(); // misal: 2020-2024
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 6. Member Employments (Riwayat Pekerjaan)
        Schema::create('member_employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('position');
            $table->string('field')->nullable(); // Bidang
            $table->integer('start_year')->nullable();
            $table->integer('end_year')->nullable();
            $table->string('employment_status')->nullable(); // ASN, TNI/Polri, BUMN, BUMD, Swasta, Profesional, Wirausaha, Akademisi, Tenaga Pendidik, Tenaga Kesehatan, Freelancer, Lainnya
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 7. Member NU Trainings (Riwayat Kaderisasi NU)
        Schema::create('member_nu_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('training_type'); // MAKESTA, PKD, PKL, PKN, Pendidikan Kader lainnya, Kaderisasi khusus NU, Pelatihan/Lokakarya NU
            $table->string('organizer')->nullable();
            $table->integer('year')->nullable();
            $table->string('location')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('certificate_file')->nullable();
            $table->timestamps();
        });

        // 8. Member Certifications (Sertifikasi Keahlian)
        Schema::create('member_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('certification_name');
            $table->string('issuing_organization')->nullable();
            $table->string('certificate_number')->nullable();
            $table->integer('issue_year')->nullable();
            $table->string('valid_until')->nullable();
            $table->string('field')->nullable(); // IT, Manajemen, Akuntansi, Hukum, Kesehatan, Pendidikan, Teknik, Keuangan, Digital Marketing, Bahasa, Halal, Kewirausahaan, dll
            $table->string('certificate_file')->nullable();
            $table->timestamps();
        });

        // 9. Positions / Kepengurusan Details
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('position_title'); // Ketua, Wakil Ketua, Sekretaris, Bendahara, Anggota Departemen
            $table->enum('level', ['Kota', 'MWC', 'PAC'])->default('Kota');
            $table->foreignId('mwc_id')->nullable()->constrained('mwc')->nullOnDelete();
            $table->foreignId('pac_id')->nullable()->constrained('pac')->nullOnDelete();
            $table->string('period')->nullable(); // misal: 2026-2030
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('sk_number')->nullable();
            $table->string('sk_file')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 10. Status History Tracking
        Schema::create('membership_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 11. Cards (Kartu Anggota & Kartu Pengurus)
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('card_number');
            $table->enum('card_type', ['MEMBER', 'OFFICER'])->default('MEMBER');
            $table->string('qr_token')->unique(); // UUID / Token acak untuk QR Code
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 12. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('membership_status_histories');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('member_certifications');
        Schema::dropIfExists('member_nu_trainings');
        Schema::dropIfExists('member_employments');
        Schema::dropIfExists('member_organizations');
        Schema::dropIfExists('member_educations');
        Schema::dropIfExists('members');
        Schema::dropIfExists('pac');
        Schema::dropIfExists('mwc');
    }
};
