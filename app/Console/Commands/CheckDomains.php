<?php

namespace App\Console\Commands;

use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use Illuminate\Console\Command;

class CheckDomains extends Command
{
    protected $signature = 'domains:check {domain?} {--all : Check all domains} {--limit=50 : Limit batch size when using --all}';

    protected $description = 'Check domains for availability and SSL validity';

    public function handle(): int
    {
        $domainArg = $this->argument('domain');
        $checkAll = $this->option('all');
        $limit = (int) $this->option('limit');

        if ($domainArg) {
            $domains = Domain::where('domain', $domainArg)->get();
        } elseif ($checkAll) {
            $domains = Domain::orderBy('last_checked_at')->limit($limit)->get();
        } else {
            $this->error('Provide a domain or use --all');
            return self::INVALID;
        }

        if ($domains->isEmpty()) {
            $this->info('No domains to check.');
            return self::SUCCESS;
        }

        foreach ($domains as $domain) {
            $this->line("Queueing {$domain->domain} ...");
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

        $this->info('Checks queued.');
        return self::SUCCESS;
    }
}

