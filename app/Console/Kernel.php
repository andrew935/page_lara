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
        // Run scheduler every minute; per-account intervals enforced in command
        $schedule->command('monitoring:schedule-checks')->everyMinute();

        // Force queue all domains at 08:00 and 17:00 (same behavior as "Manual check (all)")
        $schedule->command('domains:queue-all-hourly')
            ->cron('0 8,17 * * *')
            ->withoutOverlapping();

        // Expire time-boxed promotions (auto-downgrade back to Free)
        $schedule->command('promotions:expire')->hourly()->withoutOverlapping();

        // Auto-import domains from feed daily at 6:00 AM (for accounts with auto_import_feed enabled)
        $schedule->command('domains:auto-import')
            ->dailyAt('06:00')
            ->withoutOverlapping();
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

