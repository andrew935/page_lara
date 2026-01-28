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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('status');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->string('stripe_payment_method_id')->nullable()->after('stripe_subscription_id');
            $table->foreignId('next_plan_id')->nullable()->after('plan_id')->constrained('plans');
            $table->unsignedInteger('prorated_amount_cents')->nullable()->after('next_plan_id');
            $table->timestamp('last_payment_at')->nullable()->after('prorated_amount_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['next_plan_id']);
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'stripe_payment_method_id',
                'next_plan_id',
                'prorated_amount_cents',
                'last_payment_at',
            ]);
        });
    }
};
