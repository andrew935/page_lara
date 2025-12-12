<?php

namespace App\Http\Controllers;

use App\Models\DomainSetting;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class DomainSettingsController extends Controller
{
    public function edit()
    {
        $account = AccountResolver::current();
        $settings = DomainSetting::firstOrCreate(
            ['account_id' => $account->id],
            [
                'check_interval_minutes' => 60,
                'notify_on_fail' => false,
                'notify_payload' => null,
                'feed_url' => config('domain.source_url'),
            ]
        );

        return view('domains.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'check_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notify_on_fail' => ['nullable', 'boolean'],
            'notify_payload' => ['nullable', 'string'],
            'feed_url' => ['nullable', 'url'],
        ]);

        $account = AccountResolver::current();
        $settings = DomainSetting::firstOrCreate(['account_id' => $account->id]);
        $settings->update($data);

        return redirect()->route('domains.settings.edit')->with('success', 'Settings saved.');
    }
}

