<?php

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanDomainLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_does_not_count_duplicates_towards_limit(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $account = Account::firstOrCreate(['id' => 1], ['name' => 'Default Account', 'timezone' => 'UTC']);

        // Force a tiny plan limit for the test.
        $free = Plan::where('slug', 'free')->firstOrFail();
        $free->update(['max_domains' => 2]);
        Subscription::updateOrCreate(
            ['account_id' => $account->id],
            ['plan_id' => $free->id, 'status' => 'active', 'starts_at' => now()]
        );

        Domain::create(['account_id' => $account->id, 'domain' => 'dup.test', 'status' => 'pending']);

        $this->actingAs($user)
            ->postJson('/api/imports/json', ['json' => 'dup.test new1.test new2.test'])
            ->assertOk();

        // Limit is 2: existing dup.test + only one new domain should be created.
        $this->assertDatabaseHas('domains', ['account_id' => $account->id, 'domain' => 'new1.test']);
        $this->assertDatabaseMissing('domains', ['account_id' => $account->id, 'domain' => 'new2.test']);
    }
}


