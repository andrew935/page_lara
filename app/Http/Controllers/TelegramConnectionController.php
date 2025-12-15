<?php

namespace App\Http\Controllers;

use App\Models\TelegramConnection;
use App\Notifications\NotificationLog;
use App\Notifications\NotificationSetting;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramConnectionController extends Controller
{
    /**
     * Show the Telegram connection form.
     */
    public function edit()
    {
        $account = AccountResolver::current();
        $connection = TelegramConnection::first();
        $settings = NotificationSetting::where('account_id', $account->id)->first();

        return view('connections.telegram', [
            'connection' => $connection,
            'settings' => $settings,
        ]);
    }

    /**
     * Store or update the Telegram connection.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'chat_id' => ['required', 'string', 'max:255'],
            'api_key' => ['required', 'string', 'max:255'],
            'notify_on_fail' => ['nullable', 'boolean'],
        ]);

        $connection = TelegramConnection::first();

        if ($connection) {
            $connection->update($data);
        } else {
            $connection = TelegramConnection::create($data);
        }

        $account = AccountResolver::current();
        $settings = NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            ['notify_on_fail' => false, 'channels' => []]
        );
        $channels = is_array($settings->channels) ? $settings->channels : [];
        if (!in_array('telegram', $channels, true)) {
            $channels[] = 'telegram';
        }

        $settings->update([
            'telegram_chat_id' => $data['chat_id'],
            'telegram_api_key' => $data['api_key'],
            'channels' => $channels,
            'notify_on_fail' => (bool) ($data['notify_on_fail'] ?? $settings->notify_on_fail),
        ]);

        return redirect()
            ->route('connections.telegram.edit')
            ->with('success', 'Telegram connection saved successfully.');
    }

    /**
     * Send a test telegram message and record it in notification_logs.
     */
    public function test()
    {
        $account = AccountResolver::current();
        $settings = NotificationSetting::where('account_id', $account->id)->first();

        if (!$settings || !$settings->telegram_api_key || !$settings->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'Telegram API key / Chat ID are not configured for this account.']);
        }

        $message = 'Test Telegram message — ' . now()->toDateTimeString();
        $status = 'sent';
        $meta = [];

        try {
            // Match Telegram "GET with query params" style
            $res = Http::timeout(7)->get("https://api.telegram.org/bot{$settings->telegram_api_key}/sendMessage", [
                'chat_id' => $settings->telegram_chat_id,
                'text' => $message,
            ]);

            if (!$res->ok()) {
                $status = 'failed';
                $meta['error'] = 'Telegram API error: ' . $res->status();
                $meta['body'] = $res->json();
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $meta['error'] = $e->getMessage();
        }

        NotificationLog::create([
            'account_id' => $account->id,
            'channel' => 'telegram',
            'status' => $status,
            'message' => $message,
            'meta' => $meta,
        ]);

        return redirect()
            ->route('connections.telegram.logs')
            ->with('success', $status === 'sent' ? 'Test message sent.' : 'Test message failed (see logs).');
    }
}

