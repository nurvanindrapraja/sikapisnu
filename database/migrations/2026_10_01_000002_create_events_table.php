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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('unique_code')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->enum('method', ['daring', 'luring'])->default('luring');
            $table->string('meeting_link')->nullable();
            $table->date('event_date');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->dateTime('presence_start_at');
            $table->dateTime('presence_end_at');
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            $table->string('lpj_file')->nullable();
            $table->json('documentation_photos')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
