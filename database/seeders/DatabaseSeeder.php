<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@garasi.local'],
            [
                'nik' => '0000000000000001',
                'full_name' => 'Administrator Garasi',
                'phone' => '081200000001',
                'password' => Hash::make('admin12345'),
                'role' => 'admin',
                'verification_status' => 'approved',
            ]
        );
    }
}
