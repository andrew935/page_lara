<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainSettingsMinIntervalTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_cannot_set_check_interval_below_60_minutes(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/domains/settings', [
                'check_interval_minutes' => 10,
                'notify_on_fail' => 0,
                'notify_payload' => null,
                'feed_url' => null,
            ])
            ->assertSessionHasErrors(['check_interval_minutes']);
    }
}


