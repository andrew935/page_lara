<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(false);
            // Registration window
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Promo plan slug (initially: max)
            $table->string('promo_plan_slug')->default('max');
            // Duration for each qualifying user
            $table->unsignedInteger('duration_days')->default(60);
            $table->timestamps();

            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};


