<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('check_interval_minutes')->default(60);
            $table->boolean('notify_on_fail')->default(false);
            $table->text('notify_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_settings');
    }
};

