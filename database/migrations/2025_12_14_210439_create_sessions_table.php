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
        Schema::create('rental_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_id')->constrained('pcs')->onDelete('cascade');
            $table->timestamp('start_time');
            $table->integer('duration')->nullable(); // dalam menit
            $table->timestamp('end_time')->nullable();
            $table->string('user_name')->nullable();
            $table->enum('tier', ['REGULER', 'VIP']);
            $table->enum('status', ['ACTIVE', 'COMPLETED', 'PAUSED'])->default('ACTIVE');
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->boolean('paid')->default(false);
            $table->unsignedBigInteger('payment_id')->nullable(); // Foreign key akan ditambahkan kemudian
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_sessions');
    }
};
