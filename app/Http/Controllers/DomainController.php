<?php

namespace App\Http\Controllers;

use App\Domains\Services\DomainService;
use App\Jobs\ProcessImportBatchJob;
use App\Jobs\CheckDomainJob;
use App\Jobs\SendAlertJob;
use App\Models\Domain;
use App\Services\DomainCheckService;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DomainController extends Controller
{
    public function index()
    {
        $account = AccountResolver::current();
        $domains = Domain::where('account_id', $account->id)
            ->orderByRaw("CASE 
                WHEN status = 'down' THEN 0 
                WHEN status = 'ok' THEN 1 
                WHEN status = 'pending' THEN 2 
                ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(25);
        $total = Domain::where('account_id', $account->id)->count();
        $up = Domain::where('account_id', $account->id)->where('status', 'ok')->count();
        $down = Domain::where('account_id', $account->id)->where('status', 'down')->count();
        $pending = Domain::where('account_id', $account->id)->where('status', 'pending')->count();

        return view('domains.index', compact('domains', 'total', 'up', 'down', 'pending'));
    }

    public function store(Request $request, DomainService $service)
    {
        $data = $request->validate([
            'domains' => ['required', 'string'],
        ]);

        [$created, $newDomains] = $service->addDomainsFromText($data['domains']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$created} domain(s) added (duplicates skipped or limit reached).",
                'domains' => $newDomains,
            ]);
        }

        return redirect()->route('domains.index')->with('success', "{$created} domain(s) added (duplicates skipped or limit reached).");
    }

    public function ingest()
    {
        Artisan::call('domains:ingest');
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Domains ingested/refreshed.']);
        }
        return redirect()->route('domains.index')->with('success', 'Domains ingested/refreshed.');
    }

    public function check(Domain $domain, DomainService $service)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        $service->queueDomain($domain);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Queued check for {$domain->domain}.",
                'domain' => $service->mapDomain($domain->fresh()),
            ]);
        }

        return redirect()->route('domains.index')->with('success', "Queued check for {$domain->domain}.");
    }

    public function checkNow(Domain $domain, DomainService $service, DomainCheckService $checker)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        // Run the same logic as the queued job, but synchronously.
        $job = new CheckDomainJob($domain->id);
        $job->handle($checker);

        $domain->refresh();

        // If Telegram is enabled & configured, send a manual-check notification.
        $settings = \App\Notifications\NotificationSetting::where('account_id', $account->id)->first();
        if (
            $settings
            && $settings->notify_on_fail
            && $settings->telegram_api_key
            && $settings->telegram_chat_id
        ) {
            $statusLabel = match ($domain->status) {
                'ok' => 'UP',
                'down' => 'DOWN',
                'pending' => 'PENDING',
                default => strtoupper((string) $domain->status),
            };
            $sslLabel = $domain->ssl_valid === null ? 'UNKNOWN' : ($domain->ssl_valid ? 'VALID' : 'INVALID');
            $checkedAt = $domain->last_checked_at ? $domain->last_checked_at->toDateTimeString() : '—';
            $msg = "Manual check: {$domain->domain}\nStatus: {$statusLabel}\nSSL: {$sslLabel}\nChecked: {$checkedAt}";
            if ($domain->last_check_error) {
                $msg .= "\nError: {$domain->last_check_error}";
            }

            // Send only via Telegram, but keep "enabled" requirement.
            SendAlertJob::dispatch($account->id, $domain->id, $msg, true, ['telegram']);
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Checked {$domain->domain}.",
                'domain' => $service->mapDomain($domain),
            ]);
        }

        return redirect()->route('domains.index')->with('success', "Checked {$domain->domain}.");
    }

    public function checkAll(DomainService $service)
    {
        $updated = $service->queueAll();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Queued all domains for check.',
                'domains' => $updated,
            ]);
        }

        return redirect()->route('domains.index')->with('success', 'Queued all domains for check.');
    }

    public function deleteAll(DomainService $service)
    {
        $service->deleteAll();
        if (request()->expectsJson()) {
            return response()->json(['message' => 'All domains deleted.']);
        }
        return redirect()->route('domains.index')->with('success', 'All domains deleted.');
    }

    public function importJson(Request $request, DomainService $service)
    {
        $data = $request->validate([
            'json' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['json'], true);
        if (!is_array($decoded)) {
            return back()->withErrors(['json' => 'Invalid JSON array of domains.']);
        }

        // For large payloads, enqueue background processing
        $created = 0;
        if (count($decoded) > 200) {
            $batch = $service->createImportBatch('json', $decoded);
            ProcessImportBatchJob::dispatch($batch);
        } else {
            $created = $service->importJsonPayload($decoded);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$created} domain(s) imported."]);
        }
        return redirect()->route('domains.settings.edit')->with('success', "{$created} domain(s) imported.");
    }

    /**
     * Import from external feed (domain + campaign) at assetscdn.net/api/domains/latest
     */
    public function importLatest(DomainService $service)
    {
        $created = $service->importLatestFromFeed();

        return redirect()->route('domains.settings.edit')->with('success', "{$created} domain(s) imported from latest feed.");
    }

    /**
     * Basic update for domain fields (campaign)
     */
    public function update(Request $request, Domain $domain, DomainService $service)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain,' . $domain->id],
            'campaign' => ['nullable', 'string', 'max:255'],
        ]);

        $domain->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Domain updated.',
                'domain' => $service->mapDomain($domain->fresh()),
            ]);
        }

        return redirect()->route('domains.index')->with('success', 'Domain updated.');
    }

    /**
     * Delete a single domain.
     */
    public function destroy(Domain $domain)
    {
        $account = AccountResolver::current();
        if ($domain->account_id && $domain->account_id !== $account->id) {
            abort(404);
        }

        $domain->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Domain deleted.']);
        }

        return redirect()->route('domains.index')->with('success', 'Domain deleted.');
    }
}

