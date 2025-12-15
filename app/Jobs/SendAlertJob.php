<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Notifications\NotificationLog;
use App\Notifications\NotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(
        public int $accountId,
        public int $domainId,
        public string $message,
        public bool $force = false,
        public ?array $channelsOverride = null
    ) {
    }

    public function handle(): void
    {
        $settings = NotificationSetting::where('account_id', $this->accountId)->first();
        $domain = Domain::find($this->domainId);

        if (!$settings || (!$settings->notify_on_fail && !$this->force) || !$domain) {
            return;
        }

        $channels = $this->channelsOverride ?? ($settings->channels ?? []);
        if (empty($channels)) {
            $channels = ['telegram'];
        }

        foreach ($channels as $channel) {
            $status = 'sent';
            $meta = [];

            try {
                if ($channel === 'email' && $settings->email) {
                    Mail::raw($this->message, function ($mail) use ($settings) {
                        $mail->to($settings->email)
                            ->subject('Domain Down Alert');
                    });
                } elseif ($channel === 'slack' && $settings->slack_webhook_url) {
                    Http::timeout(5)->post($settings->slack_webhook_url, ['text' => $this->message]);
                } elseif ($channel === 'telegram' && $settings->telegram_api_key && $settings->telegram_chat_id) {
                    Http::timeout(5)->post("https://api.telegram.org/bot{$settings->telegram_api_key}/sendMessage", [
                        'chat_id' => $settings->telegram_chat_id,
                        'text' => $this->message,
                    ]);
                } else {
                    $status = 'failed';
                    $meta['reason'] = 'channel_not_configured';
                }
            } catch (\Throwable $e) {
                $status = 'failed';
                $meta['error'] = $e->getMessage();
                Log::warning('Alert dispatch failed', [
                    'channel' => $channel,
                    'account_id' => $this->accountId,
                    'domain_id' => $this->domainId,
                    'error' => $e->getMessage(),
                ]);
            }

            NotificationLog::create([
                'account_id' => $this->accountId,
                'channel' => $channel,
                'status' => $status,
                'message' => $this->message,
                'meta' => $meta,
            ]);
        }
    }
}


