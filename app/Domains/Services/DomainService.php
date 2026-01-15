<?php

namespace App\Domains\Services;

use App\Billing\Services\PlanRulesService;
use App\Identity\Account;
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
                    'lastcheck' => [],
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

    /**
     * Delete all domains for a specific account (used by scheduled tasks).
     */
    public function deleteAllForAccount(int $accountId): int
    {
        return Domain::where('account_id', $accountId)->delete();
    }

    public function queueDomain(Domain $domain): void
    {
        $this->markQueued($domain);
        CheckDomainJob::dispatch($domain->id);
    }

    public function queueAll(): array
    {
        $account = AccountResolver::current();
        return $this->queueAllMappedForAccount($account);
    }

    /**
     * Queue checks for all domains in the given account.
     * Returns the number of domains queued.
     */
    public function queueAllForAccount(Account $account): int
    {
        return $this->queueAllInternal($account->id, false)['count'];
    }

    /**
     * Queue checks for all domains in the given account.
     * Returns mapped domains (used by UI/API).
     */
    public function queueAllMappedForAccount(Account $account): array
    {
        return $this->queueAllInternal($account->id, true)['mapped'];
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

            $name = trim($item);
            $existing = Domain::where('account_id', $account->id)->where('domain', $name)->first();
            if ($existing) {
                continue;
            }

            if ($current + $created >= $limit) {
                break;
            }

            $model = Domain::create([
                'account_id' => $account->id,
                'domain' => $name,
                'status' => 'pending',
                'ssl_valid' => null,
                'last_check_error' => null,
                'lastcheck' => [],
            ]);

            if ($model->exists) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Accepts either:
     * - JSON array: ["a.com","b.com"]
     * - Plain list: "a.com b.com" or "a.com, b.com" or newline-separated
     *
     * @return array<int, string>
     */
    public function parseDomainsInput(string $input): array
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return [];
        }

        // Backward compatible: JSON array input
        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(static function ($v): string {
                    return is_string($v) ? trim($v) : '';
                }, $decoded), static fn (string $v): bool => $v !== ''));
            }
        }

        // Plain list input: split on commas and any whitespace
        $parts = preg_split('/[,\s]+/', $trimmed) ?: [];

        return array_values(array_filter(array_map(static fn (string $v): string => trim($v), $parts), static fn (string $v): bool => $v !== ''));
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
            $domainName = trim($domainName);

            $model = Domain::where('account_id', $account->id)->where('domain', $domainName)->first();
            if (!$model) {
                if ($current + $created >= $limit) {
                    break;
                }

                $model = Domain::create([
                    'account_id' => $account->id,
                    'domain' => $domainName,
                    'campaign' => $campaign,
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                    'lastcheck' => [],
                ]);

                $created++;
            }

            if ($campaign && $model->campaign !== $campaign) {
                $model->update(['campaign' => $campaign]);
            }
        }

        return $created;
    }

    /**
     * Import domains from feed for a specific account (used by scheduled tasks).
     * Does not rely on AccountResolver.
     */
    public function importLatestFromFeedForAccount(int $accountId): int
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0;
        }

        $settings = DomainSetting::where('account_id', $accountId)->first();
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
        $current = Domain::where('account_id', $accountId)->count();

        foreach ($list as $item) {
            $domainName = $item['domain'] ?? null;
            $campaign = $item['campaign'] ?? null;
            if (!$domainName || !is_string($domainName)) {
                continue;
            }
            $domainName = trim($domainName);

            $model = Domain::where('account_id', $accountId)->where('domain', $domainName)->first();
            if (!$model) {
                if ($current + $created >= $limit) {
                    break;
                }

                $model = Domain::create([
                    'account_id' => $accountId,
                    'domain' => $domainName,
                    'campaign' => $campaign,
                    'status' => 'pending',
                    'ssl_valid' => null,
                    'last_check_error' => null,
                    'lastcheck' => [],
                ]);

                $created++;
            }

            if ($campaign && $model->campaign !== $campaign) {
                $model->update(['campaign' => $campaign]);
            }
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
            'lastcheck' => $domain->lastcheck ?? [],
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

    /**
     * @return array{count:int, mapped:array<int, array<string, mixed>>}
     */
    private function queueAllInternal(int $accountId, bool $returnMapped): array
    {
        $count = 0;
        $mapped = [];

        Domain::where('account_id', $accountId)
            ->orderBy('last_checked_at')
            ->chunkById(200, function ($domains) use (&$count, &$mapped, $returnMapped) {
                foreach ($domains as $domain) {
                    $this->markQueued($domain);
                    $count++;

                    if ($returnMapped) {
                        $mapped[] = $this->mapDomain($domain->fresh());
                    }

                    CheckDomainJob::dispatch($domain->id);
                }
            });

        return ['count' => $count, 'mapped' => $mapped];
    }
}


