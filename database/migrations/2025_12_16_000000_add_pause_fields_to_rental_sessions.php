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
            $table->timestamp('paused_at')->nullable()->after('status');
            $table->integer('paused_duration')->default(0)->after('paused_at'); // dalam menit
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_duration']);
        });
    }
};
