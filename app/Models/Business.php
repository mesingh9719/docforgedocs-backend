<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'industry',
        'size',
        'description',
        'logo',
        'favicon',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
        'email',
        'website',
        'industry',
        'status',
        'invoice_prefix',
        'invoice_terms',
        'tax_label',
        'tax_percentage',
        'currency_symbol',
        'currency_code',
        'currency_country',
        'social_links',
        'bank_details',
        'default_invoice_notes',
    ];

    protected $casts = [
        'social_links' => 'array',
        'bank_details' => 'array',
    ];

    public function childUsers()
    {
        return $this->hasMany(ChildUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'child_users',
            'business_id',
            'user_id'
        );
    }

    public function owners()
    {
        return $this->belongsToMany(
            User::class,
            'child_users',
            'business_id',
            'parent_id'
        )->distinct();
    }
}
