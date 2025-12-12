<?php

namespace App\Imports;

use App\Identity\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    protected $fillable = [
        'account_id',
        'source',
        'status',
        'total',
        'processed',
        'failed',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}


