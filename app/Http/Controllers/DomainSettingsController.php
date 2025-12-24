<?php

namespace App\Http\Controllers;

use App\Billing\Services\PlanRulesService;
use App\Models\DomainSetting;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class DomainSettingsController extends Controller
{
    public function edit(PlanRulesService $planRules)
    {
        $account = AccountResolver::current();
        $minInterval = (int) $planRules->checkIntervalMinutes($account);
        $settings = DomainSetting::firstOrCreate(
            ['account_id' => $account->id],
            [
                'check_interval_minutes' => $minInterval,
                'notify_on_fail' => false,
                'notify_payload' => null,
                'feed_url' => config('domain.source_url'),
            ]
        );

        // If legacy data was below plan minimum, bump it so the UI + modal can submit successfully.
        if ((int) $settings->check_interval_minutes < $minInterval) {
            $settings->update(['check_interval_minutes' => $minInterval]);
            $settings->refresh();
        }

        return view('domains.settings', compact('settings', 'minInterval'));
    }

    public function update(Request $request, PlanRulesService $planRules)
    {
        $account = AccountResolver::current();
        $minInterval = (int) $planRules->checkIntervalMinutes($account);

        $data = $request->validate([
            'check_interval_minutes' => ['required', 'integer', 'min:' . $minInterval, 'max:1440'],
            'notify_on_fail' => ['nullable', 'boolean'],
            'notify_payload' => ['nullable', 'string'],
            'feed_url' => ['nullable', 'url'],
        ]);

        // Ensure notify_on_fail has a boolean value (not null)
        $data['notify_on_fail'] = (bool) ($data['notify_on_fail'] ?? false);

        $settings = DomainSetting::firstOrCreate(['account_id' => $account->id]);
        $settings->update($data);

        return redirect()->route('domains.settings.edit')->with('success', 'Settings saved.');
    }
}

