<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Notifications\NotificationLog;
use App\Notifications\NotificationSetting;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationSettingsController extends Controller
{
    private const ALLOWED_CHANNELS = ['telegram', 'email', 'slack', 'discord', 'teams'];

    /**
     * Show the unified notifications settings page.
     */
    public function edit()
    {
        $account = AccountResolver::current();
        $settings = NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            ['notify_on_fail' => false, 'channels' => []]
        );

        return view('connections.notifications', [
            'settings' => $settings,
        ]);
    }

    /**
     * Store or update all notification channel settings.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_on_fail' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'telegram_api_key' => ['nullable', 'string', 'max:255'],
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
            'discord_webhook_url' => ['nullable', 'url', 'max:500'],
            'teams_webhook_url' => ['nullable', 'url', 'max:500'],
            'channel_telegram' => ['nullable', 'boolean'],
            'channel_email' => ['nullable', 'boolean'],
            'channel_slack' => ['nullable', 'boolean'],
            'channel_discord' => ['nullable', 'boolean'],
            'channel_teams' => ['nullable', 'boolean'],
        ]);

        $account = AccountResolver::current();
        $settings = NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            ['notify_on_fail' => false, 'channels' => []]
        );

        $channels = [];
        if (!empty($data['channel_telegram'])) {
            $channels[] = 'telegram';
        }
        if (!empty($data['channel_email'])) {
            $channels[] = 'email';
        }
        if (!empty($data['channel_slack'])) {
            $channels[] = 'slack';
        }
        if (!empty($data['channel_discord'])) {
            $channels[] = 'discord';
        }
        if (!empty($data['channel_teams'])) {
            $channels[] = 'teams';
        }

        $settings->update([
            'notify_on_fail' => (bool) ($data['notify_on_fail'] ?? false),
            'email' => $data['email'] ?? null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'telegram_api_key' => $data['telegram_api_key'] ?? null,
            'slack_webhook_url' => $data['slack_webhook_url'] ?? null,
            'discord_webhook_url' => $data['discord_webhook_url'] ?? null,
            'teams_webhook_url' => $data['teams_webhook_url'] ?? null,
            'channels' => $channels,
        ]);

        return redirect()
            ->route('notifications.edit')
            ->with('success', 'Notification settings saved.');
    }

    /**
     * Send a test notification via the specified channel.
     */
    public function test(string $channel)
    {
        if (!in_array($channel, self::ALLOWED_CHANNELS, true)) {
            return back()->withErrors(['channel' => 'Invalid channel.']);
        }

        $account = AccountResolver::current();
        $settings = NotificationSetting::where('account_id', $account->id)->first();

        if (!$settings) {
            return back()->withErrors(['channel' => 'No notification settings found.']);
        }

        $message = 'Test notification — ' . now()->toDateTimeString();
        $status = 'sent';
        $meta = [];

        try {
            switch ($channel) {
                case 'telegram':
                    if (!$settings->telegram_api_key || !$settings->telegram_chat_id) {
                        $status = 'failed';
                        $meta['reason'] = 'channel_not_configured';
                        break;
                    }
                    $res = Http::timeout(7)->get("https://api.telegram.org/bot{$settings->telegram_api_key}/sendMessage", [
                        'chat_id' => $settings->telegram_chat_id,
                        'text' => $message,
                    ]);
                    if (!$res->ok()) {
                        $status = 'failed';
                        $meta['error'] = 'Telegram API: ' . $res->status();
                    }
                    break;

                case 'email':
                    if (!$settings->email) {
                        $status = 'failed';
                        $meta['reason'] = 'channel_not_configured';
                        break;
                    }
                    Mail::raw($message, function ($mail) use ($settings) {
                        $mail->to($settings->email)->subject('Domain Monitor — Test');
                    });
                    break;

                case 'slack':
                    if (!$settings->slack_webhook_url) {
                        $status = 'failed';
                        $meta['reason'] = 'channel_not_configured';
                        break;
                    }
                    $res = Http::timeout(7)->post($settings->slack_webhook_url, ['text' => $message]);
                    if (!$res->ok()) {
                        $status = 'failed';
                        $meta['error'] = 'Slack webhook: ' . $res->status();
                    }
                    break;

                case 'discord':
                    if (!$settings->discord_webhook_url) {
                        $status = 'failed';
                        $meta['reason'] = 'channel_not_configured';
                        break;
                    }
                    $res = Http::timeout(7)->post($settings->discord_webhook_url, ['content' => $message]);
                    if (!$res->ok()) {
                        $status = 'failed';
                        $meta['error'] = 'Discord webhook: ' . $res->status();
                    }
                    break;

                case 'teams':
                    if (!$settings->teams_webhook_url) {
                        $status = 'failed';
                        $meta['reason'] = 'channel_not_configured';
                        break;
                    }
                    $res = Http::timeout(7)->post($settings->teams_webhook_url, ['text' => $message]);
                    if (!$res->ok()) {
                        $status = 'failed';
                        $meta['error'] = 'Teams webhook: ' . $res->status();
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $meta['error'] = $e->getMessage();
        }

        NotificationLog::create([
            'account_id' => $account->id,
            'channel' => $channel,
            'status' => $status,
            'message' => $message,
            'meta' => $meta,
        ]);

        $messageKey = $status === 'sent' ? 'success' : 'error';
        $flashMessage = $status === 'sent' ? 'Test message sent.' : 'Test failed (see Notification Logs).';

        return redirect()
            ->route('notifications.edit')
            ->with($messageKey, $flashMessage);
    }
}
