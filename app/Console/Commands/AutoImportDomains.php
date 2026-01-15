<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Services\DomainService;
use App\Models\DomainSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoImportDomains extends Command
{
    protected $signature = 'domains:auto-import';

    protected $description = 'Auto-import domains from feed for accounts with auto_import_feed enabled (deletes existing domains first)';

    public function handle(DomainService $domainService): int
    {
        $settings = DomainSetting::where('auto_import_feed', true)->get();

        if ($settings->isEmpty()) {
            $this->info('No accounts have auto-import enabled.');
            return self::SUCCESS;
        }

        $this->info("Found {$settings->count()} account(s) with auto-import enabled.");

        foreach ($settings as $setting) {
            $accountId = $setting->account_id;
            $feedUrl = $setting->feed_url ?: config('domain.source_url');

            $this->info("Processing account {$accountId}...");
            Log::info("Auto-import: Starting for account {$accountId}, feed: {$feedUrl}");

            // Step 1: Delete all existing domains for this account
            $deleted = $domainService->deleteAllForAccount($accountId);
            $this->info("  Deleted {$deleted} existing domain(s).");
            Log::info("Auto-import: Deleted {$deleted} domains for account {$accountId}");

            // Step 2: Import fresh domains from feed
            $imported = $domainService->importLatestFromFeedForAccount($accountId);
            $this->info("  Imported {$imported} domain(s) from feed.");
            Log::info("Auto-import: Imported {$imported} domains for account {$accountId}");
        }

        $this->info('Auto-import completed.');
        return self::SUCCESS;
    }
}
