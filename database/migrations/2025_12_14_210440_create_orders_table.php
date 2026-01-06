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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('table_id'); // "PC-1", "PC-15", atau identifier lain
            $table->foreignId('rental_session_id')->nullable()->constrained('rental_sessions')->onDelete('cascade');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('payment_method', ['QRIS', 'CASH']);
            $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED'])->default('PENDING');
            $table->enum('order_status', ['RECEIVED', 'PREPARING', 'READY', 'DELIVERED'])->default('RECEIVED');
            $table->unsignedBigInteger('payment_id')->nullable(); // Foreign key akan ditambahkan kemudian
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
