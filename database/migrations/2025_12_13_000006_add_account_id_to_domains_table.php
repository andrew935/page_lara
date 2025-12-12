<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if (!Schema::hasColumn('domains', 'account_id')) {
                $table->foreignId('account_id')->after('id')->nullable()->constrained('accounts');
            }
        });

        if (Schema::hasColumn('domains', 'account_id')) {
            DB::table('domains')->whereNull('account_id')->update(['account_id' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            if (Schema::hasColumn('domains', 'account_id')) {
                $table->dropConstrainedForeignId('account_id');
            }
        });
    }
};


