<?php

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanSslEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_skips_ssl_and_keeps_ssl_valid_null(): void
    {
        $this->seed();

        // Ensure account is on Free
        $account = Account::firstOrCreate(['id' => 1], ['name' => 'Default Account', 'timezone' => 'UTC']);
        $free = Plan::where('slug', 'free')->firstOrFail();
        Subscription::updateOrCreate(
            ['account_id' => $account->id],
            ['plan_id' => $free->id, 'status' => 'active', 'starts_at' => now()]
        );

        $domain = Domain::create([
            'account_id' => $account->id,
            'domain' => 'example.com',
            'status' => 'pending',
        ]);

        // Simulate reachable HTTPS with verify=false path.
        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        (new CheckDomainJob($domain->id))->handle(app(\App\Services\DomainCheckService::class));

        $domain->refresh();
        $this->assertSame('ok', $domain->status);
        $this->assertNull($domain->ssl_valid);
    }

    public function test_pro_plan_runs_ssl_check_and_sets_ssl_valid_boolean(): void
    {
        $this->seed();

        $account = Account::firstOrCreate(['id' => 1], ['name' => 'Default Account', 'timezone' => 'UTC']);
        $pro = Plan::where('slug', 'pro')->firstOrFail();
        Subscription::updateOrCreate(
            ['account_id' => $account->id],
            ['plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now()]
        );

        $domain = Domain::create([
            'account_id' => $account->id,
            'domain' => 'example.com',
            'status' => 'pending',
        ]);

        // Reachability request (verify=true); SSL check uses stream socket and can't be reliably faked here,
        // but it should set ssl_valid to true/false (not null).
        Http::fake([
            'https://example.com' => Http::response('', 200),
        ]);

        (new CheckDomainJob($domain->id))->handle(app(\App\Services\DomainCheckService::class));

        $domain->refresh();
        $this->assertNotNull($domain->ssl_valid);
        $this->assertIsBool($domain->ssl_valid);
    }
}


