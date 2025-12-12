<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->boolean('notify_on_fail')->default(false);
            $table->string('email')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_api_key')->nullable();
            $table->string('slack_webhook_url')->nullable();
            $table->json('channels')->nullable(); // ['email','telegram','slack']
            $table->timestamps();

            $table->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};


