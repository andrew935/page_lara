<?php

namespace App\Identity;

use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    protected $fillable = [
        'account_id',
        'check_interval_minutes',
        'notify_on_fail',
        'channels',
        'feed_url',
    ];

    protected $casts = [
        'channels' => 'array',
        'notify_on_fail' => 'boolean',
    ];
}


