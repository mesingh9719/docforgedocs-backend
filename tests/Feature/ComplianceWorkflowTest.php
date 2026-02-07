<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\User;
use App\Models\DocumentSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ComplianceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signature_session_requires_consent()
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
            'name' => 'Compliance Doc',
            'slug' => 'comp-doc-123',
            'status' => 'sent',
            'content' => [],
            'pdf_path' => 'dummy.pdf' // Fake path
        ]);

        $signer = $document->signers()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'token' => 'secure_token_compliance',
            'status' => 'sent',
            'order' => 1
        ]);

        // 2. Visit Session (Show)
        $response = $this->getJson(route('api.v1.signatures.show', ['token' => $signer->token]));

        $response->assertStatus(200);

        // Assert current_signer and pdf_url exist (Regression Test)
        $this->assertNotNull($response->json('current_signer'));
        $this->assertNotNull($response->json('pdf_url'));

        // Assert compliance flag indicates consent is required
        $this->assertTrue($response->json('compliance.consent_required'));

        // 3. Agree to Terms
        $agreeResponse = $this->postJson(route('api.v1.signatures.agree', ['token' => $signer->token]));
        $agreeResponse->assertStatus(200);

        // 4. Visit Session Again
        $response2 = $this->getJson(route('api.v1.signatures.show', ['token' => $signer->token]));
        $this->assertFalse($response2->json('compliance.consent_required'));

        // 5. Check Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'AGREED_TO_TERMS',
            'metadata' => json_encode(['signer_id' => $signer->id, 'signer_name' => $signer->name, 'ip' => '127.0.0.1'])
        ]);

        // Check Signer Record
        $signer->refresh();
        $this->assertNotNull($signer->audit_consent_at);
    }

    public function test_signature_session_requires_otp_verification()
    {
        Storage::fake('public');

        // Setup
        $user = User::factory()->create();
        $business = $user->business()->create(['name' => 'Test Business']);
        $type = \App\Models\DocumentType::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'description' => 'General Doc']
        );

        $document = Document::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'document_type_id' => $type->id,
            'name' => 'OTP Doc',
            'slug' => 'otp-doc',
            'status' => 'sent',
            'content' => []
        ]);

        $signer = $document->signers()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'token' => 'otp_token_123',
            'status' => 'sent',
            'is_access_code_required' => true
        ]);

        // 1. Visit (Show) - Should trigger check
        $response = $this->getJson(route('api.v1.signatures.show', ['token' => $signer->token]));
        $response->assertStatus(200);
        $this->assertTrue($response->json('compliance.access_code_required'));
        $this->assertFalse($response->json('compliance.is_verified'));

        // 2. Send OTP
        $this->postJson(route('api.v1.signatures.send_otp', ['token' => $signer->token]))
            ->assertStatus(200);

        $signer->refresh();
        $this->assertNotNull($signer->access_code); // OTP stored (hashed)

        // 3. Verify OTP (Wrong)
        $this->postJson(route('api.v1.signatures.verify_otp', ['token' => $signer->token]), ['otp' => '000000'])
            ->assertStatus(400);

        // 4. Verify OTP (Correct) - Can't know the exact random OTP unless we mock it or peek
        // Since we stored Hash::make($otp), we can't reverse it.
        // For testing, I'll update the signer with a known hash
        $knownOtp = '123456';
        $signer->update(['access_code' => \Illuminate\Support\Facades\Hash::make($knownOtp)]);

        $this->postJson(route('api.v1.signatures.verify_otp', ['token' => $signer->token]), ['otp' => $knownOtp])
            ->assertStatus(200);

        // 5. Visit again - Should be verified
        $response2 = $this->getJson(route('api.v1.signatures.show', ['token' => $signer->token]));
        $this->assertTrue($response2->json('compliance.is_verified'));
    }
}
