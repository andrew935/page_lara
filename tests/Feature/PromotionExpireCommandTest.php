<?php

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PromotionExpireCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_command_downgrades_to_free(): void
    {
        $this->seed();

        $free = Plan::where('slug', 'free')->firstOrFail();
        $max = Plan::where('slug', 'max')->firstOrFail();

        $account = Account::create([
            'name' => 'Promo Account',
            'timezone' => 'UTC',
        ]);

        Subscription::create([
            'account_id' => $account->id,
            'plan_id' => $max->id,
            'status' => 'active',
            'starts_at' => now()->subDays(2),
            'promo_ends_at' => now()->subMinute(),
        ]);

        Artisan::call('promotions:expire');

        $this->assertDatabaseHas('subscriptions', [
            'account_id' => $account->id,
            'plan_id' => $free->id,
        ]);
    }
}


