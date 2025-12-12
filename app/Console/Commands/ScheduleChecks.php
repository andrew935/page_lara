<?php

namespace App\Console\Commands;

use App\Billing\Services\PlanRulesService;
use App\Identity\Account;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Monitoring\CheckBatch;
use Illuminate\Console\Command;

class ScheduleChecks extends Command
{
    protected $signature = 'monitoring:schedule-checks';

    protected $description = 'Queue domain checks per account based on plan interval';

    public function handle(PlanRulesService $planRules): int
    {
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $interval = $planRules->checkIntervalMinutes($account);
            $latestBatch = CheckBatch::where('account_id', $account->id)
                ->orderByDesc('created_at')
                ->first();

            $due = !$latestBatch || $latestBatch->created_at->addMinutes($interval)->lte(now());
            if (!$due) {
                $this->line("Skipping account {$account->id}, not due yet.");
                continue;
            }

            $domains = Domain::where('account_id', $account->id)
                ->orderBy('last_checked_at')
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


