<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\PdfFlattenService;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FlattenSignedDocument extends Command
{
    protected $signature = 'document:flatten {documentId}';

    protected $description = 'Manually flatten a signed/completed document to embed signatures';

    public function handle(PdfFlattenService $flattenService, AuditService $auditService)
    {
        $documentId = $this->argument('documentId');

        $this->info("Fetching document {$documentId}...");
        $document = Document::with('fields')->find($documentId);

        if (!$document) {
            $this->error("✗ Document {$documentId} not found");
            return 1;
        }

        $this->info("Document: {$document->name}");
        $this->info("Status: {$document->status}");
        $this->info("PDF Path: " . ($document->pdf_path ?? 'NULL'));
        $this->info("Final PDF Path: " . ($document->final_pdf_path ?? 'NULL'));

        if ($document->status !== 'completed') {
            $this->error("✗ Document must have status 'completed' to flatten");
            $this->info("Current status: {$document->status}");
            return 1;
        }

        if (!$document->pdf_path) {
            $this->error("✗ Document has no original PDF (pdf_path is null)");
            return 1;
        }

        $fieldsWithValues = $document->fields()->whereNotNull('value')->count();
        if ($fieldsWithValues === 0) {
            $this->warn("⚠ Document has no fields with values - will create copy of original PDF");
        } else {
            $this->info("Found {$fieldsWithValues} fields with signature data");
        }

        try {
            $this->info("\n🔄 Starting PDF flattening process...");

            $finalPath = $flattenService->flatten($document);

            $this->info("✓ Flattened PDF created at: {$finalPath}");

            // Generate hash
            $fullPath = Storage::disk('public')->path($finalPath);
            $hash = hash_file('sha256', $fullPath);

            $this->info("✓ SHA-256 Hash: {$hash}");

            // Update database
            $document->update([
                'final_pdf_path' => $finalPath,
                'document_hash' => $hash,
                'is_locked' => true,
            ]);

            $this->info("✓ Database updated");

            // Audit log
            $auditService->log($document, 'MANUALLY_FLATTENED', null, [
                'hash' => $hash,
                'flattened_by' => 'console_command',
                'command' => $this->signature
            ]);

            $this->info("✓ Audit log created");

            $this->newLine();
            $this->info("✅ SUCCESS! Document flattened successfully.");
            $this->info("View at: /signatures/{$documentId}/view-signed");

            return 0;

        } catch (\Exception $e) {
            $this->error("\n✗ Flattening failed!");
            $this->error("Error: " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());

            return 1;
        }
    }
}
