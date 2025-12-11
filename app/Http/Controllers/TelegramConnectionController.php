<?php

namespace App\Http\Controllers;

use App\Models\TelegramConnection;
use Illuminate\Http\Request;

class TelegramConnectionController extends Controller
{
    /**
     * Show the Telegram connection form.
     */
    public function edit()
    {
        $connection = TelegramConnection::first();

        return view('connections.telegram', [
            'connection' => $connection,
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
        ]);

        $connection = TelegramConnection::first();

        if ($connection) {
            $connection->update($data);
        } else {
            $connection = TelegramConnection::create($data);
        }

        return redirect()
            ->route('connections.telegram.edit')
            ->with('success', 'Telegram connection saved successfully.');
    }
}

