<?php

namespace App\Jobs;

use App\Identity\Account;
use App\Models\Domain;
use App\Domains\DomainIncident;
use App\Notifications\NotificationSetting;
use App\Services\DomainCheckService;
use App\Services\DomainExpirationService;
use App\Jobs\SendAlertJob;
use App\Jobs\NotifyDomainDownJob;
use App\Jobs\NotifyDomainUpJob;
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

    public function handle(DomainCheckService $checker, DomainExpirationService $expirationService): void
    {
        $domain = Domain::find($this->domainId);
        if (!$domain) {
            return;
        }

        $oldStatus = $domain->status;

        $accountId = $domain->account_id ?? 1;
        $account = Account::find($accountId);
        $subscription = $account?->activeSubscription()->with('plan')->first();
        $plan = $subscription?->plan;
        
        // SSL checking is enabled for all paid plans (price_cents > 0)
        // Paid plans: starter, business, enterprise
        // Free plan: price_cents = 0, SSL checking disabled
        $checkSsl = $plan && $plan->price_cents > 0;

        $result = $checker->check($domain->domain, $checkSsl);
        
        // Domain expiration checking is enabled for all paid plans (price_cents > 0)
        // Free plan: expiration checking disabled
        $isPaidPlan = $plan && $plan->price_cents > 0;
        
        // Check domain expiration (once per day to avoid rate limits) - only for paid plans
        $shouldCheckExpiration = $isPaidPlan && (
            !$domain->expires_checked_at 
            || $domain->expires_checked_at->lt(now()->subDay())
        );
        
        if ($shouldCheckExpiration) {
            try {
                $expirationResult = $expirationService->checkExpiration($domain->domain);
                if ($expirationResult['expires_at']) {
                    $result['expires_at'] = $expirationResult['expires_at'];
                    $result['expires_checked_at'] = now();
                }
            } catch (\Throwable $e) {
                Log::debug("Failed to check expiration for {$domain->domain}: " . $e->getMessage());
            }
        } elseif (!$isPaidPlan) {
            // For free plans, clear expiration data if it exists (from previous paid plan)
            if ($domain->expires_at || $domain->expires_checked_at) {
                $result['expires_at'] = null;
                $result['expires_checked_at'] = null;
            }
        }

        $payload = [
            'status' => $result['status'],
            'ssl_valid' => $result['ssl_valid'],
            'last_checked_at' => $result['checked_at'],
            'last_check_error' => $result['error'],
        ];
        
        // Add expiration data if checked
        if (isset($result['expires_at'])) {
            $payload['expires_at'] = $result['expires_at'];
        }
        if (isset($result['expires_checked_at'])) {
            $payload['expires_checked_at'] = $result['expires_checked_at'];
        }

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
                $payload['up_notified_at'] = null; // reset so next recovery can notify again
            }
        }

        $domain->update($payload);

        $domain->refresh();

        // If we just transitioned to DOWN, schedule a delayed notify so alerts fire after ~3 minutes
        // even when the check interval is longer than 3 minutes.
        if ($oldStatus !== 'down' && $domain->status === 'down') {
            NotifyDomainDownJob::dispatch($domain->id)->delay(now()->addMinutes(3));
        }

        // If we just transitioned to UP (recovered), schedule a delayed notify to fire after 5 minutes
        // to ensure the domain is stable before sending the "UP" notification.
        if ($oldStatus === 'down' && $domain->status === 'ok') {
            NotifyDomainUpJob::dispatch($domain->id)->delay(now()->addMinutes(5));
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

