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

class NotifyDomainUpJob implements ShouldQueue
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

        // Only notify if:
        // - Domain is still 'ok' (stable recovery)
        // - We haven't already notified for this recovery (up_notified_at is null)
        if (
            $domain->status !== 'ok'
            || $domain->up_notified_at
        ) {
            return;
        }

        // Calculate downtime duration
        $downtimeMinutes = 0;
        if ($domain->last_down_at && $domain->last_up_at) {
            $downtimeMinutes = (int) round($domain->last_down_at->diffInMinutes($domain->last_up_at));
        }

        // Escape dynamic values because we send Telegram messages with parse_mode=HTML.
        $safeDomain = htmlspecialchars((string) $domain->domain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCampaign = htmlspecialchars((string) ($domain->campaign ?: '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Build message with green circle emoji and bold "UP"
        $message = "Robot - https://tech-robot-automation.com\n"
            ."Domain: {$safeDomain}\n"
            ."Campaign: {$safeCampaign}\n"
            ."Status: 🟢 <b>UP</b> (was down for {$downtimeMinutes} minute(s))";

        // Reuse the existing alert job + settings logic (notify_on_fail + configured channels).
        SendAlertJob::dispatch($domain->account_id, $domain->id, $message);

        $domain->update(['up_notified_at' => now()]);
    }
}

