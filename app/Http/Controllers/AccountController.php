<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Billing\Services\PlanRulesService;
use App\Models\Domain;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request, PlanRulesService $plans)
    {
        $user = $request->user();
        $account = AccountResolver::current();

        $subscription = $account->activeSubscription()->with('plan')->first();
        $plan = $subscription?->plan;

        $domainCount = Domain::where('account_id', $account->id)->count();
        $maxDomains = $plans->maxDomains($account);
        $minInterval = $plans->checkIntervalMinutes($account);

        $allPlans = Plan::query()
            ->where('active', true)
            ->orderBy('price_cents')
            ->get();

        $currentSlug = $plan?->slug ?? 'free';
        $currentIndex = $allPlans->search(fn (Plan $p) => $p->slug === $currentSlug);
        $nextPlan = ($currentIndex !== false && isset($allPlans[$currentIndex + 1]))
            ? $allPlans[$currentIndex + 1]
            : null;

        return view('account.show', [
            'user' => $user,
            'account' => $account,
            'plan' => $plan,
            'allPlans' => $allPlans,
            'nextPlan' => $nextPlan,
            'domainCount' => $domainCount,
            'maxDomains' => $maxDomains,
            'minInterval' => $minInterval,
        ]);
    }

    public function upgrade(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', 'string'],
        ]);

        $account = AccountResolver::current();

        $plan = Plan::where('slug', $data['plan'])->where('active', true)->first();
        if (!$plan) {
            return redirect()->route('account.show')->withErrors(['plan' => 'Plan not found.']);
        }

        // Only allow upgrading to a higher priced plan.
        $current = $account->activeSubscription()->with('plan')->first()?->plan;
        $currentPrice = $current?->price_cents ?? 0;
        if ($plan->price_cents <= $currentPrice) {
            return redirect()->route('account.show')->withErrors(['plan' => 'Invalid upgrade selection.']);
        }

        Subscription::updateOrCreate(
            ['account_id' => $account->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]
        );

        return redirect()->route('account.show')->with('success', "Plan upgraded to {$plan->name}.");
    }
}


