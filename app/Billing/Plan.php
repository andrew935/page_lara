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
        'history_retention_days',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}


