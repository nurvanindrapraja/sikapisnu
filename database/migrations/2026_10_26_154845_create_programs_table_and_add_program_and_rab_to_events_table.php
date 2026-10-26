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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('indicator')->nullable();
            $table->text('target')->nullable();
            $table->text('description')->nullable();
            $table->string('year', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('description')->constrained('sections')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->after('section_id')->constrained('programs')->nullOnDelete();
            $table->decimal('budget', 15, 2)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropForeign(['program_id']);
            $table->dropColumn(['section_id', 'program_id', 'budget']);
        });

        Schema::dropIfExists('programs');
    }
};
