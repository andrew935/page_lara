<?php

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PromotionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_during_promo_window_gets_max_for_duration(): void
    {
        $this->seed();

        $max = Plan::where('slug', 'max')->firstOrFail();

        Promotion::create([
            'name' => 'Test Promo',
            'active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'promo_plan_slug' => 'max',
            'duration_days' => 60,
        ]);

        $this->post('/register', [
            'name' => 'Promo User',
            'email' => 'promo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/domains');

        $sub = Subscription::query()->first();
        $this->assertNotNull($sub);
        $this->assertSame($max->id, $sub->plan_id);
        $this->assertNotNull($sub->promo_ends_at);

        $diff = Carbon::parse($sub->promo_ends_at)->diffInDays(now());
        $this->assertTrue($diff >= 59 && $diff <= 60);
    }
}


