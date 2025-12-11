<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Respect configured interval; fallback to hourly
        $minutes = 60;
        if (class_exists(\App\Models\DomainSetting::class)) {
            $setting = \App\Models\DomainSetting::first();
            if ($setting && $setting->check_interval_minutes) {
                $minutes = max(1, min(1440, (int) $setting->check_interval_minutes));
            }
        }
        $schedule->command('domains:check --all --limit=' . config('domain.schedule_batch', 50))
            ->everyMinutes($minutes);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

