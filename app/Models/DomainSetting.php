<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'check_interval_minutes',
        'notify_on_fail',
        'notify_payload',
        'feed_url',
        'auto_import_feed',
    ];

    protected $casts = [
        'notify_on_fail' => 'boolean',
        'auto_import_feed' => 'boolean',
    ];
}

