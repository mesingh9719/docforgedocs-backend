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
        'access_code',
        'is_access_code_required',
        'verified_at',
        'audit_consent_ip',
        'audit_consent_at'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
