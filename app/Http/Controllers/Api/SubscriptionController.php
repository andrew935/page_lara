<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Billing\Services\StripeService;
use App\Http\Controllers\Controller;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly StripeService $stripeService
    ) {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', 'string'],
            'payment_method_id' => ['nullable', 'string'],
        ]);

        $plan = Plan::where('slug', $data['plan'])->where('active', true)->first();
        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        $account = AccountResolver::current();

        // If it's a paid plan, require payment method
        if ($plan->price_cents > 0) {
            if (empty($data['payment_method_id'])) {
                return response()->json([
                    'message' => 'Payment method required for paid plans',
                ], 422);
            }

            try {
                $subscription = $account->activeSubscription()->first();

                // Create or get Stripe customer
                if ($subscription && $subscription->stripe_customer_id) {
                    $customerId = $subscription->stripe_customer_id;
                    // Update payment method
                    $this->stripeService->attachPaymentMethod($customerId, $data['payment_method_id']);
                } else {
                    $customerId = $this->stripeService->createCustomer(
                        $account,
                        auth()->user()->email,
                        $data['payment_method_id']
                    );
                }

                // Subscribe to plan with payment
                $subscription = $this->stripeService->subscribeToPlan(
                    $account,
                    $plan,
                    $customerId,
                    $data['payment_method_id']
                );

                return response()->json([
                    'message' => 'Subscription saved.',
                    'subscription' => $subscription->fresh()->load('plan'),
                ]);
            } catch (\Exception $e) {
                Log::error('Subscription creation failed', [
                    'account_id' => $account->id,
                    'plan_slug' => $data['plan'],
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Payment failed: ' . $e->getMessage(),
                ], 422);
            }
        }

        // Free plan - no payment required
        $subscription = Subscription::updateOrCreate(
            ['account_id' => $account->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'renews_at' => now()->endOfMonth()->addMonth(),
            ]
        );

        return response()->json([
            'message' => 'Subscription saved.',
            'subscription' => $subscription->fresh()->load('plan'),
        ]);
    }
}


