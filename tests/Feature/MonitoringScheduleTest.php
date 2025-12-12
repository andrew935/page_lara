<?php

namespace Tests\Feature;

use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonitoringScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_command_queues_checks(): void
    {
        $this->seed();
        Domain::create([
            'account_id' => 1,
            'domain' => 'scheduled.test',
            'status' => 'pending',
        ]);

        Queue::fake();

        Artisan::call('monitoring:schedule-checks');

        Queue::assertPushed(CheckDomainJob::class);
        $this->assertDatabaseHas('check_batches', [
            'account_id' => 1,
        ]);
    }
}


