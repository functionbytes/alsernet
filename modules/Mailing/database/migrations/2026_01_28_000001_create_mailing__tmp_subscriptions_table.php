<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing__tmp_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->char('uid', 36);
            $table->string('user_id');
            $table->string('plan_id');
            $table->string('status');
            $table->dateTime('current_period_ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->string('timezone');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('last_period_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing__tmp_subscriptions');
    }
};
