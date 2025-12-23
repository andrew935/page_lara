<?php

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Identity\AccountUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upgrade_to_next_plan(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $account = Account::firstOrCreate(['id' => 1], ['name' => 'Default Account', 'timezone' => 'UTC']);
        AccountUserRole::firstOrCreate(['user_id' => $user->id, 'account_id' => $account->id], ['role' => 'user']);

        $pro = Plan::where('slug', 'pro')->firstOrFail();

        $this->actingAs($user)
            ->post(route('account.upgrade'), ['plan' => 'pro'])
            ->assertRedirect(route('account.show'));

        $this->assertDatabaseHas('subscriptions', [
            'account_id' => $account->id,
            'plan_id' => $pro->id,
            'status' => 'active',
        ]);

        $subscription = Subscription::where('account_id', $account->id)->first();
        $this->assertNotNull($subscription);
    }
}


