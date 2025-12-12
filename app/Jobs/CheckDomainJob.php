<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Domains\DomainIncident;
use App\Notifications\NotificationSetting;
use App\Services\DomainCheckService;
use App\Jobs\SendAlertJob;
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

        if ($oldStatus !== $result['status']) {
            $payload['status_since'] = now();
            if ($result['status'] === 'ok') {
                $payload['last_up_at'] = now();
            } elseif ($result['status'] === 'down') {
                $payload['last_down_at'] = now();
            }
        }

        $domain->update($payload);

        if ($oldStatus !== 'down' && $result['status'] === 'down') {
            $this->openIncident($domain, $oldStatus, $result['status'], $result['error']);
            $this->notifyDown($domain, $result['error']);
        } elseif ($oldStatus === 'down' && $result['status'] === 'ok') {
            $this->closeIncident($domain);
        }
    }

    protected function notifyDown(Domain $domain, ?string $error): void
    {
        $settings = NotificationSetting::where('account_id', $domain->account_id)->first();
        if (!$settings || !$settings->notify_on_fail) {
            return;
        }

        $message = "Domain {$domain->domain} is DOWN";
        if ($error) {
            $message .= " — {$error}";
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

