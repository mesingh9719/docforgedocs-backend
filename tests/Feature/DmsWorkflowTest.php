<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\Node;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_folder()
    {
        $user = User::factory()->create();
        Business::create(['user_id' => $user->id, 'name' => 'Test Biz']);

        $response = $this->actingAs($user)->postJson('/api/v1/drive/nodes', [
            'type' => 'folder',
            'name' => 'My Docs',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('node.name', 'My Docs')
            ->assertJsonPath('node.type', 'folder');

        $this->assertDatabaseHas('dms_nodes', [
            'name' => 'My Docs',
            'type' => 'folder',
            'business_id' => $user->fresh()->business->id,
        ]);
    }

    public function test_user_can_upload_file()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Business::create(['user_id' => $user->id, 'name' => 'Test Biz']);

        $file = UploadedFile::fake()->create('contract.pdf', 100);

        $response = $this->actingAs($user)->postJson('/api/v1/drive/nodes', [
            'type' => 'file',
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('node.name', 'contract.pdf')
            ->assertJsonPath('node.mime_type', 'application/pdf');

        $node = Node::where('name', 'contract.pdf')->first();
        $this->assertNotNull($node);
        $this->assertCount(1, $node->versions);

        // Check storage
        // Note: The service stores with a specific path structure, we might need to check that
        // But for now, just checking DB side is good enough for "foundation" verification
    }

    public function test_user_can_list_nodes()
    {
        $user = User::factory()->create();
        Business::create(['user_id' => $user->id, 'name' => 'Test Biz']);

        // Create a folder and a file
        $folder = Node::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $user->business->id,
            'name' => 'Folder A',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        $file = Node::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $user->business->id,
            'name' => 'File B',
            'type' => 'file',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/drive/nodes');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'nodes')
            ->assertJsonFragment(['name' => 'Folder A'])
            ->assertJsonFragment(['name' => 'File B']);
    }

    public function test_nested_structure()
    {
        $user = User::factory()->create();
        Business::create(['user_id' => $user->id, 'name' => 'Test Biz']);

        // Reload user to get business relationship
        $user->refresh();

        // Root Folder
        $rootResponse = $this->actingAs($user)->postJson('/api/v1/drive/nodes', [
            'type' => 'folder',
            'name' => 'Project X',
        ]);
        $rootId = $rootResponse->json('node.id');

        // Child File
        $file = UploadedFile::fake()->create('specs.pdf', 50);
        $this->actingAs($user)->postJson('/api/v1/drive/nodes', [
            'type' => 'file',
            'file' => $file,
            'parent_id' => $rootId,
        ]);

        // List Root - should show folder
        $this->actingAs($user)->getJson('/api/v1/drive/nodes')
            ->assertJsonCount(1, 'nodes')
            ->assertJsonPath('nodes.0.name', 'Project X');

        // List Inside Folder - should show file
        $this->actingAs($user)->getJson('/api/v1/drive/nodes?parent_id=' . $rootId)
            ->assertJsonCount(1, 'nodes')
            ->assertJsonPath('nodes.0.name', 'specs.pdf');
    }
}
