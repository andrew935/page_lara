<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Domains\DomainIncident;
use App\Notifications\NotificationSetting;
use App\Services\DomainCheckService;
use App\Jobs\SendAlertJob;
use App\Jobs\NotifyDomainDownJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $domainId)
    {
    }

    public function handle(DomainCheckService $checker): void
    {
        $domain = Domain::find($this->domainId);
        if (!$domain) {
            return;
        }

        $oldStatus = $domain->status;
        $result = $checker->check($domain->domain);

        $payload = [
            'status' => $result['status'],
            'ssl_valid' => $result['ssl_valid'],
            'last_checked_at' => $result['checked_at'],
            'last_check_error' => $result['error'],
        ];

        $history = is_array($domain->lastcheck) ? $domain->lastcheck : [];
        $history[] = $result['status'] === 'ok' ? 1 : 0;
        $payload['lastcheck'] = array_slice($history, -24);

        if ($oldStatus !== $result['status']) {
            $payload['status_since'] = now();
            if ($result['status'] === 'ok') {
                $payload['last_up_at'] = now();
                $payload['down_notified_at'] = null; // reset so next down can notify again
            } elseif ($result['status'] === 'down') {
                $payload['last_down_at'] = now();
            }
        }

        $domain->update($payload);

        $domain->refresh();

        // If we just transitioned to DOWN, schedule a delayed notify so alerts fire after ~3 minutes
        // even when the check interval is longer than 3 minutes.
        if ($oldStatus !== 'down' && $domain->status === 'down') {
            NotifyDomainDownJob::dispatch($domain->id)->delay(now()->addMinutes(3));
        }

        if ($oldStatus !== 'down' && $domain->status === 'down') {
            $this->openIncident($domain, $oldStatus, $domain->status, $domain->last_check_error);
        } elseif ($oldStatus === 'down' && $domain->status === 'ok') {
            $this->closeIncident($domain);
        }

        // Notify (Telegram/email/slack) ONLY if:
        // - still down for at least 3 minutes
        // - and we haven't already notified during this down period
        if (
            $domain->status === 'down'
            && !$domain->down_notified_at
            && $domain->status_since
            && $domain->status_since->lte(now()->subMinutes(3))
        ) {
            $minutes = $domain->status_since->diffInMinutes(now());
            $message = "Domain {$domain->domain} is DOWN for {$minutes} minute(s)";
            $this->notifyDown($domain, $message);
            $domain->update(['down_notified_at' => now()]);
        }
    }

    protected function notifyDown(Domain $domain, string $message): void
    {
        $settings = NotificationSetting::where('account_id', $domain->account_id)->first();
        if (!$settings || !$settings->notify_on_fail) {
            return;
        }

        SendAlertJob::dispatch($domain->account_id, $domain->id, $message);
    }

    protected function openIncident(Domain $domain, ?string $before, string $after, ?string $error): void
    {
        DomainIncident::create([
            'domain_id' => $domain->id,
            'status_before' => $before,
            'status_after' => $after,
            'opened_at' => now(),
            'message' => $error,
        ]);
    }

    protected function closeIncident(Domain $domain): void
    {
        $incident = DomainIncident::where('domain_id', $domain->id)
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->first();

        if ($incident) {
            $incident->update([
                'closed_at' => now(),
                'status_after' => 'ok',
            ]);
        }
    }
}

