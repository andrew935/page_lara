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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            
            // Stripe IDs
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable();
            
            // Payment details
            $table->unsignedInteger('amount_cents'); // Amount charged
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('succeeded'); // succeeded, failed, refunded
            $table->string('type')->default('subscription'); // subscription, upgrade, refund
            
            // Billing period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Additional info
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
