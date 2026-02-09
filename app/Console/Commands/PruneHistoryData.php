<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Billing\Subscription;
use App\Domains\DomainIncident;
use App\Models\Domain;
use App\Monitoring\CheckBatch;
use App\Notifications\NotificationLog;
use Illuminate\Console\Command;

class PruneHistoryData extends Command
{
    protected $signature = 'data:prune-history';

    protected $description = 'Prune domain incidents, check batches, and notification logs older than plan retention.';

    public function handle(): int
    {
        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->with(['account', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $account = $subscription->account;
            $plan = $subscription->plan;
            if (!$account || !$plan) {
                continue;
            }

            $retentionDays = (int) ($plan->history_retention_days ?? 0);
            $cutoff = $retentionDays > 0
                ? now()->subDays($retentionDays)
                : now()->subDay(); // Free plan: keep 24 hours

            $accountId = $account->id;

            // Prune notification_logs
            $deletedLogs = NotificationLog::where('account_id', $accountId)
                ->where('created_at', '<', $cutoff)
                ->delete();

            // Prune check_batches
            $deletedBatches = CheckBatch::where('account_id', $accountId)
                ->where('created_at', '<', $cutoff)
                ->delete();

            // Prune domain_incidents for this account's domains
            $domainIds = Domain::where('account_id', $accountId)->pluck('id')->toArray();
            $deletedIncidents = 0;
            if (!empty($domainIds)) {
                $deletedIncidents = DomainIncident::whereIn('domain_id', $domainIds)
                    ->where('created_at', '<', $cutoff)
                    ->delete();
            }

            if ($deletedLogs > 0 || $deletedBatches > 0 || $deletedIncidents > 0) {
                $this->info("Account {$accountId} ({$plan->name}, {$retentionDays}d): pruned {$deletedLogs} logs, {$deletedBatches} batches, {$deletedIncidents} incidents.");
            }
        }

        return self::SUCCESS;
    }
}
