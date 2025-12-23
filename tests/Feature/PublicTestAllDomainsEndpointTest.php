<?php

namespace Tests\Feature;

use App\Identity\Account;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicTestAllDomainsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_get_requires_bearer_token(): void
    {
        $this->seed();
        config(['services.cloudflare.webhook_secret' => 'secret']);

        $this->getJson('/api/domains/test-all')
            ->assertStatus(401);
    }

    public function test_public_get_queues_all_domains_across_all_accounts(): void
    {
        $this->seed();
        config(['services.cloudflare.webhook_secret' => 'secret']);

        $account2 = Account::create([
            'name' => 'Second',
            'timezone' => 'UTC',
        ]);

        Domain::create([
            'account_id' => 1,
            'domain' => 'public-a.test',
            'status' => 'ok',
        ]);
        Domain::create([
            'account_id' => $account2->id,
            'domain' => 'public-b.test',
            'status' => 'ok',
        ]);

        Queue::fake([CheckDomainJob::class]);

        $this->withHeader('Authorization', 'Bearer secret')
            ->getJson('/api/domains/test-all')
            ->assertOk()
            ->assertJsonFragment(['queued' => 2]);

        Queue::assertPushed(CheckDomainJob::class, 2);

        $this->assertDatabaseHas('domains', [
            'domain' => 'public-a.test',
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ]);
        $this->assertDatabaseHas('domains', [
            'domain' => 'public-b.test',
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ]);
    }
}


