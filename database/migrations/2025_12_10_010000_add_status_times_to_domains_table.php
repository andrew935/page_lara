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
        Schema::table('domains', function (Blueprint $table) {
            $table->timestamp('status_since')->nullable()->after('status');
            $table->timestamp('last_up_at')->nullable()->after('status_since');
            $table->timestamp('last_down_at')->nullable()->after('last_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['status_since', 'last_up_at', 'last_down_at']);
        });
    }
};

