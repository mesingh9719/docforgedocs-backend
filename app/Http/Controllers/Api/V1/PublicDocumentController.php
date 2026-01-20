<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\DocumentResource;

class PublicDocumentController extends Controller
{
    public function show($token)
    {
        $document = Document::with('documentType')->where('public_token', $token)->firstOrFail();

        // Return full document resource but maybe we could limit fields?
        // Using existing resource is fine for now as it exposes content and status.
        return new DocumentResource($document);
    }
}
