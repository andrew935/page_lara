<?php

namespace App\Billing\Services;

use App\Billing\Plan;
use App\Identity\Account;

class PlanRulesService
{
    public function maxDomains(Account $account): int
    {
        $plan = $this->resolvePlan($account);
        // Default free plan: up to 20 domains.
        return $plan?->max_domains ?? 20;
    }

    public function checkIntervalMinutes(Account $account): int
    {
        $plan = $this->resolvePlan($account);
        return $plan?->check_interval_minutes ?? 60;
    }

    protected function resolvePlan(Account $account): ?Plan
    {
        $subscription = $account->activeSubscription()->first();
        if (!$subscription) {
            return null;
        }
        return $subscription->plan;
    }
}


