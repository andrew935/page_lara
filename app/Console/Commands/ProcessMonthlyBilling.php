<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Billing\Services\StripeService;
use App\Billing\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly billing for all active subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(StripeService $stripeService): int
    {
        $this->info('Starting monthly billing process...');

        // Get all active subscriptions that are due for renewal
        $subscriptions = Subscription::where('status', 'active')
            ->whereNotNull('renews_at')
            ->where('renews_at', '<=', now())
            ->with(['plan', 'account'])
            ->get();

        $this->info("Found {$subscriptions->count()} subscription(s) due for renewal.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->line("Processing subscription #{$subscription->id} for account: {$subscription->account->name}");

            try {
                $result = $stripeService->processMonthlyBilling($subscription);

                if ($result) {
                    $successCount++;
                    $this->info("  ✓ Successfully billed subscription #{$subscription->id}");
                } else {
                    $failureCount++;
                    $this->error("  ✗ Failed to bill subscription #{$subscription->id}");
                }
            } catch (\Exception $e) {
                $failureCount++;
                $this->error("  ✗ Error processing subscription #{$subscription->id}: {$e->getMessage()}");
                Log::error('Monthly billing command error', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Monthly billing complete!");
        $this->info("Successful: {$successCount}");
        $this->info("Failed: {$failureCount}");

        return Command::SUCCESS;
    }
}
