<?php

namespace Database\Seeders;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Identity\AccountSetting;
use App\Notifications\NotificationSetting;
use App\Models\DomainSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSetupSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Plan configurations: max_domains, check_interval_minutes, price_cents, history_retention_days
            ['name' => 'Free', 'slug' => 'free', 'max_domains' => 20, 'check_interval_minutes' => 60, 'price_cents' => 0, 'history_retention_days' => 0],
            ['name' => 'Starter', 'slug' => 'starter', 'max_domains' => 100, 'check_interval_minutes' => 30, 'price_cents' => 4900, 'history_retention_days' => 7],
            ['name' => 'Business', 'slug' => 'business', 'max_domains' => 200, 'check_interval_minutes' => 15, 'price_cents' => 7900, 'history_retention_days' => 30],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'max_domains' => 500, 'check_interval_minutes' => 10, 'price_cents' => 10900, 'history_retention_days' => 30],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'max_domains' => $plan['max_domains'],
                    'check_interval_minutes' => $plan['check_interval_minutes'],
                    'price_cents' => $plan['price_cents'],
                    'currency' => 'USD',
                    'active' => true,
                    'history_retention_days' => $plan['history_retention_days'] ?? 0,
                ]
            );
        }

        $account = Account::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Default Account',
                'timezone' => 'UTC',
                'owner_user_id' => User::first()?->id,
            ]
        );

        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            Subscription::firstOrCreate(
                ['account_id' => $account->id, 'plan_id' => $freePlan->id],
                ['status' => 'active', 'starts_at' => now()]
            );
        }

        AccountSetting::firstOrCreate(
            ['account_id' => $account->id],
            [
                'check_interval_minutes' => 60,
                'notify_on_fail' => false,
            ]
        );

        NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            [
                'notify_on_fail' => false,
                'channels' => [],
            ]
        );

        // Align legacy domain settings row to account-aware table
        $legacySetting = DomainSetting::first();
        if ($legacySetting && !$legacySetting->account_id) {
            $legacySetting->update(['account_id' => $account->id]);
        }
    }
}


