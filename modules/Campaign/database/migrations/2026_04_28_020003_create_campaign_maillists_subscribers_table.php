<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot maillist <-> subscriber con campos de estado por suscriptor en cada lista
 * (subscribed/unsubscribed/blacklist/error/etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_maillists_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('mail_list_id')
                ->constrained('campaign_maillists')
                ->cascadeOnDelete();
            $table->foreignId('subscriber_id')
                ->constrained('campaign_subscribers')
                ->cascadeOnDelete();
            $table->string('status', 32)->default('subscribed')->index(); // subscribed|unsubscribed|unconfirmed|bounced|spam-reported
            $table->json('tags')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['mail_list_id', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_maillists_subscribers');
    }
};
