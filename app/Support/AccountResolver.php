<?php

namespace App\Support;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Identity\AccountUserRole;
use App\Notifications\NotificationSetting;
use Illuminate\Support\Facades\Auth;

class AccountResolver
{
    /**
     * Resolve the current account, defaulting to a shared account.
     */
    public static function current(): Account
    {
        $user = Auth::user();

        if ($user) {
            $existing = AccountUserRole::where('user_id', $user->id)->first();
            if ($existing) {
                return Account::findOrFail($existing->account_id);
            }
        }

        return self::defaultAccount();
    }

    /**
     * Ensure there is at least one default account with an active plan.
     */
    public static function defaultAccount(): Account
    {
        $account = Account::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Default Account',
                'timezone' => 'UTC',
            ]
        );

        // Ensure plans exist
        self::ensurePlans();

        // Ensure a subscription is attached
        if (!$account->activeSubscription) {
            $plan = Plan::where('slug', 'free')->first();
            if ($plan) {
                Subscription::firstOrCreate(
                    ['account_id' => $account->id, 'plan_id' => $plan->id],
                    ['status' => 'active', 'starts_at' => now()]
                );
            }
        }

        // Ensure notification settings exist
        NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            [
                'notify_on_fail' => false,
                'channels' => [],
            ]
        );

        return $account;
    }

    protected static function ensurePlans(): void
    {
        $plans = [
            // Plan minimum check intervals:
            // - Free: 60 minutes
            // - Pro: 30 minutes
            // - Max: 10 minutes
            ['name' => 'Free', 'slug' => 'free', 'max_domains' => 50, 'check_interval_minutes' => 60, 'price_cents' => 0],
            ['name' => 'Pro', 'slug' => 'pro', 'max_domains' => 200, 'check_interval_minutes' => 30, 'price_cents' => 2900],
            ['name' => 'Max', 'slug' => 'max', 'max_domains' => 500, 'check_interval_minutes' => 10, 'price_cents' => 9900],
        ];

        foreach ($plans as $plan) {
            // Keep plan values in sync across deploys.
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'max_domains' => $plan['max_domains'],
                    'check_interval_minutes' => $plan['check_interval_minutes'],
                    'price_cents' => $plan['price_cents'],
                    'currency' => 'USD',
                    'active' => true,
                ]
            );
        }
    }
}


