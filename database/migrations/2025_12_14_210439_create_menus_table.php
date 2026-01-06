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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['MAKANAN_BERAT', 'MAKANAN_RINGAN', 'MINUMAN_DINGIN', 'MINUMAN_PANAS', 'MINUMAN_SACHET']);
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->boolean('available')->default(true);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
