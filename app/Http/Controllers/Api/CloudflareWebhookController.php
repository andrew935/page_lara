<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\DomainIncident;
use App\Http\Controllers\Controller;
use App\Jobs\NotifyDomainDownJob;
use App\Jobs\NotifyDomainUpJob;
use App\Jobs\SendAlertJob;
use App\Models\Domain;
use App\Models\DomainSetting;
use App\Notifications\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API endpoints for Cloudflare Workers to check domains.
 * 
 * These endpoints are authenticated via Bearer token (CLOUDFLARE_WEBHOOK_SECRET).
 * Cloudflare Workers call these endpoints to:
 * 1. Get domains that are due for checking
 * 2. Report check results back to Laravel
 */
class CloudflareWebhookController extends Controller
{
    /**
     * GET /api/cf/domains/due
     * 
     * Returns domains that are due for checking (across all accounts).
     * Respects each account's check_interval_minutes setting.
     * Cloudflare Worker calls this every 20 minutes to get domains to check.
     * 
     * Query params:
     * - limit: Max domains to return (default 500)
     */
    public function due(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 500);
        $limit = min($limit, 1000); // Cap at 1000 per request

        // Get all account settings indexed by account_id
        $accountSettings = DomainSetting::all()->keyBy('account_id');
        $defaultInterval = 60; // Default to 60 minutes if no setting exists

        // Get all domains and filter by each account's check interval
        $allDomains = Domain::query()
            ->orderByRaw('last_checked_at is null desc') // Prioritize never-checked
            ->orderBy('last_checked_at') // Then oldest first
            ->get(['id', 'domain', 'campaign', 'account_id', 'status', 'last_checked_at']);

        $dueDomains = $allDomains->filter(function ($domain) use ($accountSettings, $defaultInterval) {
            // Never checked - always due
            if ($domain->last_checked_at === null) {
                return true;
            }

            // Get this account's check interval
            $setting = $accountSettings->get($domain->account_id);
            $intervalMinutes = $setting ? (int) $setting->check_interval_minutes : $defaultInterval;

            // Check if domain is due based on account's interval
            $cutoff = now()->subMinutes($intervalMinutes);
            return $domain->last_checked_at <= $cutoff;
        })->take($limit);

        Log::info("Cloudflare: Returning {$dueDomains->count()} domains due for checking");

        return response()->json([
            'count' => $dueDomains->count(),
            'domains' => $dueDomains->map(fn($d) => [
                'id' => $d->id,
                'domain' => $d->domain,
                'campaign' => $d->campaign,
                'account_id' => $d->account_id,
            ])->values(),
        ]);
    }

    /**
     * POST /api/cf/domains/result
     * 
     * Receives check results from Cloudflare Worker.
     * Updates domain status and triggers notifications if needed.
     * 
     * Body (JSON):
     * - id: Domain ID
     * - status: 'ok', 'down', or 'error'
     * - ssl_valid: boolean
     * - error: string|null
     * - checked_at: ISO timestamp
     */
    public function result(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:ok,down,error'],
            'ssl_valid' => ['nullable', 'boolean'],
            'error' => ['nullable', 'string', 'max:1000'],
            'checked_at' => ['nullable', 'string'],
        ]);

        $domain = Domain::find($data['id']);
        if (!$domain) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        $oldStatus = $domain->status;

        // Build update payload
        $payload = [
            'status' => $data['status'],
            'ssl_valid' => $data['ssl_valid'] ?? null,
            'last_checked_at' => $data['checked_at'] ? now()->parse($data['checked_at']) : now(),
            'last_check_error' => $data['error'],
        ];

        // Update lastcheck history (1 = up, 0 = down)
        $history = is_array($domain->lastcheck) ? $domain->lastcheck : [];
        $history[] = $data['status'] === 'ok' ? 1 : 0;
        $payload['lastcheck'] = array_slice($history, -24);

        // Handle status transitions
        if ($oldStatus !== $data['status']) {
            $payload['status_since'] = now();

            if ($data['status'] === 'ok') {
                $payload['last_up_at'] = now();
                $payload['down_notified_at'] = null; // Reset for next down
            } elseif ($data['status'] === 'down') {
                $payload['last_down_at'] = now();
                $payload['up_notified_at'] = null; // Reset for next recovery
            }
        }

        $domain->update($payload);
        $domain->refresh();

        // Schedule delayed DOWN notification (3 minutes)
        if ($oldStatus !== 'down' && $domain->status === 'down') {
            NotifyDomainDownJob::dispatch($domain->id)->delay(now()->addMinutes(3));
            $this->openIncident($domain, $oldStatus, $domain->status, $domain->last_check_error);
        }

        // Schedule delayed UP notification (5 minutes for stability)
        if ($oldStatus === 'down' && $domain->status === 'ok') {
            NotifyDomainUpJob::dispatch($domain->id)->delay(now()->addMinutes(5));
            $this->closeIncident($domain);
        }

        // Immediate notification if down for 3+ minutes and not yet notified
        if (
            $domain->status === 'down'
            && !$domain->down_notified_at
            && $domain->status_since
            && $domain->status_since->lte(now()->subMinutes(3))
        ) {
            $minutes = $domain->status_since->diffInMinutes(now());
            $this->notifyDown($domain, "Domain {$domain->domain} is DOWN for {$minutes} minute(s)");
            $domain->update(['down_notified_at' => now()]);
        }

        Log::info("Cloudflare: Domain {$domain->domain} checked - status: {$domain->status}");

        return response()->json([
            'success' => true,
            'domain' => $domain->domain,
            'status' => $domain->status,
        ]);
    }

    /**
     * POST /api/cf/domains/results (batch)
     * 
     * Receives multiple check results at once from Cloudflare Worker.
     * More efficient for processing many domains.
     */
    public function resultsBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'results' => ['required', 'array', 'max:100'],
            'results.*.id' => ['required', 'integer'],
            'results.*.status' => ['required', 'string', 'in:ok,down,error'],
            'results.*.ssl_valid' => ['nullable', 'boolean'],
            'results.*.error' => ['nullable', 'string', 'max:1000'],
            'results.*.checked_at' => ['nullable', 'string'],
        ]);

        $processed = 0;
        $errors = [];

        foreach ($data['results'] as $result) {
            $domain = Domain::find($result['id']);
            if (!$domain) {
                $errors[] = ['id' => $result['id'], 'error' => 'Not found'];
                continue;
            }

            $this->processSingleResult($domain, $result);
            $processed++;
        }

        Log::info("Cloudflare: Batch processed {$processed} domains");

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
        ]);
    }

    /**
     * Process a single domain result (shared logic).
     */
    protected function processSingleResult(Domain $domain, array $data): void
    {
        $oldStatus = $domain->status;

        $payload = [
            'status' => $data['status'],
            'ssl_valid' => $data['ssl_valid'] ?? null,
            'last_checked_at' => isset($data['checked_at']) ? now()->parse($data['checked_at']) : now(),
            'last_check_error' => $data['error'] ?? null,
        ];

        $history = is_array($domain->lastcheck) ? $domain->lastcheck : [];
        $history[] = $data['status'] === 'ok' ? 1 : 0;
        $payload['lastcheck'] = array_slice($history, -24);

        if ($oldStatus !== $data['status']) {
            $payload['status_since'] = now();

            if ($data['status'] === 'ok') {
                $payload['last_up_at'] = now();
                $payload['down_notified_at'] = null;
            } elseif ($data['status'] === 'down') {
                $payload['last_down_at'] = now();
                $payload['up_notified_at'] = null;
            }
        }

        $domain->update($payload);
        $domain->refresh();

        if ($oldStatus !== 'down' && $domain->status === 'down') {
            NotifyDomainDownJob::dispatch($domain->id)->delay(now()->addMinutes(3));
            $this->openIncident($domain, $oldStatus, $domain->status, $domain->last_check_error);
        }

        if ($oldStatus === 'down' && $domain->status === 'ok') {
            NotifyDomainUpJob::dispatch($domain->id)->delay(now()->addMinutes(5));
            $this->closeIncident($domain);
        }

        if (
            $domain->status === 'down'
            && !$domain->down_notified_at
            && $domain->status_since
            && $domain->status_since->lte(now()->subMinutes(3))
        ) {
            $minutes = $domain->status_since->diffInMinutes(now());
            $this->notifyDown($domain, "Domain {$domain->domain} is DOWN for {$minutes} minute(s)");
            $domain->update(['down_notified_at' => now()]);
        }
    }

    protected function notifyDown(Domain $domain, string $message): void
    {
        $settings = NotificationSetting::where('account_id', $domain->account_id)->first();
        if (!$settings || !$settings->notify_on_fail) {
            return;
        }

        SendAlertJob::dispatch($domain->account_id, $domain->id, $message);
    }

    protected function openIncident(Domain $domain, ?string $before, string $after, ?string $error): void
    {
        DomainIncident::create([
            'domain_id' => $domain->id,
            'status_before' => $before,
            'status_after' => $after,
            'opened_at' => now(),
            'message' => $error,
        ]);
    }

    protected function closeIncident(Domain $domain): void
    {
        $incident = DomainIncident::where('domain_id', $domain->id)
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->first();

        if ($incident) {
            $incident->update([
                'closed_at' => now(),
                'status_after' => 'ok',
            ]);
        }
    }
}

