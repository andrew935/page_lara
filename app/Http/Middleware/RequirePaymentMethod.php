<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AccountResolver;
use Closure;
use Illuminate\Http\Request;

class RequirePaymentMethod
{
    /**
     * Handle an incoming request.
     * Redirect to billing if user has paid plan without payment method.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip during beta mode (all plans free, no card required)
        if (config('app.beta_mode')) {
            return $next($request);
        }

        // Skip for guest users
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip if already on billing page
        if ($request->routeIs('billing.*')) {
            return $next($request);
        }

        // Skip for logout and auth routes
        if ($request->routeIs('logout') || $request->routeIs('login') || $request->routeIs('register.*')) {
            return $next($request);
        }

        $account = AccountResolver::current();
        $subscription = $account->activeSubscription()->first();

        // Check if user has paid plan without payment method
        if ($subscription && $subscription->plan) {
            $isPaidPlan = $subscription->plan->price_cents > 0;
            $hasPaymentMethod = !empty($subscription->stripe_payment_method_id);

            if ($isPaidPlan && !$hasPaymentMethod) {
                return redirect()->route('billing.index')
                    ->with('warning', 'Please add a payment method to activate your ' . $subscription->plan->name . ' plan.');
            }
        }

        return $next($request);
    }
}
