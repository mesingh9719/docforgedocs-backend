<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildUser extends Model
{
    protected $fillable = [
        'parent_id',
        'user_id',
        'business_id',
        'role',
        'status',
        'permissions',
        'invitation_token',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function child()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    protected $hidden = [
        'invitation_token'
    ];
}
