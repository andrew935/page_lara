<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Jobs\SendAlertJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyDomainDownJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(public int $domainId)
    {
    }

    public function handle(): void
    {
        $domain = Domain::find($this->domainId);
        if (!$domain) {
            return;
        }

        // Only notify if still down, down has lasted >= 3 minutes, and not notified yet for this downtime.
        if (
            $domain->status !== 'down'
            || $domain->down_notified_at
            || !$domain->status_since
            || $domain->status_since->gt(now()->subMinutes(3))
        ) {
            return;
        }

        $minutes = max(3, $domain->status_since->diffInMinutes(now()));
        $message = "Domain {$domain->domain} is DOWN for {$minutes} minute(s)";

        // Reuse the existing alert job + settings logic (notify_on_fail + configured channels).
        SendAlertJob::dispatch($domain->account_id, $domain->id, $message);

        $domain->update(['down_notified_at' => now()]);
    }
}


