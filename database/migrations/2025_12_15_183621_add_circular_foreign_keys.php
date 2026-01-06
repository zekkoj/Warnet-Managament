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
        // Add foreign key dari rental_sessions ke payments
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->onDelete('set null');
        });
        
        // Add foreign key dari orders ke payments
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });
    }
};
