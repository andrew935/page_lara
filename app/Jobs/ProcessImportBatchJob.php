<?php

namespace App\Jobs;

use App\Billing\Services\PlanRulesService;
use App\Imports\ImportBatch;
use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $batchId)
    {
    }

    public function handle(PlanRulesService $planRules): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (!$batch) {
            return;
        }

        $payload = $batch->payload ?? [];
        $account = $batch->account;
        $limit = $planRules->maxDomains($account);
        $current = Domain::where('account_id', $account->id)->count();

        $created = 0;
        $failed = 0;

        foreach ($payload as $item) {
            if (!is_string($item) || trim($item) === '') {
                $failed++;
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
            ]);

            if ($model->exists) {
                $created++;
            }
        }

        $batch->update([
            'status' => 'completed',
            'processed' => $created,
            'failed' => $failed,
        ]);
    }
}


