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
        ], ['slug'], ['name', 'description']);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
