<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Billing\Plan;
use App\Billing\Services\StripeService;
use App\Billing\Subscription;
use App\Models\Domain;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly StripeService $stripeService
    ) {
    }

    /**
     * Show billing page
     */
    public function index()
    {
        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->first();
        // Only show the 4 main plans: Free, Starter, Business, Enterprise
        $plans = Plan::where('active', true)
            ->whereIn('slug', ['free', 'starter', 'business', 'enterprise'])
            ->orderBy('price_cents')
            ->get();
        $currentDomainCount = Domain::where('account_id', $account->id)->count();

        // Calculate prorated amount for current plan if payment method is missing
        $proratedAmount = null;
        $daysRemaining = null;
        if ($subscription && $subscription->plan && $subscription->plan->price_cents > 0 && !$subscription->stripe_payment_method_id) {
            $now = now();
            $endOfMonth = $now->copy()->endOfMonth();
            $daysRemaining = $now->diffInDays($endOfMonth) + 1; // Include today
            $daysInMonth = $now->daysInMonth;
            
            // Prorated amount based on remaining days
            $proratedAmount = (int) round(($subscription->plan->price_cents * $daysRemaining) / $daysInMonth);
        }

        return view('billing.index', [
            'account' => $account,
            'subscription' => $subscription,
            'currentPlan' => $subscription?->plan,
            'nextPlan' => $subscription?->nextPlan,
            'plans' => $plans,
            'currentDomainCount' => $currentDomainCount,
            'proratedAmount' => $proratedAmount,
            'daysRemaining' => $daysRemaining,
        ]);
    }

    /**
     * Add or update payment method
     */
    public function updatePaymentMethod(Request $request)
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'string'],
        ]);

        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->first();

        try {
            // Check if this is a paid plan without payment method (needs subscription creation)
            $needsSubscription = $subscription && 
                                $subscription->plan && 
                                $subscription->plan->price_cents > 0 && 
                                !$subscription->stripe_payment_method_id;

            if ($needsSubscription) {
                // Create Stripe customer and subscribe to paid plan
                $customerId = $this->stripeService->createCustomer(
                    $account,
                    auth()->user()->email,
                    $data['payment_method_id']
                );

                // Subscribe to the plan with payment
                $subscription = $this->stripeService->subscribeToPlan(
                    $account,
                    $subscription->plan,
                    $customerId,
                    $data['payment_method_id']
                );

                return response()->json([
                    'message' => 'Payment method added and subscription activated successfully!',
                ]);
            } else {
                // Just update payment method for existing subscription
                if ($subscription && $subscription->stripe_customer_id) {
                    // Update existing customer's payment method
                    $this->stripeService->attachPaymentMethod(
                        $subscription->stripe_customer_id,
                        $data['payment_method_id']
                    );

                    $subscription->update([
                        'stripe_payment_method_id' => $data['payment_method_id'],
                    ]);
                } else {
                    // Create new customer
                    $customerId = $this->stripeService->createCustomer(
                        $account,
                        auth()->user()->email,
                        $data['payment_method_id']
                    );

                    if ($subscription) {
                        $subscription->update([
                            'stripe_customer_id' => $customerId,
                            'stripe_payment_method_id' => $data['payment_method_id'],
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'Payment method updated successfully',
                ]);
            }
        } catch (ApiErrorException $e) {
            Log::error('Payment method update failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update payment method: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Subscribe to a paid plan
     */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'plan_slug' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
        ]);

        $account = AccountResolver::current();
        $plan = Plan::where('slug', $data['plan_slug'])->where('active', true)->firstOrFail();

        // Validate it's not the free plan
        if ($plan->price_cents === 0) {
            return response()->json([
                'message' => 'Cannot subscribe to free plan with payment',
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

            // Subscribe to plan
            $subscription = $this->stripeService->subscribeToPlan(
                $account,
                $plan,
                $customerId,
                $data['payment_method_id']
            );

            return response()->json([
                'message' => 'Successfully subscribed to ' . $plan->name,
                'subscription' => $subscription->fresh()->load('plan'),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Subscription creation failed', [
                'account_id' => $account->id,
                'plan_slug' => $data['plan_slug'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upgrade to a higher tier plan (immediate with prorated payment)
     */
    public function upgrade(Request $request)
    {
        $data = $request->validate([
            'plan_slug' => ['required', 'string'],
        ]);

        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->firstOrFail();
        $newPlan = Plan::where('slug', $data['plan_slug'])->where('active', true)->firstOrFail();
        $currentPlan = $subscription->plan;

        // Validate it's an upgrade
        if ($newPlan->price_cents <= $currentPlan->price_cents) {
            return response()->json([
                'message' => 'New plan must be a higher tier than current plan',
            ], 422);
        }

        // Ensure payment method exists
        if (!$subscription->stripe_payment_method_id) {
            return response()->json([
                'message' => 'Please add a payment method first',
            ], 422);
        }

        try {
            $subscription = $this->stripeService->upgradeSubscription($subscription, $newPlan);

            return response()->json([
                'message' => 'Successfully upgraded to ' . $newPlan->name,
                'subscription' => $subscription->load('plan'),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Upgrade failed', [
                'account_id' => $account->id,
                'current_plan' => $currentPlan->slug,
                'new_plan' => $data['plan_slug'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Upgrade payment failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Downgrade to a lower tier plan (effective next billing cycle)
     */
    public function downgrade(Request $request)
    {
        $data = $request->validate([
            'plan_slug' => ['required', 'string'],
        ]);

        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->firstOrFail();
        $newPlan = Plan::where('slug', $data['plan_slug'])->where('active', true)->firstOrFail();
        $currentPlan = $subscription->plan;

        // Validate it's a downgrade
        if ($newPlan->price_cents >= $currentPlan->price_cents) {
            return response()->json([
                'message' => 'New plan must be a lower tier than current plan',
            ], 422);
        }

        // Check domain count before allowing downgrade
        $currentDomainCount = Domain::where('account_id', $account->id)->count();
        $newPlanLimit = $newPlan->max_domains;

        if ($currentDomainCount > $newPlanLimit) {
            $excessDomains = $currentDomainCount - $newPlanLimit;
            return response()->json([
                'message' => "Cannot downgrade to {$newPlan->name}. You currently have {$currentDomainCount} domains, but this plan only allows {$newPlanLimit}. Please delete {$excessDomains} domain(s) first.",
                'current_domains' => $currentDomainCount,
                'plan_limit' => $newPlanLimit,
                'excess_domains' => $excessDomains,
            ], 422);
        }

        try {
            $subscription = $this->stripeService->scheduleDowngrade($subscription, $newPlan);

            return response()->json([
                'message' => 'Downgrade scheduled. Your plan will change to ' . $newPlan->name . ' on ' . $subscription->renews_at->format('M d, Y'),
                'subscription' => $subscription->load('plan', 'nextPlan'),
            ]);
        } catch (\Exception $e) {
            Log::error('Downgrade scheduling failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to schedule downgrade: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel subscription (downgrade to free at end of period)
     */
    public function cancel()
    {
        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->firstOrFail();

        // Check domain count before allowing cancellation
        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            $currentDomainCount = Domain::where('account_id', $account->id)->count();
            $freePlanLimit = $freePlan->max_domains; // 20

            if ($currentDomainCount > $freePlanLimit) {
                $excessDomains = $currentDomainCount - $freePlanLimit;
                return response()->json([
                    'message' => "Cannot cancel subscription. You currently have {$currentDomainCount} domains, but the Free plan only allows {$freePlanLimit}. Please delete {$excessDomains} domain(s) first before canceling.",
                    'current_domains' => $currentDomainCount,
                    'plan_limit' => $freePlanLimit,
                    'excess_domains' => $excessDomains,
                ], 422);
            }
        }

        try {
            $subscription = $this->stripeService->cancelSubscription($subscription);

            return response()->json([
                'message' => 'Subscription canceled. You will be downgraded to Free plan on ' . $subscription->renews_at->format('M d, Y'),
                'subscription' => $subscription->load('plan', 'nextPlan'),
            ]);
        } catch (\Exception $e) {
            Log::error('Cancellation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Select a plan during beta (no payment required)
     */
    public function selectBetaPlan(Request $request)
    {
        if (!config('app.beta_mode')) {
            return response()->json(['message' => 'Beta plan selection is not available.'], 403);
        }

        $data = $request->validate([
            'plan_slug' => ['required', 'string', 'in:free,starter,business,enterprise'],
        ]);

        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->firstOrFail();
        $newPlan = Plan::where('slug', $data['plan_slug'])->where('active', true)->firstOrFail();

        $currentDomainCount = Domain::where('account_id', $account->id)->count();
        if ($currentDomainCount > $newPlan->max_domains) {
            return response()->json([
                'message' => "You have {$currentDomainCount} domains. The {$newPlan->name} plan allows up to {$newPlan->max_domains}. Please remove " . ($currentDomainCount - $newPlan->max_domains) . ' domain(s) first.',
            ], 422);
        }

        $subscription->update([
            'plan_id' => $newPlan->id,
            'status' => 'active',
            'next_plan_id' => null,
        ]);

        return response()->json([
            'message' => 'Plan updated to ' . $newPlan->name . ' (Free Beta)',
            'subscription' => $subscription->fresh()->load('plan'),
        ]);
    }
}
