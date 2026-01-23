<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'iso_code', 'phone_code', 'currency_code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
