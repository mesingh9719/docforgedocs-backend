<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\User;
use App\Models\DocumentSigner;
use App\Models\DocumentField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class SignatureWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signature_workflow_completes_document_and_generates_certificate()
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

        // Create a dummy PDF to serve as the original document
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Original Document Content', 0, 1);
        $originalPdfPath = 'documents/' . $business->id . '/test_doc.pdf';
        $pdfContent = $pdf->Output('S');
        Storage::disk('public')->put($originalPdfPath, $pdfContent);

        $document = Document::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'document_type_id' => $type->id,
            'name' => 'Test Contract',
            'slug' => 'test-contract-789',
            'status' => 'sent', // It must be sent to be signed
            'content' => [],
            'pdf_path' => $originalPdfPath
        ]);

        $signer = $document->signers()->create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'token' => 'secure_token_123',
            'status' => 'sent',
            'order' => 1
        ]);

        $field = $document->fields()->create([
            'signer_id' => $signer->id,
            'type' => 'signature',
            'page_number' => 1,
            'x_position' => 10,
            'y_position' => 10,
            'width' => 100,
            'height' => 50
        ]);

        // 2. Perform Request
        $response = $this->postJson(route('api.v1.signatures.sign', ['token' => $signer->token]), [
            'fields' => [
                [
                    'id' => $field->id,
                    'value' => 'Signed by John Doe' // Text signature
                ]
            ]
        ]);

        // 3. Assertions
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Signature submitted successfully']);

        $document->refresh();
        $this->assertEquals('completed', $document->status);
        $this->assertNotNull($document->final_pdf_path);

        Storage::disk('public')->assertExists($document->final_pdf_path);

        // Assert Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'COMPLETED'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'document_id' => $document->id,
            'action' => 'SIGNED'
        ]);
    }
}
