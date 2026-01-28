<?php

declare(strict_types=1);

namespace App\Billing\Services;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Identity\Account;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Stripe\Price;
use Stripe\Product;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe customer for an account
     */
    public function createCustomer(Account $account, string $email, ?string $paymentMethodId = null): string
    {
        try {
            $customerData = [
                'email' => $email,
                'name' => $account->name,
                'description' => 'Account: ' . $account->name . ' - ' . config('app.name'),
                'metadata' => [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'site' => config('app.name'),
                    'site_url' => config('app.url'),
                    'environment' => config('app.env'),
                    'created_at' => now()->toDateTimeString(),
                ],
            ];

            if ($paymentMethodId) {
                $customerData['payment_method'] = $paymentMethodId;
                $customerData['invoice_settings'] = [
                    'default_payment_method' => $paymentMethodId,
                ];
            }

            $customer = Customer::create($customerData);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer creation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Attach a payment method to a customer
     */
    public function attachPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            // Set as default payment method
            Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment method attachment failed', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate prorated amount based on days remaining in month
     */
    public function calculateProration(Carbon $startDate, Plan $plan): int
    {
        if ($plan->price_cents === 0) {
            return 0;
        }

        $endOfMonth = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;
        $daysRemaining = $startDate->diffInDays($endOfMonth) + 1; // Include start day

        $proratedAmount = (int) round(($plan->price_cents * $daysRemaining) / $daysInMonth);

        return $proratedAmount;
    }

    /**
     * Create a payment intent and charge the customer
     */
    public function createPaymentIntent(
        string $customerId,
        int $amountCents,
        string $currency = 'usd',
        array $metadata = []
    ): PaymentIntent {
        try {
            return PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => $currency,
                'customer' => $customerId,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => $metadata,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'customer_id' => $customerId,
                'amount' => $amountCents,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create Stripe Price for a plan
     */
    protected function getOrCreatePrice(Plan $plan): string
    {
        // Check if we already have a price_id stored (optional: add stripe_price_id to plans table)
        // For now, create a new price each time or use a cached approach
        
        try {
            // Create a product if needed
            $product = Product::create([
                'name' => $plan->name . ' - ' . config('app.name'),
                'description' => 'Domain monitoring service - ' . $plan->name . ' plan from ' . config('app.name'),
                'statement_descriptor' => config('services.stripe.statement_descriptor'),
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_slug' => $plan->slug,
                    'plan_name' => $plan->name,
                    'site' => config('app.name'),
                    'site_url' => config('app.url'),
                    'max_domains' => $plan->max_domains,
                    'check_interval_minutes' => $plan->check_interval_minutes,
                ],
            ]);

            // Create a price for this product
            $price = Price::create([
                'product' => $product->id,
                'unit_amount' => $plan->price_cents,
                'currency' => 'usd',
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => 1,
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
            ]);

            return $price->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe price creation failed', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Subscribe an account to a plan (initial subscription with automatic billing)
     */
    public function subscribeToPlan(
        Account $account,
        Plan $plan,
        string $customerId,
        string $paymentMethodId
    ): Subscription {
        $now = now();
        $renewsAt = $now->copy()->endOfMonth();
        $proratedAmount = $this->calculateProration($now, $plan);

        try {
            // Get Stripe Price ID for the plan
            $priceId = $this->getOrCreatePrice($plan);

            // Calculate proration for first payment
            $daysInMonth = $now->daysInMonth;
            $daysRemaining = $now->diffInDays($renewsAt) + 1;
            $prorationMultiplier = $daysRemaining / $daysInMonth;

            // Create Stripe Subscription with automatic billing
            $stripeSubscription = StripeSubscription::create([
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'default_payment_method' => $paymentMethodId,
                'proration_behavior' => 'none', // We handle proration manually for first payment
                'billing_cycle_anchor' => $renewsAt->endOfDay()->timestamp, // Bill on last day of month
                'description' => $plan->name . ' Plan - ' . config('app.name'),
                'metadata' => [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'plan_slug' => $plan->slug,
                    'site' => config('app.name'),
                    'site_url' => config('app.url'),
                    'environment' => config('app.env'),
                    'subscribed_at' => $now->toDateTimeString(),
                    'prorated_amount_cents' => $proratedAmount,
                    'days_remaining_first_month' => $daysRemaining,
                ],
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
                // Automatically charge on renewal
                'collection_method' => 'charge_automatically',
            ]);

            // Create or update local subscription record
            $subscription = Subscription::updateOrCreate(
                ['account_id' => $account->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => $now,
                    'renews_at' => $renewsAt,
                    'stripe_customer_id' => $customerId,
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'stripe_payment_method_id' => $paymentMethodId,
                    'prorated_amount_cents' => $proratedAmount,
                    'last_payment_at' => $now,
                ]
            );

            Log::info('Stripe subscription created with automatic billing', [
                'account_id' => $account->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'next_billing' => $renewsAt->toDateTimeString(),
            ]);

            return $subscription;
        } catch (ApiErrorException $e) {
            Log::error('Stripe subscription creation failed', [
                'account_id' => $account->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upgrade subscription to a higher tier (immediate with prorated payment and automatic billing)
     */
    public function upgradeSubscription(Subscription $subscription, Plan $newPlan): Subscription
    {
        $currentPlan = $subscription->plan;
        $now = now();

        try {
            if ($subscription->stripe_subscription_id) {
                // Get new price ID
                $newPriceId = $this->getOrCreatePrice($newPlan);

                // Update Stripe subscription
                $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                
                // Update the subscription item to new price
                // Stripe will automatically calculate and charge the prorated difference
                StripeSubscription::update($subscription->stripe_subscription_id, [
                    'items' => [
                        [
                            'id' => $stripeSubscription->items->data[0]->id,
                            'price' => $newPriceId,
                        ],
                    ],
                    'proration_behavior' => 'always_invoice', // Automatically charge prorated difference
                    'metadata' => [
                        'account_id' => $subscription->account_id,
                        'account_name' => $subscription->account->name,
                        'plan_id' => $newPlan->id,
                        'plan_name' => $newPlan->name,
                        'plan_slug' => $newPlan->slug,
                        'previous_plan_slug' => $currentPlan->slug,
                        'upgraded_at' => $now->toDateTimeString(),
                        'site' => config('app.name'),
                        'site_url' => config('app.url'),
                    ],
                ]);

                // Update local subscription
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'last_payment_at' => $now,
                ]);

                Log::info('Stripe subscription upgraded with automatic proration', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'old_plan' => $currentPlan->slug,
                    'new_plan' => $newPlan->slug,
                ]);
            } else {
                // Fallback: No Stripe subscription exists, just update plan
                $subscription->update([
                    'plan_id' => $newPlan->id,
                ]);
            }

            return $subscription->fresh();
        } catch (ApiErrorException $e) {
            Log::error('Stripe upgrade failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Schedule downgrade to a lower tier (effective next billing cycle)
     */
    public function scheduleDowngrade(Subscription $subscription, Plan $newPlan): Subscription
    {
        try {
            if ($subscription->stripe_subscription_id && $newPlan->price_cents > 0) {
                // Get new price ID
                $newPriceId = $this->getOrCreatePrice($newPlan);

                // Schedule the change for end of billing period
                $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                
                StripeSubscription::update($subscription->stripe_subscription_id, [
                    'items' => [
                        [
                            'id' => $stripeSubscription->items->data[0]->id,
                            'price' => $newPriceId,
                        ],
                    ],
                    'proration_behavior' => 'none', // Don't charge/refund immediately
                    'billing_cycle_anchor' => 'unchanged', // Keep current billing cycle
                    'metadata' => array_merge(
                        $stripeSubscription->metadata->toArray() ?? [], // Preserve existing metadata
                        [
                            'downgrade_scheduled_at' => now()->toDateTimeString(),
                            'downgrade_to_plan' => $newPlan->slug,
                            'downgrade_effective_date' => $subscription->renews_at->toDateString(),
                        ]
                    ),
                ]);

                Log::info('Stripe subscription downgrade scheduled', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                    'new_plan' => $newPlan->slug,
                ]);
            }

            // Also update local record
            $subscription->update([
                'next_plan_id' => $newPlan->id,
            ]);

            Log::info('Downgrade scheduled locally', [
                'subscription_id' => $subscription->id,
                'current_plan' => $subscription->plan->slug,
                'next_plan' => $newPlan->slug,
                'effective_date' => $subscription->renews_at,
            ]);

            return $subscription->fresh();
        } catch (ApiErrorException $e) {
            Log::error('Downgrade scheduling failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process monthly billing for a subscription
     * NOTE: This is now mostly handled by Stripe's automatic billing.
     * This method is kept for free plans and any manual interventions needed.
     */
    public function processMonthlyBilling(Subscription $subscription): bool
    {
        $plan = $subscription->plan;

        // For free plans, just update the renewal date
        if ($plan->price_cents === 0) {
            $subscription->update([
                'renews_at' => now()->endOfMonth()->addMonth(),
            ]);
            return true;
        }

        // For paid plans with Stripe subscriptions, Stripe handles billing automatically
        // We just need to apply any scheduled downgrades
        if ($subscription->next_plan_id && $subscription->renews_at && $subscription->renews_at->isPast()) {
            $subscription->update([
                'plan_id' => $subscription->next_plan_id,
                'next_plan_id' => null,
                'renews_at' => now()->endOfMonth()->addMonth(),
            ]);

            Log::info('Applied scheduled plan change', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $subscription->plan_id,
            ]);
        }

        return true;
    }

    /**
     * Cancel a subscription (downgrade to free at end of period)
     */
    public function cancelSubscription(Subscription $subscription): Subscription
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            throw new \Exception('Free plan not found');
        }

        try {
            // Cancel Stripe subscription at period end (not immediately)
            if ($subscription->stripe_subscription_id) {
                $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id);
                
                StripeSubscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                    'metadata' => array_merge(
                        $stripeSubscription->metadata->toArray() ?? [],
                        [
                            'canceled_at' => now()->toDateTimeString(),
                            'downgrade_to' => 'free',
                            'canceled_by_user' => auth()->user()->name ?? 'system',
                            'cancellation_reason' => 'user_requested',
                        ]
                    ),
                ]);

                Log::info('Stripe subscription canceled at period end', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $subscription->stripe_subscription_id,
                ]);
            }

            // Update local subscription
            $subscription->update([
                'next_plan_id' => $freePlan->id,
                'canceled_at' => now(),
            ]);

            Log::info('Subscription cancellation scheduled', [
                'subscription_id' => $subscription->id,
                'effective_date' => $subscription->renews_at,
            ]);

            return $subscription->fresh();
        } catch (ApiErrorException $e) {
            Log::error('Subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
