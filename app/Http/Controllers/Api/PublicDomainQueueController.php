<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Services\DomainService;
use App\Http\Controllers\Controller;
use App\Identity\Account;
use Illuminate\Http\JsonResponse;

class PublicDomainQueueController extends Controller
{
    /**
     * GET /api/domains/test-all
     *
     * Public endpoint (protected by cloudflare.webhook middleware) that queues
     * checks for ALL domains across ALL accounts.
     */
    public function testAllDomains(DomainService $domains): JsonResponse
    {
        // Skip if using Cloudflare mode (checks are handled externally)
        if (config('domain.check_mode') === 'cloudflare') {
            return response()->json([
                'skipped' => true,
                'message' => 'Domain checks are handled by Cloudflare Workers.',
            ]);
        }

        $totalQueued = 0;
        $perAccount = [];

        foreach (Account::all() as $account) {
            $queued = $domains->queueAllForAccount($account);
            $totalQueued += $queued;
            $perAccount[] = [
                'account_id' => $account->id,
                'queued' => $queued,
            ];
        }

        return response()->json([
            'queued' => $totalQueued,
            'per_account' => $perAccount,
        ]);
    }

    
}


