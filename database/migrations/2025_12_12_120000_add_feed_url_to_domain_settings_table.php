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
        Schema::table('domain_settings', function (Blueprint $table) {
            $table->string('feed_url')->nullable()->after('notify_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_settings', function (Blueprint $table) {
            $table->dropColumn('feed_url');
        });
    }
};

