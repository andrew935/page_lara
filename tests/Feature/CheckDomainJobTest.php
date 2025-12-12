<?php

namespace Tests\Feature;

use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Notifications\NotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckDomainJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_updates_status_and_opens_incident(): void
    {
        $this->seed();
        $domain = Domain::create([
            'account_id' => 1,
            'domain' => 'down.test',
            'status' => 'ok',
        ]);

        NotificationSetting::updateOrCreate(
            ['account_id' => 1],
            ['notify_on_fail' => false]
        );

        $job = new CheckDomainJob($domain->id);
        $job->handle(new FakeChecker());

        $domain->refresh();
        $this->assertEquals('down', $domain->status);
        $this->assertDatabaseHas('domain_incidents', [
            'domain_id' => $domain->id,
            'status_after' => 'down',
        ]);
    }
}

class FakeChecker extends \App\Services\DomainCheckService
{
    public function check(string $domain): array
    {
        return [
            'status' => 'down',
            'ssl_valid' => false,
            'error' => 'Connection failed',
            'checked_at' => now(),
        ];
    }
}


