<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Billing\Plan;
use App\Billing\Subscription;
use Illuminate\Console\Command;

class ExpirePromotions extends Command
{
    protected $signature = 'promotions:expire';

    protected $description = 'Downgrade accounts whose promo period has ended back to Free';

    public function handle(): int
    {
        $freePlan = Plan::where('slug', 'free')->where('active', true)->first();
        if (!$freePlan) {
            $this->error('Free plan not found.');
            return self::FAILURE;
        }

        $expired = Subscription::query()
            ->whereNotNull('promo_ends_at')
            ->where('promo_ends_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            // Only downgrade if currently on the promo plan (typically Max)
            if ((int) $subscription->plan_id !== (int) $freePlan->id) {
                $subscription->update([
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'promo_ends_at' => null,
                    'promo_source_promotion_id' => null,
                ]);
                $count++;
            } else {
                // Cleanup promo fields even if already on Free
                $subscription->update([
                    'promo_ends_at' => null,
                    'promo_source_promotion_id' => null,
                ]);
            }
        }

        $this->info("Expired promotions processed: {$count}");

        return self::SUCCESS;
    }
}


