<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Jobs\CheckDomainJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::orderByRaw("CASE 
                WHEN status = 'down' THEN 0 
                WHEN status = 'pending' THEN 1 
                WHEN status = 'error' THEN 2 
                ELSE 3 END")
            ->orderBy('domain')
            ->paginate(25);
        $total = Domain::count();
        $up = Domain::where('status', 'ok')->count();
        $down = Domain::where('status', 'down')->count();
        $pending = Domain::where('status', 'pending')->count();

        return view('domains.index', compact('domains', 'total', 'up', 'down', 'pending'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'domains' => ['required', 'string'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', $data['domains']);
        $created = 0;
        $newDomains = [];
        foreach ($lines as $line) {
            $domain = trim($line);
            if ($domain === '') {
                continue;
            }
            $model = Domain::firstOrCreate(
                ['domain' => $domain],
                ['status' => 'pending', 'ssl_valid' => null, 'last_check_error' => null]
            );
            if ($model->wasRecentlyCreated) {
                $newDomains[] = $this->mapDomain($model);
            }
            $created++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$created} domain(s) added (duplicates skipped).",
                'domains' => $newDomains,
            ]);
        }

        return redirect()->route('domains.index')->with('success', "{$created} domain(s) added (duplicates skipped).");
    }

    public function ingest()
    {
        Artisan::call('domains:ingest');
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Domains ingested/refreshed.']);
        }
        return redirect()->route('domains.index')->with('success', 'Domains ingested/refreshed.');
    }

    public function check(Domain $domain)
    {
        $this->queueCheck($domain);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Queued check for {$domain->domain}.",
                'domain' => $this->mapDomain($domain->fresh()),
            ]);
        }

        return redirect()->route('domains.index')->with('success', "Queued check for {$domain->domain}.");
    }

    public function checkAll()
    {
        $updated = [];
        Domain::orderBy('last_checked_at')
            ->chunkById(200, function ($domains) use (&$updated) {
                foreach ($domains as $domain) {
                    $this->queueCheck($domain);
                    $updated[] = $this->mapDomain($domain->fresh());
                }
            });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Queued all domains for check.',
                'domains' => $updated,
            ]);
        }

        return redirect()->route('domains.index')->with('success', 'Queued all domains for check.');
    }

    public function deleteAll()
    {
        Domain::query()->delete();
        if (request()->expectsJson()) {
            return response()->json(['message' => 'All domains deleted.']);
        }
        return redirect()->route('domains.index')->with('success', 'All domains deleted.');
    }

    public function importJson(Request $request)
    {
        $data = $request->validate([
            'json' => ['required', 'string'],
        ]);

        $decoded = json_decode($data['json'], true);
        if (!is_array($decoded)) {
            return back()->withErrors(['json' => 'Invalid JSON array of domains.']);
        }

        $created = 0;
        foreach ($decoded as $d) {
            if (!is_string($d) || trim($d) === '') {
                continue;
            }
            Domain::firstOrCreate(
                ['domain' => trim($d)],
                ['status' => 'pending', 'ssl_valid' => null, 'last_check_error' => null]
            );
            $created++;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$created} domain(s) imported."]);
        }
        return redirect()->route('domains.settings.edit')->with('success', "{$created} domain(s) imported.");
    }

    /**
     * Import from external feed (domain + campaign) at assetscdn.net/api/domains/latest
     */
    public function importLatest()
    {
        $settings = \App\Models\DomainSetting::first();
        $url = $settings && $settings->feed_url ? $settings->feed_url : config('domain.source_url', 'https://assetscdn.net/api/domains/latest333');
        $response = Http::withoutVerifying()->timeout(15)->get($url);

        if (!$response->ok()) {
            return back()->withErrors(['json' => 'Failed to fetch latest domains feed.']);
        }

        $payload = $response->json();
        $list = $payload['domains'] ?? [];
        $created = 0;

        foreach ($list as $item) {
            $domainName = $item['domain'] ?? null;
            $campaign = $item['campaign'] ?? null;
            if (!$domainName || !is_string($domainName)) {
                continue;
            }
            $domainName = trim($domainName);
            if ($domainName === '') {
                continue;
            }
            $model = Domain::firstOrCreate(
                ['domain' => $domainName],
                [
                    'campaign' => $campaign,
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                ]
            );
            // If it existed, update campaign if changed
            if (!$model->wasRecentlyCreated && $campaign && $model->campaign !== $campaign) {
                $model->update(['campaign' => $campaign]);
            }
            $created++;
        }

        return redirect()->route('domains.settings.edit')->with('success', "{$created} domain(s) imported from latest feed.");
    }

    /**
     * Basic update for domain fields (campaign)
     */
    public function update(Request $request, Domain $domain)
    {
        $data = $request->validate([
            'campaign' => ['nullable', 'string', 'max:255'],
        ]);

        $domain->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Domain updated.',
                'domain' => $this->mapDomain($domain->fresh()),
            ]);
        }

        return redirect()->route('domains.index')->with('success', 'Domain updated.');
    }

    /**
     * Delete a single domain.
     */
    public function destroy(Domain $domain)
    {
        $domain->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Domain deleted.']);
        }

        return redirect()->route('domains.index')->with('success', 'Domain deleted.');
    }

    protected function queueCheck(Domain $domain): void
    {
        $payload = [
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ];

        if ($domain->status !== 'pending') {
            $payload['status_since'] = now();
        }

        $domain->update($payload);

        CheckDomainJob::dispatch($domain->id);
    }

    protected function mapDomain(Domain $domain): array
    {
        return [
            'id' => $domain->id,
            'domain' => $domain->domain,
            'campaign' => $domain->campaign,
            'status' => $domain->status,
            'ssl_valid' => $domain->ssl_valid,
            'last_checked_at' => $domain->last_checked_at ? $domain->last_checked_at->diffForHumans() : null,
            'status_since' => $domain->status_since ? $domain->status_since->diffForHumans() : null,
            'last_up_at' => $domain->last_up_at ? $domain->last_up_at->toDateTimeString() : null,
            'last_down_at' => $domain->last_down_at ? $domain->last_down_at->toDateTimeString() : null,
            'error' => $domain->last_check_error,
            // placeholder history; replace with real history if/when available
            'history' => [], 
        ];
    }
}

