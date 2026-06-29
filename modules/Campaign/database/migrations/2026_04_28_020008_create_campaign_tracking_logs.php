<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log central por mensaje enviado: el "ledger" de qué se envió a quién
        Schema::create('campaign_tracking_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->cascadeOnDelete();
            $table->foreignId('subscriber_id')
                ->nullable()
                ->constrained('campaign_subscribers')
                ->nullOnDelete();
            $table->foreignId('sending_server_id')
                ->nullable()
                ->constrained('campaign_sending_servers')
                ->nullOnDelete();
            $table->string('email')->index();
            $table->string('message_id')->nullable()->index(); // X-Mailer-Message-Id
            $table->string('runtime_message_id')->nullable();   // ID del proveedor (SES, SendGrid, …)
            $table->string('status', 32)->default('sent')->index(); // sent|failed|skipped|bounced|feedback
            $table->text('error')->nullable();
            $table->string('trigger_id')->nullable()->index();  // automation trigger ID si aplica
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });

        // Aperturas, clicks, desuscripciones, feedback (tabla por evento para evitar UPDATE caro)
        Schema::create('campaign_open_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_log_id')
                ->constrained('campaign_tracking_logs')
                ->cascadeOnDelete();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_click_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_log_id')
                ->constrained('campaign_tracking_logs')
                ->cascadeOnDelete();
            $table->foreignId('campaign_link_id')
                ->nullable()
                ->constrained('campaign_links')
                ->nullOnDelete();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_unsubscribe_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_log_id')
                ->nullable()
                ->constrained('campaign_tracking_logs')
                ->nullOnDelete();
            $table->foreignId('subscriber_id')
                ->constrained('campaign_subscribers')
                ->cascadeOnDelete();
            $table->string('ip')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_feedback_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracking_log_id')
                ->nullable()
                ->constrained('campaign_tracking_logs')
                ->nullOnDelete();
            $table->string('email')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_feedback_logs');
        Schema::dropIfExists('campaign_unsubscribe_logs');
        Schema::dropIfExists('campaign_click_logs');
        Schema::dropIfExists('campaign_open_logs');
        Schema::dropIfExists('campaign_tracking_logs');
    }
};
