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
        // PCs table indexes
        try {
            Schema::table('pcs', function (Blueprint $table) {
                $table->index('status');
                $table->index('type');
            });
        } catch (\Exception $e) {}

        // Rental Sessions table indexes
        try {
            Schema::table('rental_sessions', function (Blueprint $table) {
                $table->index('pc_id');
                $table->index('status');
                $table->index('start_time');
                $table->index(['status', 'start_time']);
            });
        } catch (\Exception $e) {}

        // Orders table indexes
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('status');
                $table->index('payment_status');
                $table->index('created_at');
                $table->index(['status', 'created_at']);
            });
        } catch (\Exception $e) {}

        // Order Items table indexes
        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('order_id');
                $table->index('menu_id');
            });
        } catch (\Exception $e) {}

        // Payments table indexes
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('order_id');
                $table->index('rental_session_id');
                $table->index('status');
                $table->index('method');
                $table->index('transaction_ref');
            });
        } catch (\Exception $e) {}

        // Menu table indexes
        try {
            Schema::table('menus', function (Blueprint $table) {
                $table->index('category');
                $table->index('is_available');
                $table->index(['category', 'is_available']);
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('pcs', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['type']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('rental_sessions', function (Blueprint $table) {
                $table->dropIndex(['pc_id']);
                $table->dropIndex(['status']);
                $table->dropIndex(['start_time']);
                $table->dropIndex(['status', 'start_time']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['payment_status']);
                $table->dropIndex(['created_at']);
                $table->dropIndex(['status', 'created_at']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['menu_id']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['rental_session_id']);
                $table->dropIndex(['status']);
                $table->dropIndex(['method']);
                $table->dropIndex(['transaction_ref']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('menus', function (Blueprint $table) {
                $table->dropIndex(['category']);
                $table->dropIndex(['is_available']);
                $table->dropIndex(['category', 'is_available']);
            });
        } catch (\Exception $e) {}
    }
};
