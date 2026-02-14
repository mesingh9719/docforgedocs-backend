<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        \App\Models\DocumentType::upsert([
            ['name' => 'Non-Disclosure Agreement', 'slug' => 'nda', 'description' => 'Standard NDA template'],
            ['name' => 'Business Proposal', 'slug' => 'proposal', 'description' => 'Project proposal template'],
            ['name' => 'Invoice', 'slug' => 'invoice', 'description' => 'Service invoice template'],
            ['name' => 'Employment Offer Letter', 'slug' => 'offer-letter', 'description' => 'Standard job offer letter template'],
            ['name' => 'Consulting Agreement', 'slug' => 'consulting-agreement', 'description' => 'Contract for consulting services'],
        ], ['slug'], ['name', 'description']);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]
        );

        $this->call([
            SystemRoleSeeder::class,
            AdminUserSeeder::class,
            MasterDataSeeder::class,
        ]);
    }
}
