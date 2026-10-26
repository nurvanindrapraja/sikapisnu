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
        Schema::create('workgroups', function (Blueprint $table) {
            $table->id();
            $table->enum('level', ['PC ISNU', 'PAC ISNU'])->default('PC ISNU');
            $table->foreignId('pac_id')->nullable()->constrained('pac')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workgroup_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workgroup_id')->constrained('workgroups')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('role')->default('Anggota');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workgroup_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workgroup_members');
        Schema::dropIfExists('workgroups');
    }
};
