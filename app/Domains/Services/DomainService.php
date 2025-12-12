<?php

namespace App\Domains\Services;

use App\Billing\Services\PlanRulesService;
use App\Imports\ImportBatch;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Models\DomainSetting;
use App\Support\AccountResolver;
use Illuminate\Support\Facades\Http;

class DomainService
{
    public function __construct(private PlanRulesService $planRules)
    {
    }

    /**
    * Add domains from a newline-separated string, enforcing plan limits.
    */
    public function addDomainsFromText(string $domainsText): array
    {
        $account = AccountResolver::current();
        $limit = $this->planRules->maxDomains($account);
        $currentCount = Domain::where('account_id', $account->id)->count();

        $lines = preg_split('/\r\n|\r|\n/', $domainsText);
        $created = 0;
        $newDomains = [];

        foreach ($lines as $line) {
            $domain = trim($line);
            if ($domain === '') {
                continue;
            }

            // Enforce max domains for new insertions
            if ($currentCount + $created >= $limit) {
                break;
            }

            $model = Domain::firstOrCreate(
                ['domain' => $domain, 'account_id' => $account->id],
                [
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                ]
            );

            if ($model->wasRecentlyCreated) {
                $newDomains[] = $this->mapDomain($model);
                $created++;
            }
        }

        return [$created, $newDomains];
    }

    public function deleteAll(): void
    {
        $account = AccountResolver::current();
        Domain::where('account_id', $account->id)->delete();
    }

    public function queueDomain(Domain $domain): void
    {
        $this->markQueued($domain);
        CheckDomainJob::dispatch($domain->id);
    }

    public function queueAll(): array
    {
        $account = AccountResolver::current();
        $updated = [];

        Domain::where('account_id', $account->id)
            ->orderBy('last_checked_at')
            ->chunkById(200, function ($domains) use (&$updated) {
                foreach ($domains as $domain) {
                    $this->markQueued($domain);
                    $updated[] = $this->mapDomain($domain->fresh());
                    CheckDomainJob::dispatch($domain->id);
                }
            });

        return $updated;
    }

    public function importJsonPayload(array $domains): int
    {
        $account = AccountResolver::current();
        $limit = $this->planRules->maxDomains($account);
        $current = Domain::where('account_id', $account->id)->count();
        $created = 0;

        foreach ($domains as $item) {
            if (!is_string($item) || trim($item) === '') {
                continue;
            }
            if ($current + $created >= $limit) {
                break;
            }
            Domain::firstOrCreate(
                ['domain' => trim($item), 'account_id' => $account->id],
                [
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                ]
            );
            $created++;
        }

        return $created;
    }

    public function importLatestFromFeed(): int
    {
        $account = AccountResolver::current();
        $settings = DomainSetting::where('account_id', $account->id)->first();
        $url = $settings && $settings->feed_url ? $settings->feed_url : config('domain.source_url', '');
        if (!$url) {
            return 0;
        }

        $response = Http::withoutVerifying()->timeout(15)->get($url);
        if (!$response->ok()) {
            return 0;
        }

        $payload = $response->json();
        $list = $payload['domains'] ?? [];
        $created = 0;
        $limit = $this->planRules->maxDomains($account);
        $current = Domain::where('account_id', $account->id)->count();

        foreach ($list as $item) {
            $domainName = $item['domain'] ?? null;
            $campaign = $item['campaign'] ?? null;
            if (!$domainName || !is_string($domainName)) {
                continue;
            }
            if ($current + $created >= $limit) {
                break;
            }
            $domainName = trim($domainName);
            $model = Domain::firstOrCreate(
                ['domain' => $domainName, 'account_id' => $account->id],
                [
                    'campaign' => $campaign,
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                ]
            );

            if (!$model->wasRecentlyCreated && $campaign && $model->campaign !== $campaign) {
                $model->update(['campaign' => $campaign]);
            }
            $created++;
        }

        return $created;
    }

    public function createImportBatch(string $source, array $payload): ImportBatch
    {
        $account = AccountResolver::current();
        $batch = ImportBatch::create([
            'account_id' => $account->id,
            'source' => $source,
            'status' => 'pending',
            'payload' => $payload,
            'total' => is_countable($payload) ? count($payload) : 0,
        ]);

        return $batch;
    }

    public function mapDomain(Domain $domain): array
    {
        return [
            'id' => $domain->id,
            'domain' => $domain->domain,
            'campaign' => $domain->campaign,
            'status' => $domain->status,
            'ssl_valid' => $domain->ssl_valid,
            'last_checked_at' => $domain->last_checked_at ? $domain->last_checked_at->toDateTimeString() : null,
            'status_since' => $domain->status_since ? $domain->status_since->toDateTimeString() : null,
            'last_up_at' => $domain->last_up_at ? $domain->last_up_at->toDateTimeString() : null,
            'last_down_at' => $domain->last_down_at ? $domain->last_down_at->toDateTimeString() : null,
            'error' => $domain->last_check_error,
        ];
    }

    protected function markQueued(Domain $domain): void
    {
        $payload = [
            'status' => 'pending',
            'last_check_error' => 'Queued for check',
        ];

        if ($domain->status !== 'pending') {
            $payload['status_since'] = now();
        }

        $domain->update($payload);
    }
}


