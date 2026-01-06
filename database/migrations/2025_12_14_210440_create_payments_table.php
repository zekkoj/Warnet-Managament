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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('rental_session_id')->nullable()->constrained('rental_sessions')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['QRIS', 'CASH']);
            $table->enum('status', ['PENDING', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->string('qris_code')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
