<?php

namespace App\Billing;

use App\Identity\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'account_id',
        'plan_id',
        'status',
        'starts_at',
        'renews_at',
        'ends_at',
        'canceled_at',
        'promo_ends_at',
        'promo_source_promotion_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'renews_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'promo_ends_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}


