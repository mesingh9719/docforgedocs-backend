<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentUsageLog extends Model
{
    protected $fillable = [
        'document_id',
        'action',
        'ip_address',
        'user_agent',
        'location',
        'device',
        'platform',
        'browser'
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
