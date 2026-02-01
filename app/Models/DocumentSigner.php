<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Document;

class DocumentSigner extends Model
{
    protected $fillable = [
        'document_id',
        'name',
        'email',
        'token',
        'status', // sent, viewed, signed
        'order',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
