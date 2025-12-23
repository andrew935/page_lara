<?php

namespace Tests\Feature;

use App\Jobs\CheckDomainJob;
use App\Jobs\ProcessImportBatchJob;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_flow_from_api(): void
    {
        $this->seed();
        $user = User::factory()->create();
        Queue::fake([CheckDomainJob::class, ProcessImportBatchJob::class]);

        $this->actingAs($user)
            ->postJson('/api/domains', ['domains' => "example.com\nfoo.test"])
            ->assertOk()
            ->assertJsonFragment(['created' => 2]);

        $this->actingAs($user)
            ->postJson('/api/domains/check-all')
            ->assertOk()
            ->assertJsonStructure(['domains']);

        $this->actingAs($user)
            ->postJson('/api/domains/test-all')
            ->assertOk()
            ->assertJsonStructure(['domains']);

        Queue::assertPushed(CheckDomainJob::class);

        $this->actingAs($user)
            ->postJson('/api/imports/json', ['json' => json_encode(['bar.test'])])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/api/imports/json', ['json' => 'baz.test qux.test,example.net'])
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/plans')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'free']);

        $this->actingAs($user)
            ->postJson('/api/subscriptions', ['plan' => 'pro'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/api/notifications/settings', [
                'notify_on_fail' => true,
                'channels' => ['telegram'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('domains', [
            'domain' => 'example.com',
        ]);
    }
}


