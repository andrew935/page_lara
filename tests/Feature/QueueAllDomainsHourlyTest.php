<?php

namespace Tests\Feature;

use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueAllDomainsHourlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_hourly_command_queues_all_domains_for_all_accounts(): void
    {
        $this->seed();

        Domain::create([
            'account_id' => 1,
            'domain' => 'a.test',
            'status' => 'ok',
        ]);
        Domain::create([
            'account_id' => 1,
            'domain' => 'b.test',
            'status' => 'down',
        ]);

        Queue::fake();

        Artisan::call('domains:queue-all-hourly');

        Queue::assertPushed(CheckDomainJob::class, 2);
        $this->assertDatabaseHas('domains', [
            'account_id' => 1,
            'domain' => 'a.test',
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ]);
        $this->assertDatabaseHas('domains', [
            'account_id' => 1,
            'domain' => 'b.test',
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ]);
    }
}


