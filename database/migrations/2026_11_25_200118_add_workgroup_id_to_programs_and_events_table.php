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
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->nullable()->change();
            $table->foreignId('workgroup_id')->nullable()->after('section_id')->constrained('workgroups')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('workgroup_id')->nullable()->after('section_id')->constrained('workgroups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workgroup_id');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workgroup_id');
            $table->unsignedBigInteger('section_id')->nullable(false)->change();
        });
    }
};
