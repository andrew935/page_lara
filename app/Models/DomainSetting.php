<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_interval_minutes',
        'notify_on_fail',
        'notify_payload',
        'feed_url',
    ];
}

