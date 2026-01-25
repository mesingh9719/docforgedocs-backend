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
        'public_token',
        'pdf_path',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    protected $appends = ['pdf_url'];

    public function getPdfUrlAttribute()
    {
        return $this->pdf_path ? asset('storage/' . $this->pdf_path) : null;
    }

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

    /**
     * Alias for creator to fix "undefined relationship [user]" error.
     */
    public function user()
    {
        return $this->creator();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }
    public function shares()
    {
        return $this->hasMany(DocumentShare::class)->orderBy('sent_at', 'desc');
    }
}
