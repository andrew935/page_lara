<?php

namespace App\Console\Commands;

use App\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestDomains extends Command
{
    protected $signature = 'domains:ingest';

    protected $description = 'Ingest domains from source API and upsert into the domains table';

    public function handle(): int
    {
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

        foreach ($domains as $row) {
            $domainName = $row['domain'] ?? null;
            if (!$domainName) {
                continue;
            }
            Domain::updateOrCreate(
                ['domain' => $domainName],
                ['status' => 'pending', 'ssl_valid' => null, 'last_check_error' => null]
            );
            $count++;
        }

        $this->info("Ingested/updated {$count} domains.");
        return self::SUCCESS;
    }
}

