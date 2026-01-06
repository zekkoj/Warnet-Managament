<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            // MAKANAN_BERAT
            ['name' => 'Nasi Goreng Spesial', 'category' => 'MAKANAN_BERAT', 'price' => 25000, 'description' => 'Nasi goreng dengan telur & daging', 'available' => true],
            ['name' => 'Nasi Kuning', 'category' => 'MAKANAN_BERAT', 'price' => 20000, 'description' => 'Nasi kuning gurih', 'available' => true],
            ['name' => 'Mie Goreng Spesial', 'category' => 'MAKANAN_BERAT', 'price' => 22000, 'description' => 'Mie dengan ayam & sayur', 'available' => true],
            
            // MAKANAN_RINGAN
            ['name' => 'Mie Instan Goreng', 'category' => 'MAKANAN_RINGAN', 'price' => 15000, 'description' => 'Mie instan dengan telur', 'available' => true],
            ['name' => 'Roti Bakar', 'category' => 'MAKANAN_RINGAN', 'price' => 12000, 'description' => 'Roti bakar dengan mentega & keju', 'available' => true],
            ['name' => 'Snack Pack', 'category' => 'MAKANAN_RINGAN', 'price' => 10000, 'description' => 'Paket snack campur', 'available' => true],
            
            // MINUMAN_DINGIN
            ['name' => 'Es Jus Jeruk', 'category' => 'MINUMAN_DINGIN', 'price' => 12000, 'description' => 'Jus jeruk segar dengan es', 'available' => true],
            ['name' => 'Es Teh Manis', 'category' => 'MINUMAN_DINGIN', 'price' => 8000, 'description' => 'Teh manis dengan es batu', 'available' => true],
            ['name' => 'Es Cendol', 'category' => 'MINUMAN_DINGIN', 'price' => 15000, 'description' => 'Cendol tradisional', 'available' => true],
            
            // MINUMAN_PANAS
            ['name' => 'Kopi Robusta', 'category' => 'MINUMAN_PANAS', 'price' => 10000, 'description' => 'Kopi robusta panas', 'available' => true],
            ['name' => 'Teh Panas', 'category' => 'MINUMAN_PANAS', 'price' => 7000, 'description' => 'Teh hangat biasa', 'available' => true],
            ['name' => 'Milo Panas', 'category' => 'MINUMAN_PANAS', 'price' => 8000, 'description' => 'Milo coklat panas', 'available' => true],
            
            // MINUMAN_SACHET
            ['name' => 'Sprite 220ml', 'category' => 'MINUMAN_SACHET', 'price' => 6000, 'description' => 'Sprite kaleng 220ml', 'available' => true],
            ['name' => 'Coca Cola 220ml', 'category' => 'MINUMAN_SACHET', 'price' => 6000, 'description' => 'Coca Cola kaleng 220ml', 'available' => true],
            ['name' => 'Aqua 600ml', 'category' => 'MINUMAN_SACHET', 'price' => 5000, 'description' => 'Air mineral 600ml', 'available' => true],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
