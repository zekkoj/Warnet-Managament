<?php

namespace Database\Seeders;

use App\Models\PC;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 30 REGULER PCs (Lantai 1 & 2)
        for ($i = 1; $i <= 30; $i++) {
            $floor = $i <= 15 ? 'Lantai 1' : 'Lantai 2';
            $position = 'Row ' . chr(65 + (($i - 1) % 5)) . '-' . (floor(($i - 1) / 5) + 1);
            
            PC::create([
                'pc_code' => 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'location' => $floor,
                'position' => $position,
                'type' => 'REGULER',
                'status' => 'IDLE',
                'specifications' => 'Intel i5, 8GB RAM, 240GB SSD, Windows 10',
            ]);
        }

        // Create 15 VIP PCs (Lantai 2 booth)
        for ($i = 1; $i <= 15; $i++) {
            PC::create([
                'pc_code' => 'VIP-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'location' => 'Lantai 2',
                'position' => 'Booth ' . $i,
                'type' => 'VIP',
                'status' => 'IDLE',
                'specifications' => 'Intel i9, 16GB RAM, 512GB SSD, Windows 10 Pro, RTX 3060',
            ]);
        }
    }
}
