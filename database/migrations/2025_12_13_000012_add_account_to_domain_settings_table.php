<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_settings', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts');
                $table->unique(['account_id']);
            }
        });

        // Backfill existing rows to default account
        if (Schema::hasColumn('domain_settings', 'account_id')) {
            DB::table('domain_settings')->whereNull('account_id')->update(['account_id' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('domain_settings', function (Blueprint $table) {
            if (Schema::hasColumn('domain_settings', 'account_id')) {
                $table->dropUnique(['account_id']);
                $table->dropConstrainedForeignId('account_id');
            }
        });
    }
};


