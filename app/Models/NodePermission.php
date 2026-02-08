<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodePermission extends Model
{
    protected $table = 'dms_node_permissions';

    protected $fillable = [
        'node_id',
        'accessor_id',
        'accessor_type',
        'permission_level', // 'viewer', 'editor', 'manager'
    ];

    // --- Relationships ---

    /**
     * The node this permission applies to.
     */
    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    /**
     * The entity (User or Team) that has this permission.
     */
    public function accessor()
    {
        return $this->morphTo();
    }
}
