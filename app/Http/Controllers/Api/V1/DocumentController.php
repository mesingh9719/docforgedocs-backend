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
            ->with('documentType');

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

        $documents = $query->latest()->paginate(20);

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

        $document->update([
            'name' => $request->name ?? $document->name,
            'content' => $request->input('content') ?? $document->content,
            'description' => $request->description ?? $document->description,
            'status' => $request->status ?? $document->status,
            'updated_by' => $request->user()->id,
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
        ]);

        $html = $request->input('html_content');

        // Wraps the document HTML with the full page structure and styles.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        // Delete old PDF if exists
        if ($document->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->pdf_path);
        }

        $filename = "documents/{$business->id}/{$document->id}_" . time() . '.pdf';

        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $pdf->output());

        $document->update(['pdf_path' => $filename]);

        return response()->json([
            'message' => 'PDF generated successfully',
            'url' => asset('storage/' . $filename)
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

        return response()->json(['message' => 'Reminder queued for sending.', 'document' => $document->fresh()]);
    }
}
