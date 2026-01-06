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
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->enum('payment_method', ['QRIS', 'CASH'])->default('CASH')->after('tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
