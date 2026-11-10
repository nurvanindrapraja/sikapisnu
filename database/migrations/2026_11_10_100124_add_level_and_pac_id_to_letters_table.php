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
            if (! Schema::hasColumn('letters', 'level')) {
                $table->string('level', 30)->default('PC ISNU')->after('letter_type')->index();
            }
            if (! Schema::hasColumn('letters', 'pac_id')) {
                $table->foreignId('pac_id')->nullable()->after('level')->constrained('pac')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropForeign(['pac_id']);
            $table->dropColumn(['level', 'pac_id']);
        });
    }
};
