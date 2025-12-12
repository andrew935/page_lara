<?php

namespace App\Notifications;

use App\Identity\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'account_id',
        'notify_on_fail',
        'email',
        'telegram_chat_id',
        'telegram_api_key',
        'slack_webhook_url',
        'channels',
    ];

    protected $casts = [
        'channels' => 'array',
        'notify_on_fail' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}


