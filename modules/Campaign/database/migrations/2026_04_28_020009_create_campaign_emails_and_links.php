<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas para el motor de automation: cada Email es una "plantilla específica"
 * dentro de un automation node, con sus propios links y webhooks para tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_emails', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('subject')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->longText('html')->nullable();
            $table->longText('plain')->nullable();
            $table->string('preheader')->nullable();
            $table->boolean('track_open')->default(true);
            $table->boolean('track_click')->default(true);
            $table->boolean('sign_dkim')->default(false);
            $table->unsignedBigInteger('automation_element_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('campaign_email_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('email_id')
                ->constrained('campaign_emails')
                ->cascadeOnDelete();
            $table->text('url');
            $table->string('hash', 64)->index();
            $table->timestamps();
        });

        Schema::create('campaign_email_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('email_id')
                ->constrained('campaign_emails')
                ->cascadeOnDelete();
            $table->string('event', 64)->index();
            $table->string('url');
            $table->string('method', 8)->default('POST');
            $table->json('headers')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_email_webhooks');
        Schema::dropIfExists('campaign_email_links');
        Schema::dropIfExists('campaign_emails');
    }
};
