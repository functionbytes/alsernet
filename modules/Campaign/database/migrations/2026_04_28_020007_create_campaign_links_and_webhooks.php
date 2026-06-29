<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enlaces extraídos del HTML de la campaña (para reescritura de URL)
        Schema::create('campaign_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();
            $table->text('url');
            $table->string('hash', 64)->index();
            $table->timestamps();
        });

        // Webhooks por campaña: notificaciones de eventos a URLs externas
        Schema::create('campaign_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('event', 64)->index(); // sent|opened|clicked|bounced|complained|...
            $table->string('url');
            $table->string('method', 8)->default('POST');
            $table->json('headers')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_webhooks');
        Schema::dropIfExists('campaign_links');
    }
};
