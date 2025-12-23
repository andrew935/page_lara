<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Services\DomainService;
use App\Identity\Account;
use Illuminate\Console\Command;

class QueueAllDomainsHourly extends Command
{
    protected $signature = 'domains:queue-all-hourly';

    protected $description = 'Queue checks for all domains in all accounts (hourly)';

    public function handle(DomainService $domains): int
    {
        // Skip if using Cloudflare mode
        if (config('domain.check_mode') === 'cloudflare') {
            $this->info('Domain checks are handled by Cloudflare Workers. Skipping server-side checks.');
            return self::SUCCESS;
        }

        $totalQueued = 0;
        foreach (Account::all() as $account) {
            $queued = $domains->queueAllForAccount($account);
            $totalQueued += $queued;

            if ($queued > 0) {
                $this->info("Queued {$queued} domains for account {$account->id}");
            }
        }

        $this->info("Total queued: {$totalQueued}");

        return self::SUCCESS;
    }
}


