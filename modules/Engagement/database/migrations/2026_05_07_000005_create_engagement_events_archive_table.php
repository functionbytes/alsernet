<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_events_archive', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->index();
            $table->unsignedBigInteger('inbox_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('event_name', 100);
            $table->string('platform', 50)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_events_archive');
    }
};
