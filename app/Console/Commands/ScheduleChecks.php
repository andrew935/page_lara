<?php

namespace App\Console\Commands;

use App\Billing\Services\PlanRulesService;
use App\Identity\Account;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Models\DomainSetting;
use App\Monitoring\CheckBatch;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ScheduleChecks extends Command
{
    protected $signature = 'monitoring:schedule-checks';

    protected $description = 'Queue domain checks per account based on plan interval';

    public function handle(PlanRulesService $planRules): int
    {
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $planInterval = (int) $planRules->checkIntervalMinutes($account);
            $settingsInterval = (int) (DomainSetting::where('account_id', $account->id)->value('check_interval_minutes') ?? 0);
            // Enforce plan minimum interval (can't check more frequently than plan allows).
            $interval = $settingsInterval > 0 ? max($planInterval, $settingsInterval) : $planInterval;

            // Queue domains individually when due:
            // - never checked (last_checked_at is null) => due immediately
            // - checked before => due when last_checked_at <= now - interval
            // Also avoid re-queuing "just queued" pending rows every minute.
            $dueCutoff = now()->subMinutes($interval);
            $domains = Domain::where('account_id', $account->id)
                ->where(function (Builder $q) use ($dueCutoff) {
                    $q->whereNull('last_checked_at')
                        ->orWhere('last_checked_at', '<=', $dueCutoff);
                })
                ->where(function (Builder $q) {
                    $q->where('status', '!=', 'pending')
                        ->orWhereNull('last_check_error')
                        ->orWhere('last_check_error', '!=', 'Queued for check')
                        ->orWhere('updated_at', '<=', now()->subMinutes(5));
                })
                ->orderByRaw('last_checked_at is null desc')
                ->orderBy('last_checked_at')
                ->limit(500)
                ->get();

            if ($domains->isEmpty()) {
                continue;
            }

            $batch = CheckBatch::create([
                'account_id' => $account->id,
                'status' => 'processing',
                'total_domains' => $domains->count(),
                'processed_domains' => 0,
                'scheduled_for' => now(),
            ]);

            foreach ($domains as $domain) {
                $payload = [
                    'status' => 'pending',
                    'last_check_error' => 'Queued for check',
                ];
                if ($domain->status !== 'pending') {
                    $payload['status_since'] = now();
                }
                $domain->update($payload);
                CheckDomainJob::dispatch($domain->id);
            }

            $batch->update([
                'processed_domains' => $domains->count(),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->info("Queued {$domains->count()} domains for account {$account->id}");
        }

        return self::SUCCESS;
    }
}


