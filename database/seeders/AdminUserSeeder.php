<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@techsynchronic.com'],
            [
                'name' => 'Platform Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_platform_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
