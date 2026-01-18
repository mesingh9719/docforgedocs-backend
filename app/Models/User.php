<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function childUsers()
    {
        return $this->hasMany(ChildUser::class, 'parent_id');
    }

    public function parentUser()
    {
        return $this->hasOne(ChildUser::class, 'user_id');
    }

    public function children()
    {
        return $this->belongsToMany(
            User::class,
            'child_users',
            'parent_id',
            'user_id'
        )->withTimestamps();
    }

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    /**
     * Resolve the business for this user (Owner or Member).
     */
    public function resolveBusiness()
    {
        // 1. Check if user owns a business
        if ($this->business) {
            return $this->business;
        }

        // 2. Check if user is a child user and has a business associated via pivot
        if ($this->parentUser && $this->parentUser->business) {
            return $this->parentUser->business;
        }

        return null;
    }

    /**
     * Resolve the role of the user.
     */
    public function resolveRole()
    {
        // 1. If owner
        if ($this->business) {
            return 'admin'; // Owner is effectively super admin
        }

        // 2. If child user
        if ($this->parentUser) {
            return $this->parentUser->role;
        }

        return 'guest';
    }

    /**
     * Resolve the specific permissions of the user.
     */
    public function resolvePermissions()
    {
        // 1. If owner
        if ($this->business) {
            return ['*']; // Owner has all permissions
        }

        // 2. If child user
        if ($this->parentUser) {
            return $this->parentUser->permissions ?? [];
        }

        return [];
    }
}
