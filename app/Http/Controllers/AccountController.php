<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

        return view('account.show', [
            'user' => $user,
            'account' => $account,
            'plan' => $plan,
            'domainCount' => $domainCount,
            'maxDomains' => $maxDomains,
        ]);
    }
}


