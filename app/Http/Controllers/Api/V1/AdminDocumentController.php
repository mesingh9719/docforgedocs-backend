<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminDocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with(['documentType', 'business', 'creator']) // Eager load type and business
            ->latest()
            ->paginate(10);

        return \App\Http\Resources\Api\V1\DocumentResource::collection($documents);
    }

    public function show(Document $document)
    {
        $document->load(['documentType', 'business', 'creator']);
        return new \App\Http\Resources\Api\V1\DocumentResource($document);
    }

    public function destroy(Document $document)
    {
        $document->delete();
        return response()->json(['message' => 'Document deleted successfully']);
    }
}
