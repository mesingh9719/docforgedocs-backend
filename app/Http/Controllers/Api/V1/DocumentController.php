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

        $documents = Document::where('business_id', $business->id)
            ->with('documentType')
            ->latest()
            ->paginate(20);

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

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}
