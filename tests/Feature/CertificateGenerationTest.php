<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\User;
use App\Models\DocumentSigner;
use App\Models\DocumentAuditLog; // Assuming model name
use App\Services\CertificateGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class CertificateGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_generation()
    {
        Storage::fake('public');

        // 1. Setup Data
        $user = User::factory()->create();
        $business = $user->business()->create(['name' => 'Test Business']);

        $type = \App\Models\DocumentType::create([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'General Doc'
        ]);

        $document = Document::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'document_type_id' => $type->id,
            'name' => 'Test Agreement',
            'slug' => 'test-agreement-123',
            'status' => 'completed',
            'content' => [] // Dummy
        ]);

        // Add Signers
        $signer1 = $document->signers()->create([
            'name' => 'Alice Signer',
            'email' => 'alice@example.com',
            'token' => 'abc123token',
            'status' => 'signed'
        ]);

        $signer2 = $document->signers()->create([
            'name' => 'Bob Signer',
            'email' => 'bob@example.com',
            'token' => 'xyz789token',
            'status' => 'signed'
        ]);

        // Add Audit Logs using the Service (or direct create)
        // Check AuditService for model name. It was `AuditLog` in the code I viewed.
        \App\Models\AuditLog::create([
            'document_id' => $document->id,
            'action' => 'CREATED',
            'user_id' => $user->id
        ]);

        \App\Models\AuditLog::create([
            'document_id' => $document->id,
            'action' => 'SIGNED',
            'metadata' => ['signer_name' => 'Alice Signer']
        ]);

        // 2. Run Service
        $service = new CertificateGeneratorService();
        $hash = 'dummy_sha256_hash_1234567890abcdef';

        $path = $service->generateCertificate($document, $hash);

        // 3. Assertions
        Storage::disk('public')->assertExists($path);

        // Optional: Check if it's a valid PDF by trying to load it with Fpdi
        $absPath = Storage::disk('public')->path($path);
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($absPath);

        $this->assertGreaterThan(0, $pageCount, 'Certificate PDF should have pages');
    }
}
