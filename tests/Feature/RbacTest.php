<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase; // Needed for in-memory DB
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        // Seed system roles as they are expected to exist
        $this->seed(\Database\Seeders\SystemRoleSeeder::class);
    }
    public function test_system_roles_exist()
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin', 'is_system' => true]);
        $this->assertDatabaseHas('roles', ['name' => 'editor', 'is_system' => true]);
    }

    public function test_can_create_custom_role()
    {
        // 1. Create a User who owns a Business
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Test Business ' . $this->faker->company,
            'email' => $this->faker->email,
        ]);

        // Mock authentication
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        // 2. Create a Role
        $roleName = 'Custom Role ' . time();
        $response = $this->postJson('/api/v1/roles', [
            'name' => $roleName,
            'label' => 'Custom Role Label',
            'description' => 'A custom role description',
            'permissions' => []
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', [
            'name' => \Illuminate\Support\Str::slug($roleName),
            'business_id' => $business->id
        ]);

        $roleId = $response->json('id');

        // 3. Update the Role
        $updateResponse = $this->putJson("/api/v1/roles/{$roleId}", [
            'label' => 'Updated Label',
            'description' => 'Updated Description',
            'permissions' => []
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('roles', ['id' => $roleId, 'label' => 'Updated Label']);

        // 4. Delete the Role
        $deleteResponse = $this->deleteJson("/api/v1/roles/{$roleId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    public function test_cannot_delete_system_role()
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Test Business ' . $this->faker->company,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $systemRole = Role::where('is_system', true)->first();

        $response = $this->deleteJson("/api/v1/roles/{$systemRole->id}");

        $response->assertStatus(403);
    }
}
