<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Support\AccountResolver;
use App\Billing\Services\PlanRulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestDomains extends Command
{
    protected $signature = 'domains:ingest';

    protected $description = 'Ingest domains from source API and upsert into the domains table';

    public function handle(PlanRulesService $planRules): int
    {
        $account = AccountResolver::current();
        $source = config('domain.source_url');

        $this->info("Fetching domains from {$source}");

        try {
            $response = Http::timeout(10)->get($source);
            if (!$response->ok()) {
                $this->error('Failed to fetch domains: ' . $response->status());
                return self::FAILURE;
            }
            $data = $response->json();
        } catch (\Throwable $e) {
            $this->error('Error fetching domains: ' . $e->getMessage());
            return self::FAILURE;
        }

        $domains = $data['domains'] ?? [];
        $count = 0;

        $current = Domain::where('account_id', $account->id)->count();
        $limit = $planRules->maxDomains($account);

        foreach ($domains as $row) {
            $domainName = $row['domain'] ?? null;
            if (!$domainName) {
                continue;
            }
            if ($current >= $limit) {
                $this->warn('Reached plan domain limit; stopping ingest.');
                break;
            }
            Domain::updateOrCreate(
                ['domain' => $domainName, 'account_id' => $account->id],
                ['status' => 'pending', 'ssl_valid' => null, 'last_check_error' => null]
            );
            $count++;
            $current++;
        }

        $this->info("Ingested/updated {$count} domains.");
        return self::SUCCESS;
    }
}

