<?php

namespace App\Http\Controllers\Api;

use App\Billing\Plan;
use App\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::where('active', true)
            ->orderBy('price_cents')
            ->get();

        return response()->json($plans);
    }
}


