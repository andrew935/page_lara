<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Models\TelegramConnection;
use App\Services\DomainCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $domainId)
    {
    }

    public function handle(DomainCheckService $checker): void
    {
        $domain = Domain::find($this->domainId);
        if (!$domain) {
            return;
        }

        $oldStatus = $domain->status;
        $result = $checker->check($domain->domain);

        $payload = [
            'status' => $result['status'],
            'ssl_valid' => $result['ssl_valid'],
            'last_checked_at' => $result['checked_at'],
            'last_check_error' => $result['error'],
        ];

        if ($oldStatus !== $result['status']) {
            $payload['status_since'] = now();
            if ($result['status'] === 'ok') {
                $payload['last_up_at'] = now();
            } elseif ($result['status'] === 'down') {
                $payload['last_down_at'] = now();
            }
        }

        $domain->update($payload);

        if ($oldStatus !== 'down' && $result['status'] === 'down') {
            $this->notifyDown($domain, $result['error']);
        }
    }

    protected function notifyDown(Domain $domain, ?string $error): void
    {
        $connection = TelegramConnection::first();
        if (!$connection || !$connection->api_key || !$connection->chat_id) {
            return;
        }

        $message = "Domain {$domain->domain} is DOWN";
        if ($error) {
            $message .= " — {$error}";
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$connection->api_key}/sendMessage", [
                'chat_id' => $connection->chat_id,
                'text' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send Telegram notification', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

