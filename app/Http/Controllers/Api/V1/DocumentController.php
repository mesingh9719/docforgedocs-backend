<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Http\Requests\Api\V1\StoreDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Filter by user's business
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        $query = Document::where('business_id', $business->id)
            ->with(['documentType', 'creator']);

        // Handle Trash View
        if ($request->input('view_mode') === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter by document type slug (e.g. 'nda', 'proposal') via relationship
        if ($request->has('type') && $request->input('type') !== 'all') {
            $type = $request->input('type');
            $query->whereHas('documentType', function ($q) use ($type) {
                $q->where('slug', $type);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSort = ['name', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        // Variable Pagination
        $perPage = $request->input('per_page', 10);
        $documents = $query->paginate($perPage);

        return DocumentResource::collection($documents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentRequest $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        // Check for duplicate name
        if (Document::where('business_id', $business->id)->where('name', $request->name)->exists()) {
            $suggestion = $this->getSmartSuggestion($request->name, $business->id);
            return response()->json([
                'message' => 'Document name already exists.',
                'suggested_name' => $suggestion
            ], 422);
        }

        $type = DocumentType::where('slug', $request->type_slug)->firstOrFail();

        $document = Document::create([
            'document_type_id' => $type->id,
            'business_id' => $business->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'slug' => Str::slug($request->name) . '-' . Str::random(5),
            'content' => $request->input('content'), // Casts to JSON automatically if model configured
            'status' => $request->status ?? 'draft',
        ]);

        \App\Services\ActivityLogger::log(
            'document.created',
            "Document created: {$document->name}",
            'info',
            ['document_id' => $document->id],
            $user->id
        );

        return new DocumentResource($document);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Document $document)
    {
        // Authorization check: User must own the business of the document
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new DocumentResource($document);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check for duplicate name (excluding current document)
        if ($request->has('name') && $request->name !== $document->name) {
            if (Document::where('business_id', $business->id)->where('name', $request->name)->where('id', '!=', $document->id)->exists()) {
                $suggestion = $this->getSmartSuggestion($request->name, $business->id, $document->id);
                return response()->json([
                    'message' => 'Document name already exists.',
                    'suggested_name' => $suggestion
                ], 422);
            }
        }

        $document->update([
            'name' => $request->name ?? $document->name,
            'content' => $request->input('content') ?? $document->content,
            'description' => $request->description ?? $document->description,
            'status' => $request->status ?? $document->status,
            'updated_by' => $request->user()->id,
        ]);

        \App\Services\ActivityLogger::log(
            'document.updated',
            "Document updated: {$document->name}",
            'info',
            ['document_id' => $document->id, 'changes' => array_keys($request->only('name', 'status', 'description'))],
            $request->user()->id
        );

        // Create a new version snapshot
        $latestVersion = $document->versions()->max('version_number') ?? 0;
        $document->versions()->create([
            'content' => $document->content,
            'version_number' => $latestVersion + 1,
            'created_by' => $request->user()->id,
        ]);

        return new DocumentResource($document);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($document->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->pdf_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    public function generatePdf(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'html_content' => 'required|string',
            'lock_document' => 'nullable|boolean'
        ]);

        $html = $request->input('html_content');
        $shouldLock = $request->input('lock_document', false);

        // Wraps the document HTML with the full page structure and styles.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        // Delete old PDF if exists
        if ($document->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->pdf_path);
        }

        $filename = "documents/{$business->id}/{$document->id}_" . time() . '.pdf';

        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $pdf->output());

        $updateData = ['pdf_path' => $filename];
        if ($shouldLock) {
            $updateData['status'] = 'LOCKED'; // Assuming 'LOCKED' or similar status exists or just string
        }

        $document->update($updateData);

        \App\Services\ActivityLogger::log(
            'document.generated_pdf',
            "PDF Generated for: {$document->name}" . ($shouldLock ? " (Locked)" : ""),
            'info',
            ['document_id' => $document->id],
            $business->user_id
        );

        return response()->json([
            'message' => 'PDF generated successfully',
            'url' => asset('storage/' . $filename),
            'status' => $document->status
        ]);
    }
    public function getNextInvoiceNumber(Request $request)
    {
        $user = $request->user();
        $business = $user->resolveBusiness();

        if (!$business) {
            return response()->json(['message' => 'User does not have a business.'], 403);
        }

        // Find the invoice document type
        $invoiceType = DocumentType::where('slug', 'invoice')->first();
        if (!$invoiceType) {
            return response()->json(['next_number' => 'INV-0001']);
        }

        // Get the last created invoice for this business
        $lastInvoice = Document::where('business_id', $business->id)
            ->where('document_type_id', $invoiceType->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastInvoice) {
            return response()->json(['next_number' => 'INV-0001']);
        }

        // Try to parse the number from the last invoice content
        // Assuming structure: content -> formData -> invoiceNumber
        $content = $lastInvoice->content;
        $lastNumberStr = $content['formData']['invoiceNumber'] ?? null;

        if (!$lastNumberStr) {
            // Fallback: try to guess from document name if needed, or just reset
            return response()->json(['next_number' => 'INV-0001']);
        }

        // simple increment logic assume format prefix-number or just number
        // Extract numeric part
        if (preg_match('/(\d+)$/', $lastNumberStr, $matches)) {
            $number = intval($matches[1]);
            $nextNumber = $number + 1;
            // Pad with zeros to match length if needed, e.g. 0001 -> 0002
            $length = strlen($matches[1]);
            $nextNumberStr = str_pad($nextNumber, $length, '0', STR_PAD_LEFT);

            // Replace the numeric part in original string
            $newInvoiceNumber = preg_replace('/(\d+)$/', $nextNumberStr, $lastNumberStr);
            return response()->json(['next_number' => $newInvoiceNumber]);
        }

        return response()->json(['next_number' => 'INV-0001']);
    }

    public function send(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string',
            'html_content' => 'nullable|string'
        ]);

        $email = $request->input('email');
        $customMessage = $request->input('message');
        $htmlContent = $request->input('html_content');

        // 1. Generate/Overwrite PDF if HTML provided
        if ($htmlContent) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent)->setPaper('a4', 'portrait');
            $filename = "documents/{$business->id}/{$document->id}_" . time() . '.pdf';

            // Delete old PDF if exists
            if ($document->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($document->pdf_path);
            }

            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $pdf->output());
            $document->update(['pdf_path' => $filename]);
        }

        // 2. Ensure Public Token
        if (!$document->public_token) {
            $document->update(['public_token' => Str::uuid()]);
        }

        // 3. Construct Public Link
        // Frontend URL /view/{token}
        $shareLink = env('FRONTEND_URL') . "/view/" . $document->public_token;

        // 4. Dispatch Job (Pass PDF path for attachment)
        \App\Jobs\SendDocumentEmail::dispatch($email, $document, $shareLink, $customMessage);

        $document->update([
            'sent_at' => now(),
            'status' => 'sent'
        ]);

        // Record Share History
        $document->shares()->create([
            'user_id' => $request->user()->id,
            'recipient_email' => $email,
            'message' => $customMessage,
            'sent_at' => now()
        ]);

        \App\Services\ActivityLogger::log(
            'document.sent',
            "Document sent to {$email}",
            'info',
            ['document_id' => $document->id, 'recipient' => $email],
            $request->user()->id
        );

        return response()->json([
            'message' => 'Email queued for sending.',
            'document' => $document->fresh(),
            'pdf_url' => $document->pdf_path ? asset('storage/' . $document->pdf_path) : null
        ]);
    }

    public function remind(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$document->sent_at) {
            return response()->json(['message' => 'Document has not been sent yet.'], 400);
        }

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string'
        ]);

        $email = $request->input('email');
        $customMessage = "REMINDER: " . ($request->input('message') ?? 'Just following up on this document.');

        $shareLink = $document->pdf_path ? asset('storage/' . $document->pdf_path) : env('FRONTEND_URL') . "/documents/" . $document->documentType->slug . "/" . $document->id;

        \App\Jobs\SendDocumentEmail::dispatch($email, $document, $shareLink, $customMessage);

        $document->update(['last_reminded_at' => now()]);

        // Record Share History
        $document->shares()->create([
            'user_id' => $request->user()->id,
            'recipient_email' => $email,
            'message' => $customMessage,
            'sent_at' => now()
        ]);

        \App\Services\ActivityLogger::log(
            'document.remind',
            "Reminder sent to {$email}",
            'info',
            ['document_id' => $document->id, 'recipient' => $email],
            $request->user()->id
        );

        return response()->json(['message' => 'Reminder queued for sending.', 'document' => $document->fresh()]);
    }

    public function getVersions(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $document->versions()->with('creator:id,name')->orderBy('version_number', 'desc')->get()->map(function ($v) {
                return [
                    'id' => $v->id,
                    'version_number' => $v->version_number,
                    'created_at' => $v->created_at->toIso8601String(),
                    'created_by' => $v->creator ? $v->creator->name : 'Unknown',
                    'content' => $v->content // Include content for preview/restore
                ];
            })
        ]);
    }

    public function restoreVersion(Request $request, Document $document, $versionId)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $version = $document->versions()->findOrFail($versionId);

        // Update document content to match version
        $document->update([
            'content' => $version->content,
            'updated_by' => $request->user()->id,
        ]);

        // Create a new version snapshot for this "Restore" event
        $latestVersion = $document->versions()->max('version_number') ?? 0;
        $document->versions()->create([
            'content' => $document->content,
            'version_number' => $latestVersion + 1,
            'created_by' => $request->user()->id,
        ]);

        \App\Services\ActivityLogger::log(
            'document.restored',
            "Document restored to version {$version->version_number}",
            'info',
            ['document_id' => $document->id, 'version_restored' => $version->version_number],
            $request->user()->id
        );

        return new DocumentResource($document);
    }
    public function getShares(Request $request, Document $document)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business || $document->business_id !== $business->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => $document->shares()->with('sender:id,name')->get()
        ]);
    }
    public function bulkDestroy(Request $request)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:documents,id'
        ]);

        $ids = $request->input('ids');

        // Ensure user owns these documents
        $count = Document::where('business_id', $business->id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json(['message' => "{$count} documents moved to trash."]);
    }

    public function restore(Request $request, $id)
    {
        $business = $request->user()->resolveBusiness();
        if (!$business) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $document = Document::onlyTrashed()
            ->where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $document->restore();

        \App\Services\ActivityLogger::log(
            'document.restored',
            "Document restored: {$document->name}",
            'info',
            ['document_id' => $document->id],
            $request->user()->id
        );

        return response()->json(['message' => 'Document restored successfully.']);
    }
    /**
     * Generate a smart suggestion for a unique name.
     */
    private function getSmartSuggestion($name, $businessId, $excludeId = null)
    {
        $counter = 1;
        $originalName = $name;

        // Strip existing counter if present, e.g., "NDA (1)" -> "NDA"
        if (preg_match('/^(.*) \((\d+)\)$/', $name, $matches)) {
            $originalName = $matches[1];
            $counter = intval($matches[2]) + 1;
        }

        // Generate suggestions until one is unique
        do {
            $suggestion = "{$originalName} ({$counter})";
            $query = Document::where('business_id', $businessId)->where('name', $suggestion);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $exists = $query->exists();
            if ($exists) {
                $counter++;
            }
        } while ($exists);

        return $suggestion;
    }
}
