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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->string('type', 20); // 'in' (Uang Masuk) atau 'out' (Uang Keluar)
            $table->string('category', 100);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('payment_method', 50)->default('Tunai'); // Tunai, Transfer Bank, QRIS, dll
            $table->string('proof_path')->nullable();
            $table->string('level', 50)->default('PC ISNU'); // PC ISNU / PAC ISNU
            $table->foreignId('pac_id')->nullable()->constrained('pac')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
