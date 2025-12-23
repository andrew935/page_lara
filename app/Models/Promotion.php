<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'active',
        'starts_at',
        'ends_at',
        'promo_plan_slug',
        'duration_days',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}


