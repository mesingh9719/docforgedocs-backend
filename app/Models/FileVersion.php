<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    protected $table = 'dms_file_versions';

    protected $fillable = [
        'node_id',
        'storage_path',
        'version_number',
        'checksum',
        'size',
        'created_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'version_number' => 'integer',
    ];

    // --- Relationships ---

    /**
     * The node this version belongs to.
     */
    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    /**
     * The user who uploaded this version.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
