<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\DocumentSigner;
use App\Models\DocumentField;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Services\Msg91Service;
use App\Models\DocumentType;
use App\Services\AuditService;
use App\Services\PdfFlattenService;
use Illuminate\Support\Facades\Hash;

class SignatureController extends Controller
{
    /**
     * List all signature documents for the business.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        $documents = Document::where('business_id', $business->id)
            ->whereHas('signers') // Only documents with signers (or drafted for signature)
            ->with(['signers', 'creator'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return DocumentResource::collection($documents);
        // Note: DocumentResource needs to include 'signers' which we updated earlier.
    }

    /**
     * View a signed/completed document with audit trail.
     */
    public function viewSigned(Request $request, $documentId)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $document = Document::where('id', $documentId)
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->with(['fields', 'signers', 'creator', 'auditLogs.user'])
            ->firstOrFail();

        return response()->json([
            'document' => new DocumentResource($document),
            'audit_logs' => $document->auditLogs->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user ? $log->user->name : 'Public Signer',
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
            ]),
        ]);
    }

    /**
     * Download/stream the PDF file for a signed document.
     */
    public function downloadPdf(Request $request, $documentId)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        $document = Document::where('id', $documentId)
            ->where('business_id', $business->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // Try final_pdf_path first, then pdf_path
        $pdfPath = $document->final_pdf_path ?? $document->pdf_path;

        if (!$pdfPath || !\Storage::disk('public')->exists($pdfPath)) {
            return response()->json(['message' => 'PDF file not found'], 404);
        }

        $fullPath = storage_path('app/public/' . $pdfPath);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->name . '.pdf"'
        ]);
    }

    /**
     * Upload a document specifically for signature workflow.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'name' => 'nullable|string',
        ]);

        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        $file = $request->file('file');
        $name = $request->input('name') ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        DB::beginTransaction();
        try {
            // Find or create 'general' document type
            $type = \App\Models\DocumentType::where('slug', 'general')->first();
            if (!$type) {
                // If 'general' type doesn't exist, create it dynamically to avoid NULL error
                $type = \App\Models\DocumentType::firstOrCreate(
                    ['slug' => 'general'],
                    ['name' => 'General', 'description' => 'General document type']
                );
            }

            // Create Document Record
// Note: We bypass strict content validation here as this is a PDF upload.
            $document = Document::create([
                'document_type_id' => $type ? $type->id : null,
                'business_id' => $business->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::random(5),
                'content' => [], // Empty content for PDF uploads
                'status' => 'draft',
            ]);

            // Store File
            $filename = "documents/{$business->id}/{$document->id}_" . time() . '.pdf';
            Storage::disk('public')->putFileAs(dirname($filename), $file, basename($filename));

            $document->update(['pdf_path' => $filename]);

            DB::commit();

            return response()->json([
                'message' => 'Document uploaded successfully',
                'data' => new DocumentResource($document)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to upload document', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a document for signature.
     */
    public function send(Request $request, Msg91Service $msg91Service)
    {
        $request->validate([
            'document_id' => 'required|exists:documents,id',
            'signers' => 'required|array|min:1',
            'signers.*.name' => 'required|string',
            'signers.*.email' => 'required|email',
            'fields' => 'required|array',
        ]);

        $document = Document::findOrFail($request->document_id);

        DB::beginTransaction();
        try {
            // 1. Process Signers
            $signerMap = []; // temporary ID -> DB Model

            // First, remove existing signers/fields to handle updates/re-sends cleanly?
// Or just append? Requirement is separate flow. Let's assume clean slate for "Sending" phase.
// Actually, if we re-send, we might want to keep history. But simplified:
            $document->signers()->delete();
            $document->fields()->delete();

            foreach ($request->signers as $signerData) {
                // Generate secure token
                $token = Str::random(64);

                $signer = $document->signers()->create([
                    'name' => $signerData['name'],
                    'email' => $signerData['email'],
                    'token' => $token,
                    'status' => 'sent',
                    'order' => $signerData['order'] ?? 1,
                ]);

                // Map the frontend "temp id" (if any) to the database signer
// We assume the frontend passes some identifier if needed, OR we match by email/order.
// However, the `fields` need to know which signer they belong to.
// The frontend should pass `signer_email` or `signer_temp_id` in fields.
// Let's assume frontend manages fields by matching `signer_email` or `signer_id` from the list.
// Reviewing frontend logic: The fields have a `signerId` or similar.
// Let's rely on matching Order or Email.

                $signerMap[$signerData['id']] = $signer; // Assuming request sends `id` for temp mapping
            }

            // 2. Process Fields
            foreach ($request->fields as $fieldData) {
                // Find the signer logic
// If signer_id is a temp ID from frontend
                $signerId = null;
                if (isset($fieldData['signerId']) && isset($signerMap[$fieldData['signerId']])) {
                    $signerId = $signerMap[$fieldData['signerId']]->id;
                }

                $document->fields()->create([
                    'signer_id' => $signerId,
                    'type' => $fieldData['type'],
                    'page_number' => $fieldData['pageNumber'],
                    'x_position' => $fieldData['x'],
                    'y_position' => $fieldData['y'],
                    'width' => $fieldData['width'] ?? null,
                    'height' => $fieldData['height'] ?? null,
                    'metadata' => $fieldData['metadata'] ?? [],
                ]);
            }

            // 3. Dispatch Emails via MSG91
            $templateId = env('DOCUMENT_SHARED_FOR__SIGNATURE_TEMPLATE_ID', 'signature_request_for_docforgedocs');

            foreach ($signerMap as $signer) {
                $signLink = config('app.frontend_url') . '/sign/' . $signer->token;

                // Prepare variables for MSG91 template
// Assuming the template uses variables like ##name##, ##sender##, ##document##, ##link##
// The Msg91Service expects ['name' => 'Value', 'link' => 'Value'] etc.
                $variables = [
                    'recipient_name' => $signer->name,
                    'sender_name' => auth()->user()->name ?? 'DocForgeDocs User',
                    'document_name' => $document->name,
                    'sign_link' => $signLink,
                    'year' => date('Y'),
                ];

                $msg91Service->sendEmail(
                    $signer->email,
                    $templateId,
                    $variables
                );
            }

            DB::commit();

            return response()->json(['message' => 'Document sent successfully', 'signers' => array_values($signerMap)]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to send document', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get signing session by token.
     */
    public function show($token)
    {
        $signer = DocumentSigner::where('token', $token)->firstOrFail();
        $document = $signer->document;

        // Load fields.
// Logic: Show ALL fields so they see where others sign? Or only theirs?
// Requirement: "dheyan rahe jisko mail gayi hai usse sirf apne hee signature sign krne ki options dikhai de"
// This implies they can SEE others (maybe), but only SIGN theirs.
// Let's load all fields, but frontend handles the "readonly" logic based on signer_id.
        $document->load(['fields', 'signers']);

        // Audit Log: Viewed (user_id is null because signer is not an authenticated user)
        app(AuditService::class)->log($document, 'VIEWED', null, [
            'signer_id' => $signer->id,
            'signer_email' => $signer->email,
            'signer_name' => $signer->name,
            'token' => $token
        ]);

        // Mark as viewed if first time
        if ($signer->status === 'sent') {
            $signer->update(['status' => 'viewed']);
        }

        return response()->json([
            'document' => new DocumentResource($document),
            'current_signer' => $signer,
            'pdf_url' => route('api.v1.signatures.preview', ['token' => $token]),
            'is_locked' => $document->is_locked // Expose lock status
        ]);
    }

    /**
     * Preview the document PDF for the signer.
     */
    public function preview($token)
    {
        $signer = DocumentSigner::where('token', $token)->firstOrFail();
        $document = $signer->document;

        if (!$document->pdf_path || !Storage::disk('public')->exists($document->pdf_path)) {
            abort(404, 'PDF not found');
        }

        return response()->file(Storage::disk('public')->path($document->pdf_path));
    }

    /**
     * Download the final signed PDF.
     */
    public function downloadSigned($token)
    {
        $signer = DocumentSigner::where('token', $token)->firstOrFail();
        $document = $signer->document;

        if ($document->status !== 'completed' || !$document->final_pdf_path) {
            abort(404, 'Signed PDF not available yet.');
        }

        if (!Storage::disk('public')->exists($document->final_pdf_path)) {
            abort(404, 'File not found');
        }

        return response()->download(Storage::disk('public')->path($document->final_pdf_path), $document->name . '_signed.pdf');
    }

    /**
     * Submit a signature.
     */
    public function sign(Request $request, $token, AuditService $auditService, PdfFlattenService $flattenService)
    {
        $signer = DocumentSigner::where('token', $token)->firstOrFail();
        $document = $signer->document;

        // Check Lock
        if ($document->is_locked || $document->status === 'completed') {
            return response()->json(['message' => 'Document is completed and locked.'], 403);
        }

        $request->validate([
            'fields' => 'required|array',
            // 'fields.*.id' => 'required|exists:document_fields,id'
            // 'fields.*.value' => 'required'
        ]);

        \Log::info("=== SIGNATURE SUBMISSION RECEIVED ===", [
            'token' => $token,
            'signer_id' => $signer->id,
            'signer_email' => $signer->email,
            'document_id' => $document->id,
            'document_status' => $document->status,
            'is_locked' => $document->is_locked,
            'request_fields' => $request->fields,
            'fields_count' => count($request->fields ?? [])
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->fields as $fieldUpdate) {
                $field = DocumentField::where('id', $fieldUpdate['id'])
                    ->where('signer_id', $signer->id) // Security check: Must belong to signer
                    ->first();

                if ($field) {
                    \Log::info("Updating field", ['field_id' => $fieldUpdate['id'], 'value_preview' => substr($fieldUpdate['value'] ?? '', 0, 50)]);

                    $field->update([
                        'value' => $fieldUpdate['value'],
                        // Store signature image/data in metadata or value?
                        // Let's assume value holds the signature data (base64 or text)
                    ]);

                    \Log::info("Field updated", ['field_id' => $field->id, 'new_value' => substr($field->value ?? '', 0, 50)]);
                }
            }

            $signer->update(['status' => 'signed']);

            // Audit (user_id is null because signer is not an authenticated user)
            $auditService->log($document, 'SIGNED', null, [
                'signer_id' => $signer->id,
                'signer_name' => $signer->name,
                'signer_email' => $signer->email
            ]);

            // Check if all signers have signed -> Mark document complete?
            if ($signer->document->signers()->where('status', '!=', 'signed')->count() === 0) {
                // All signed - Completion Flow
                $document->update(['status' => 'completed']);

                // 1. Flatten PDF
                try {
                    $finalPath = $flattenService->flatten($document);

                    // 2. Hash
                    $hash = hash_file('sha256', Storage::disk('public')->path($finalPath));

                    $document->update([
                        'final_pdf_path' => $finalPath,
                        'document_hash' => $hash,
                        'is_locked' => true,
                        'expires_at' => null // Clear expiry if any
                    ]);

                    $auditService->log($document, 'COMPLETED', null, ['hash' => $hash]);

                } catch (\Exception $e) {
                    \Log::error("Flattening failed: " . $e->getMessage());
                    // Don't fail the request, but log it. Logic could be improved to retry.
                }

                // Trigger "Completed" email to owner? (TODO)
            }

            DB::commit();
            return response()->json(['message' => 'Signature submitted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit signature', 'error' => $e->getMessage()], 500);
        }
    }
}