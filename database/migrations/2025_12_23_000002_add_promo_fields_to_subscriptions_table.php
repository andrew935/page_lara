<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'promo_ends_at')) {
                $table->timestamp('promo_ends_at')->nullable()->after('canceled_at');
            }
            if (!Schema::hasColumn('subscriptions', 'promo_source_promotion_id')) {
                $table->foreignId('promo_source_promotion_id')
                    ->nullable()
                    ->after('promo_ends_at')
                    ->constrained('promotions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'promo_source_promotion_id')) {
                $table->dropConstrainedForeignId('promo_source_promotion_id');
            }
            if (Schema::hasColumn('subscriptions', 'promo_ends_at')) {
                $table->dropColumn('promo_ends_at');
            }
        });
    }
};


