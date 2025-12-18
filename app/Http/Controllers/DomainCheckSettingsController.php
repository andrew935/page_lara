<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DomainCheckSettingsController extends Controller
{
    /**
     * Show the domain check settings page.
     */
    public function index()
    {
        $currentMode = config('domain.check_mode', 'server');
        $modes = config('domain.modes');
        $modeInfo = $modes[$currentMode] ?? $modes['server'];

        return view('settings.domain-check', compact('currentMode', 'modes', 'modeInfo'));
    }

    /**
     * Update the domain check mode.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'check_mode' => ['required', 'string', 'in:server,cloudflare'],
        ]);

        $newMode = $data['check_mode'];
        $oldMode = config('domain.check_mode', 'server');

        // Update the .env file
        $this->updateEnvFile('DOMAIN_CHECK_MODE', $newMode);

        // Clear config cache
        Artisan::call('config:clear');

        // Provide feedback based on mode
        if ($newMode === 'server' && $oldMode === 'cloudflare') {
            $message = 'Switched to Server mode. Make sure the scheduler service is running: docker compose up -d scheduler';
        } elseif ($newMode === 'cloudflare' && $oldMode === 'server') {
            $message = 'Switched to Cloudflare mode. Deploy the worker and stop the scheduler: docker compose stop scheduler';
        } else {
            $message = 'Configuration saved successfully.';
        }

        return redirect()->route('settings.domain-check.index')->with('success', $message);
    }

    /**
     * Update or add a key-value pair in the .env file.
     */
    protected function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $dockerEnvPath = base_path('docker/env.docker');
        
        // Determine which file to use
        $targetPath = File::exists($dockerEnvPath) ? $dockerEnvPath : $envPath;

        if (!File::exists($targetPath)) {
            return;
        }

        $content = File::get($targetPath);
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";

        if (preg_match($pattern, $content)) {
            // Key exists, update it
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Key doesn't exist, append it
            $content .= "\n{$replacement}\n";
        }

        File::put($targetPath, $content);
    }
}

