<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Notifications\NotificationLog;
use App\Support\AccountResolver;
use Illuminate\Http\Request;

class TelegramLogController extends Controller
{
    public function index(Request $request)
    {
        $account = AccountResolver::current();

        $query = NotificationLog::query()
            ->where('account_id', $account->id)
            ->orderByDesc('id');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, ['sent', 'failed'], true)) {
            $query->where('status', $status);
        }

        $channel = $request->query('channel');
        $allowedChannels = ['telegram', 'email', 'slack', 'discord', 'teams'];
        if (is_string($channel) && in_array($channel, $allowedChannels, true)) {
            $query->where('channel', $channel);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('connections.telegram-logs', [
            'logs' => $logs,
            'status' => $status,
            'channel' => $channel,
        ]);
    }
}


