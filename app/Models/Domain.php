<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'domain',
        'campaign',
        'status',
        'status_since',
        'last_up_at',
        'last_down_at',
        'ssl_valid',
        'last_checked_at',
        'last_check_error',
        'lastcheck',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'status_since' => 'datetime',
        'last_up_at' => 'datetime',
        'last_down_at' => 'datetime',
        'ssl_valid' => 'boolean',
        'lastcheck' => 'array',
    ];
}

