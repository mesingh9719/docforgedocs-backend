<?php

namespace App\Models;

use App\Models\Document;
use App\Models\DocumentSigner;
use Illuminate\Database\Eloquent\Model;

class DocumentField extends Model
{
    protected $fillable = [
        'document_id',
        'signer_id',
        'type', // signature, date, text
        'page_number',
        'x_position',
        'y_position',
        'width',
        'height',
        'value', // ← CRITICAL: Must be fillable to save signature data!
        'metadata', // JSON for extra data
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function signer()
    {
        return $this->belongsTo(DocumentSigner::class, 'signer_id');
    }
}
