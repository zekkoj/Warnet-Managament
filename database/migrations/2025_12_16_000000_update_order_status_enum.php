<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We use raw SQL because Doctrine DBAL has issues with ENUMs in some versions/configurations
        // and we want to be explicit about the new column definition.
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_status ENUM('RECEIVED', 'PREPARING', 'READY', 'DELIVERED', 'COMPLETED') DEFAULT 'RECEIVED'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to the previous state. 
        // WARNING: This might fail if there are records with 'COMPLETED' status.
        // In a real production environment, we might want to handle that case (e.g., update them to 'DELIVERED' or 'READY').
        // For this fix, we'll just revert the definition.
        DB::statement("ALTER TABLE orders MODIFY COLUMN order_status ENUM('RECEIVED', 'PREPARING', 'READY', 'DELIVERED') DEFAULT 'RECEIVED'");
    }
};
