<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_webhook_id')
                ->constrained('campaign_webhooks')
                ->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('status', 32)->default('pending'); // pending|success|failed
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->text('response')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_webhook_logs');
    }
};
