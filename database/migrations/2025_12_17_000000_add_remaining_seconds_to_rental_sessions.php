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
            // Add remaining_seconds column to store exact remaining time when paused
            // This prevents race conditions and ensures accurate time tracking
            $table->integer('remaining_seconds')->nullable()->after('paused_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->dropColumn('remaining_seconds');
        });
    }
};
