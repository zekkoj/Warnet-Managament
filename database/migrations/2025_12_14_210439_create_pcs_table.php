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
        Schema::create('pcs', function (Blueprint $table) {
            $table->id();
            $table->string('pc_code')->unique(); // "PC-01", "VIP-15"
            $table->string('location'); // "Lantai 1", "Lantai 2"
            $table->string('position'); // "Row A-1", "Booth 5"
            $table->enum('type', ['REGULER', 'VIP']);
            $table->text('specifications')->nullable(); // PC specs like CPU, RAM, etc
            $table->enum('status', ['ONLINE', 'OFFLINE', 'IDLE', 'IN_USE', 'MAINTENANCE'])->default('OFFLINE');
            $table->unsignedBigInteger('current_session_id')->nullable(); // Foreign key akan ditambahkan di migration terpisah
            $table->unsignedTinyInteger('cpu_usage')->default(0);
            $table->unsignedTinyInteger('ram_usage')->default(0);
            $table->unsignedTinyInteger('disk_usage')->default(0);
            $table->json('current_process')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcs');
    }
};
