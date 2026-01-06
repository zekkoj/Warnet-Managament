<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Zzzzzz',
            'username' => 'Zzzzzz',
            'email' => 'Zzzzzz@warnet.local',
            'password' => Hash::make('66666666'),
        ]);
    }
}
