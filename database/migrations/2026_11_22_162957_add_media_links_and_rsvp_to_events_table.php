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
        Schema::table('events', function (Blueprint $table) {
            $table->json('media_links')->nullable()->after('documentation_photos');
            $table->boolean('rsvp_enabled')->default(true)->after('presence_end_at');
            $table->timestamp('rsvp_deadline_at')->nullable()->after('rsvp_enabled');
        });

        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('nik_or_member_number', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('institution_or_pac')->nullable();
            $table->string('attendance_status', 20)->default('hadir');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['event_id', 'attendance_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_rsvps');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['media_links', 'rsvp_enabled', 'rsvp_deadline_at']);
        });
    }
};
