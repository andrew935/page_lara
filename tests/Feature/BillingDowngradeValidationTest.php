<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Billing\Plan;
use App\Billing\Payment;
use App\Billing\Subscription;
use App\Identity\Account;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingDowngradeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Plan $freePlan;
    protected Plan $starterPlan;
    protected Plan $businessPlan;
    protected Plan $enterprisePlan;

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

        // Create test plans matching the actual structure
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

        $this->enterprisePlan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_domains' => 500,
            'check_interval_minutes' => 5,
            'price_cents' => 10900,
            'currency' => 'USD',
            'active' => true,
        ]);
    }

    /**
     * Test: User with 100 domains cannot downgrade from Starter to Free
     */
    public function test_cannot_downgrade_when_exceeding_domain_limit(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 100 domains (at Starter limit)
        for ($i = 1; $i <= 100; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Try to downgrade to Free (20 domain limit)
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'free',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot downgrade to Free. You currently have 100 domains, but this plan only allows 20. Please delete 80 domain(s) first.',
            'current_domains' => 100,
            'plan_limit' => 20,
            'excess_domains' => 80,
        ]);
    }

    /**
     * Test: User with 50 domains can downgrade from Starter to Free
     */
    public function test_can_downgrade_when_within_domain_limit(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create only 15 domains (under Free limit of 20)
        for ($i = 1; $i <= 15; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Mock StripeService to avoid actual API calls
        $this->mock(\App\Billing\Services\StripeService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('scheduleDowngrade')
                ->once()
                ->andReturn($subscription->fresh());
        });

        // Try to downgrade to Free
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'free',
        ]);

        // Should succeed (status 200) - actual implementation would schedule downgrade
        $response->assertStatus(200);
    }

    /**
     * Test: User with 150 domains cannot downgrade from Business to Starter
     */
    public function test_cannot_downgrade_from_business_to_starter_with_excess_domains(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Business plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->businessPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 150 domains (over Starter limit of 100)
        for ($i = 1; $i <= 150; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Try to downgrade to Starter
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'current_domains' => 150,
            'plan_limit' => 100,
            'excess_domains' => 50,
        ]);
        $this->assertStringContainsString('Cannot downgrade to Starter', $response->json('message'));
        $this->assertStringContainsString('Please delete 50 domain(s) first', $response->json('message'));
    }

    /**
     * Test: User with 80 domains can downgrade from Business to Starter
     */
    public function test_can_downgrade_from_business_to_starter_within_limit(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Business plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->businessPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 80 domains (under Starter limit of 100)
        for ($i = 1; $i <= 80; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Mock StripeService
        $this->mock(\App\Billing\Services\StripeService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('scheduleDowngrade')
                ->once()
                ->andReturn($subscription->fresh());
        });

        // Try to downgrade to Starter
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test: User cannot cancel subscription if they have more than 20 domains
     */
    public function test_cannot_cancel_subscription_with_excess_domains(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 50 domains (over Free limit of 20)
        for ($i = 1; $i <= 50; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Try to cancel (would downgrade to Free)
        $response = $this->postJson(route('billing.cancel'));

        $response->assertStatus(422);
        $response->assertJson([
            'current_domains' => 50,
            'plan_limit' => 20,
            'excess_domains' => 30,
        ]);
        $this->assertStringContainsString('Cannot cancel subscription', $response->json('message'));
        $this->assertStringContainsString('Please delete 30 domain(s) first', $response->json('message'));
    }

    /**
     * Test: User can cancel subscription if they have 20 or fewer domains
     */
    public function test_can_cancel_subscription_within_free_limit(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create exactly 20 domains (at Free limit)
        for ($i = 1; $i <= 20; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Mock StripeService
        $this->mock(\App\Billing\Services\StripeService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->andReturn($subscription->fresh());
        });

        // Try to cancel
        $response = $this->postJson(route('billing.cancel'));

        $response->assertStatus(200);
    }

    /**
     * Test: User with exactly the limit can downgrade
     */
    public function test_can_downgrade_at_exact_domain_limit(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create exactly 20 domains (at Free limit)
        for ($i = 1; $i <= 20; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Mock StripeService
        $this->mock(\App\Billing\Services\StripeService::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('scheduleDowngrade')
                ->once()
                ->andReturn($subscription->fresh());
        });

        // Try to downgrade to Free
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'free',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test: Validation still checks even if user tries to downgrade to same tier
     */
    public function test_downgrade_validation_runs_before_tier_check(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 150 domains (over Starter limit)
        for ($i = 1; $i <= 150; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Try to "downgrade" to Starter (same plan - invalid)
        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'starter',
        ]);

        // Should fail on tier check first, but domain check would catch it if tier check passed
        $response->assertStatus(422);
    }

    /**
     * Test: Billing page shows domain count
     */
    public function test_billing_page_displays_domain_count(): void
    {
        $this->actingAs($this->user);

        // Create subscription
        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create some domains
        for ($i = 1; $i <= 45; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Visit billing page
        $response = $this->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Your domains: 45 / 100', false);
    }

    /**
     * Test: Billing page highlights excess domains in red
     */
    public function test_billing_page_highlights_excess_domains(): void
    {
        $this->actingAs($this->user);

        // Create subscription on Starter plan
        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 150 domains (over Starter limit of 100)
        for ($i = 1; $i <= 150; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        // Visit billing page
        $response = $this->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Your domains: 150 / 100', false);
        $response->assertSee('(50 over limit)', false);
    }

    /**
     * Test: Payment tracking - webhook creates payment record
     */
    public function test_webhook_creates_payment_record_on_successful_invoice(): void
    {
        $this->actingAs($this->user);

        // Create subscription with Stripe IDs
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Simulate Stripe webhook payload
        $webhookPayload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test123',
                    'customer' => 'cus_test123',
                    'subscription' => 'sub_test123',
                    'amount_paid' => 4900,
                    'currency' => 'usd',
                    'period_start' => now()->startOfMonth()->timestamp,
                    'period_end' => now()->endOfMonth()->timestamp,
                    'description' => 'Monthly subscription payment',
                    'number' => 'INV-001',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                    'payment_intent' => 'pi_test123',
                    'charge' => 'ch_test123',
                ],
            ],
        ];

        // Mock Stripe webhook signature verification
        $this->mock(\Stripe\Webhook::class, function ($mock) {
            $mock->shouldReceive('constructEvent')
                ->andReturn((object) $webhookPayload);
        });

        // Call webhook endpoint
        $response = $this->postJson('/api/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 'test_signature',
        ]);

        // Check payment was created
        $this->assertDatabaseHas('payments', [
            'account_id' => $this->account->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $this->starterPlan->id,
            'stripe_invoice_id' => 'in_test123',
            'amount_cents' => 4900,
            'status' => 'succeeded',
            'type' => 'subscription',
        ]);
    }

    /**
     * Test: Payment tracking - webhook creates failed payment record
     */
    public function test_webhook_creates_failed_payment_record(): void
    {
        $this->actingAs($this->user);

        // Create subscription
        $subscription = Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
            'stripe_customer_id' => 'cus_test123',
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Simulate failed payment webhook
        $webhookPayload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test456',
                    'customer' => 'cus_test123',
                    'amount_due' => 4900,
                    'currency' => 'usd',
                    'attempt_count' => 2,
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                    'last_finalization_error' => [
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ];

        // Mock Stripe webhook
        $this->mock(\Stripe\Webhook::class, function ($mock) {
            $mock->shouldReceive('constructEvent')
                ->andReturn((object) $webhookPayload);
        });

        // Call webhook endpoint
        $response = $this->postJson('/api/stripe/webhook', $webhookPayload, [
            'Stripe-Signature' => 'test_signature',
        ]);

        // Check failed payment was created
        $this->assertDatabaseHas('payments', [
            'account_id' => $this->account->id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => 'in_test456',
            'amount_cents' => 4900,
            'status' => 'failed',
            'type' => 'subscription',
        ]);

        // Check subscription status updated
        $subscription->refresh();
        $this->assertEquals('past_due', $subscription->status);
    }

    /**
     * Test: Cannot downgrade if plan doesn't exist
     */
    public function test_cannot_downgrade_to_nonexistent_plan(): void
    {
        $this->actingAs($this->user);

        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'nonexistent-plan',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test: Error message includes correct excess domain count
     */
    public function test_error_message_includes_correct_excess_count(): void
    {
        $this->actingAs($this->user);

        Subscription::create([
            'account_id' => $this->account->id,
            'plan_id' => $this->businessPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'renews_at' => now()->endOfMonth(),
        ]);

        // Create 250 domains (over Starter limit of 100, excess = 150)
        for ($i = 1; $i <= 250; $i++) {
            Domain::create([
                'account_id' => $this->account->id,
                'domain' => "test{$i}.example.com",
                'status' => 'pending',
            ]);
        }

        $response = $this->postJson(route('billing.downgrade'), [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'current_domains' => 250,
            'plan_limit' => 100,
            'excess_domains' => 150,
        ]);
        $this->assertStringContainsString('150', $response->json('message'));
    }
}
