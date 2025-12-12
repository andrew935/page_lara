<?php

namespace App\Billing;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'max_domains',
        'check_interval_minutes',
        'price_cents',
        'currency',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}


