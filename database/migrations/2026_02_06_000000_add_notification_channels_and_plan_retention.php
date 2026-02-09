<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->string('discord_webhook_url')->nullable()->after('slack_webhook_url');
            $table->string('teams_webhook_url')->nullable()->after('discord_webhook_url');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('history_retention_days')->nullable()->default(0)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['discord_webhook_url', 'teams_webhook_url']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('history_retention_days');
        });
    }
};
