<?php

namespace App\Http\Controllers;

use App\Domains\DomainIncident;
use App\Domains\Services\DomainService;
use App\Jobs\ProcessImportBatchJob;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Services\DomainCheckService;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Billing\Subscription;

class DomainController extends Controller
{
    public function index()
    {
        $account = AccountResolver::current();
        $user = auth()->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');

        $query = Domain::query();
        if (!$isAdmin) {
            $query->where('account_id', $account->id);
        }

        $domains = $query
            ->orderByRaw("CASE 
                WHEN status = 'down' THEN 0 
                WHEN status = 'ok' THEN 1 
                WHEN status = 'pending' THEN 2 
                ELSE 3 END")
            ->orderByDesc('id')
            ->paginate(25);

        $statsQuery = Domain::query();
        if (!$isAdmin) {
            $statsQuery->where('account_id', $account->id);
        }
        $total = (clone $statsQuery)->count();
        $up = (clone $statsQuery)->where('status', 'ok')->count();
        $down = (clone $statsQuery)->where('status', 'down')->count();
        $pending = (clone $statsQuery)->where('status', 'pending')->count();

        // Get current plan for expiration checking (paid plans only)
        $subscription = $account->activeSubscription()->with('plan')->first();
        $currentPlan = $subscription?->plan;
        $isPaidPlan = $currentPlan && $currentPlan->price_cents > 0;

        return view('domains.index', compact('domains', 'total', 'up', 'down', 'pending', 'currentPlan', 'isPaidPlan'));
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
        $user = request()->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        if ($domain->account_id && $domain->account_id !== $account->id && !$isAdmin) {
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
        $user = request()->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        if ($domain->account_id && $domain->account_id !== $account->id && !$isAdmin) {
            abort(404);
        }

        // Run the same logic as the queued job, but synchronously.
        $job = new CheckDomainJob($domain->id);
        $expirationService = app(\App\Services\DomainExpirationService::class);
        $job->handle($checker, $expirationService);

        $domain->refresh();

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

        $domains = $service->parseDomainsInput($data['json']);
        if (empty($domains)) {
            return back()->withErrors(['json' => 'Please provide at least one domain (separate by space, comma, or new line).']);
        }

        // For large payloads, enqueue background processing
        $created = 0;
        if (count($domains) > 200) {
            $batch = $service->createImportBatch('json', $domains);
            ProcessImportBatchJob::dispatch($batch);
        } else {
            $created = $service->importJsonPayload($domains);
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
        $user = $request->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        if ($domain->account_id && $domain->account_id !== $account->id && !$isAdmin) {
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
        $user = request()->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        if ($domain->account_id && $domain->account_id !== $account->id && !$isAdmin) {
            abort(404);
        }

        $domain->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Domain deleted.']);
        }

        return redirect()->route('domains.index')->with('success', 'Domain deleted.');
    }

    /**
     * Show domain check log (last 30 days of incidents + current status).
     */
    public function showLog(Domain $domain)
    {
        $account = AccountResolver::current();
        $user = request()->user();
        $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
        if ($domain->account_id && $domain->account_id !== $account->id && !$isAdmin) {
            abort(403);
        }

        $incidents = DomainIncident::where('domain_id', $domain->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('opened_at')
            ->paginate(50);

        return view('domains.log', [
            'domain' => $domain,
            'incidents' => $incidents,
        ]);
    }
}

