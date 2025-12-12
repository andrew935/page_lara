<?php

namespace App\Notifications;

use App\Identity\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'account_id',
        'channel',
        'status',
        'message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}


