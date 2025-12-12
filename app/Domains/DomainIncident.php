<?php

namespace App\Domains;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainIncident extends Model
{
    protected $fillable = [
        'domain_id',
        'status_before',
        'status_after',
        'opened_at',
        'closed_at',
        'message',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}


