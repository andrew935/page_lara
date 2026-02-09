<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\NotificationSetting;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function show()
    {
        $account = AccountResolver::current();
        $settings = NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            ['notify_on_fail' => false, 'channels' => []]
        );

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_on_fail' => ['required', 'boolean'],
            'channels' => ['array'],
            'email' => ['nullable', 'email'],
            'telegram_chat_id' => ['nullable', 'string'],
            'telegram_api_key' => ['nullable', 'string'],
            'slack_webhook_url' => ['nullable', 'url'],
            'discord_webhook_url' => ['nullable', 'url'],
            'teams_webhook_url' => ['nullable', 'url'],
        ]);

        $account = AccountResolver::current();
        $settings = NotificationSetting::firstOrCreate(
            ['account_id' => $account->id],
            ['notify_on_fail' => false, 'channels' => []]
        );

        $settings->update($data);

        return response()->json([
            'message' => 'Notification settings saved.',
            'settings' => $settings->fresh(),
        ]);
    }
}


