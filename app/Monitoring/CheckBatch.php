<?php

namespace App\Monitoring;

use App\Identity\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckBatch extends Model
{
    protected $fillable = [
        'account_id',
        'status',
        'total_domains',
        'processed_domains',
        'scheduled_for',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'scheduled_for' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}


