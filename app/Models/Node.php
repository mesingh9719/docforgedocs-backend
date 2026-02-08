<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'dms_nodes';

    protected $fillable = [
        'uuid',
        'business_id',
        'parent_id',
        'name',
        'type', // 'folder', 'file'
        'mime_type',
        'size',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // --- Relationships ---

    /**
     * Parent folder relationship.
     */
    public function parent()
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }

    /**
     * Child files and folders.
     */
    public function children()
    {
        return $this->hasMany(Node::class, 'parent_id');
    }

    /**
     * All file versions for this node.
     */
    public function versions()
    {
        return $this->hasMany(FileVersion::class, 'node_id');
    }

    /**
     * The latest file version.
     */
    public function latestVersion()
    {
        return $this->hasOne(FileVersion::class, 'node_id')->latest('version_number');
    }

    /**
     * The business this node belongs to.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The user who created this node.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
