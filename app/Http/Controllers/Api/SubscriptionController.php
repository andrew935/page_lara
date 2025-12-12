<?php

namespace App\Http\Controllers\Api;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Http\Controllers\Controller;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', 'string'],
        ]);

        $plan = Plan::where('slug', $data['plan'])->first();
        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        $account = AccountResolver::current();

        $subscription = Subscription::updateOrCreate(
            ['account_id' => $account->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Subscription saved.',
            'subscription' => $subscription->fresh(),
        ]);
    }
}


