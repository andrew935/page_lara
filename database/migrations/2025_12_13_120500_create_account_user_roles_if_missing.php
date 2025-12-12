<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_user_roles')) {
            Schema::create('account_user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50);
                $table->timestamps();

                $table->unique(['account_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_user_roles');
    }
};


