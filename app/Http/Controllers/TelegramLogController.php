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
            ->where('channel', 'telegram')
            ->orderByDesc('id');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, ['sent', 'failed'], true)) {
            $query->where('status', $status);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('connections.telegram-logs', [
            'logs' => $logs,
            'status' => $status,
        ]);
    }
}


