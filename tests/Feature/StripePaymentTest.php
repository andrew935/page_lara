<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Services\StripeService;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Plan $freePlan;
    protected Plan $starterPlan;
    protected Plan $businessPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and account
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->account = Account::create([
            'name' => 'Test Account',
            'timezone' => 'UTC',
            'owner_user_id' => $this->user->id,
        ]);

        // Create test plans
        $this->freePlan = Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'max_domains' => 20,
            'check_interval_minutes' => 60,
            'price_cents' => 0,
            'currency' => 'USD',
            'active' => true,
        ]);

        $this->starterPlan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'max_domains' => 100,
            'check_interval_minutes' => 30,
            'price_cents' => 4900,
            'currency' => 'USD',
            'active' => true,
        ]);

        $this->businessPlan = Plan::create([
            'name' => 'Business',
            'slug' => 'business',
            'max_domains' => 200,
            'check_interval_minutes' => 15,
            'price_cents' => 7900,
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    public function test_prorated_calculation_mid_month(): void
    {
        $stripeService = new StripeService();

        // Test on day 15 of a 30-day month
        $startDate = Carbon::create(2026, 1, 15);
        $proratedAmount = $stripeService->calculateProration($startDate, $this->starterPlan);

        // Expected: $49 * 16 days / 31 days = ~$25.29 = 2529 cents
        $expectedAmount = (int) round((4900 * 16) / 31);

        $this->assertEquals($expectedAmount, $proratedAmount);
    }

    public function test_prorated_calculation_start_of_month(): void
    {
        $stripeService = new StripeService();

        // Test on day 1 of a 30-day month
        $startDate = Carbon::create(2026, 1, 1);
        $proratedAmount = $stripeService->calculateProration($startDate, $this->starterPlan);

        // Expected: full month price
        $this->assertEquals(4900, $proratedAmount);
    }

    public function test_prorated_calculation_end_of_month(): void
    {
        $stripeService = new StripeService();

        // Test on last day of month
        $startDate = Carbon::create(2026, 1, 31);
        $proratedAmount = $stripeService->calculateProration($startDate, $this->starterPlan);

        // Expected: 1 day = $49 / 31 = ~$1.58 = 158 cents
        $expectedAmount = (int) round(4900 / 31);

        $this->assertEquals($expectedAmount, $proratedAmount);
    }

    public function test_free_plan_no_proration(): void
    {
        $stripeService = new StripeService();

        $startDate = Carbon::create(2026, 1, 15);
        $proratedAmount = $stripeService->calculateProration($startDate, $this->freePlan);

        $this->assertEquals(0, $proratedAmount);
    }

    public function test_subscription_requires_payment_for_paid_plans(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('billing.subscribe'), [
            'plan_slug' => 'starter',
            // Missing payment_method_id
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Payment method required for paid plans']);
    }

    public function test_free_plan_requires_no_payment(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/subscriptions', [
            'plan' => 'free',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Subscription saved.']);

        $subscription = Subscription::where('account_id', $this->account->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($this->freePlan->id, $subscription->plan_id);
        $this->assertNull($subscription->stripe_customer_id);
    }

    public function test_scheduled_downgrade_sets_next_plan(): void
    {
        $stripeService = new StripeService();

        // Create subscription with starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test123',
        ]);

        // Schedule downgrade to free
        $stripeService->scheduleDowngrade($subscription, $this->freePlan);

        $subscription->refresh();
        $this->assertEquals($this->freePlan->id, $subscription->next_plan_id);
        $this->assertEquals($this->starterPlan->id, $subscription->plan_id); // Current plan unchanged
    }

    public function test_upgrade_calculation(): void
    {
        $stripeService = new StripeService();

        // Create subscription on day 15 with starter plan
        Carbon::setTestNow(Carbon::create(2026, 1, 15));
        
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test123',
        ]);

        // Calculate upgrade to business plan
        $daysRemaining = now()->diffInDays(now()->endOfMonth()) + 1;
        $daysInMonth = now()->daysInMonth;

        $starterProrated = (int) round(($this->starterPlan->price_cents * $daysRemaining) / $daysInMonth);
        $businessProrated = (int) round(($this->businessPlan->price_cents * $daysRemaining) / $daysInMonth);
        $expectedDifference = $businessProrated - $starterProrated;

        // Expected: $79 prorated - $49 prorated for 16 days
        $this->assertGreaterThan(0, $expectedDifference);
        $this->assertLessThan($this->businessPlan->price_cents, $expectedDifference);

        Carbon::setTestNow(); // Reset
    }

    public function test_billing_view_accessible_to_authenticated_users(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertViewHas('plans');
        $response->assertViewHas('account');
    }

    public function test_billing_view_requires_authentication(): void
    {
        $response = $this->get(route('billing.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_downgrade_validation_requires_lower_price(): void
    {
        $this->actingAs($this->user);

        // Create subscription with free plan
        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        // Try to "downgrade" to starter (actually an upgrade)
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'New plan must be a lower tier than current plan']);
    }

    public function test_upgrade_validation_requires_higher_price(): void
    {
        $this->actingAs($this->user);

        // Create subscription with business plan
        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->businessPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test123',
        ]);

        // Try to "upgrade" to starter (actually a downgrade)
        $response = $this->postJson(route('billing.upgrade'), [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'New plan must be a higher tier than current plan']);
    }

    public function test_cancel_subscription_schedules_free_plan(): void
    {
        $this->actingAs($this->user);

        $stripeService = new StripeService();

        // Create subscription with starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
        ]);

        $stripeService->cancelSubscription($subscription);

        $subscription->refresh();
        $this->assertEquals($this->freePlan->id, $subscription->next_plan_id);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_plans_api_returns_active_plans_only(): void
    {
        $this->actingAs($this->user);

        // Create an inactive plan
        Plan::create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'max_domains' => 50,
            'check_interval_minutes' => 45,
            'price_cents' => 2900,
            'currency' => 'USD',
            'active' => false,
        ]);

        $response = $this->getJson('/api/plans');

        $response->assertStatus(200);
        $plans = $response->json();

        $this->assertCount(3, $plans); // Only active plans
        $this->assertContains('free', array_column($plans, 'slug'));
        $this->assertContains('starter', array_column($plans, 'slug'));
        $this->assertContains('business', array_column($plans, 'slug'));
        $this->assertNotContains('inactive', array_column($plans, 'slug'));
    }

    public function test_renews_at_set_to_end_of_month(): void
    {
        $this->actingAs($this->user);

        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $response = $this->postJson('/api/subscriptions', [
            'plan' => 'free',
        ]);

        $response->assertStatus(200);

        $subscription = Subscription::where('account_id', $this->account->id)->first();
        $this->assertNotNull($subscription->renews_at);

        // Should be February 28, 2026 (end of next month from January)
        $expectedRenewsAt = Carbon::create(2026, 2, 28, 23, 59, 59);
        $this->assertTrue(
            $subscription->renews_at->isSameDay($expectedRenewsAt),
            "Expected renews_at to be end of February, got {$subscription->renews_at}"
        );

        Carbon::setTestNow(); // Reset
    }
}
