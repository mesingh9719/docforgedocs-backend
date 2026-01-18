<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'document_type_id',
        'business_id',
        'created_by',
        'updated_by',
        'name',
        'description',
        'slug',
        'content',
        'status',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
