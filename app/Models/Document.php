<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;
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
        'final_pdf_path',
        'document_hash',
        'is_locked',
        'expires_at',
    ];

    protected $casts = [
        'content' => 'array',
        'is_locked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $appends = ['pdf_url', 'final_pdf_url'];

    public function getPdfUrlAttribute()
    {
        return $this->pdf_path ? asset('storage/' . $this->pdf_path) : null;
    }

    public function getFinalPdfUrlAttribute()
    {
        return $this->final_pdf_path ? asset('storage/' . $this->final_pdf_path) : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function signers()
    {
        return $this->hasMany(DocumentSigner::class)->orderBy('order');
    }

    public function fields()
    {
        return $this->hasMany(DocumentField::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at', 'desc');
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
