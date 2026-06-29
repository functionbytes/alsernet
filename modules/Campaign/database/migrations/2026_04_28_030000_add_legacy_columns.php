<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración suplementaria: añade columnas que el código heredado de Acelle
 * accede pero que no estaban en mis migraciones consolidadas iniciales.
 *
 * Detectadas con grep -rhoE '\$campaign->[a-z_]+\b' contra los modelos
 * heredados, $fillable, controllers manager y vistas Blade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->longText('html')->nullable()->after('plain'); // HTML compilado en cache
            $table->longText('final_html')->nullable()->after('html');
            $table->string('html_signature')->nullable()->after('final_html');
            $table->float('score')->nullable(); // SpamAssassin score
            $table->string('step', 32)->nullable(); // wizard step (recipients|template|confirm|...)
            $table->timestamp('last_run_at')->nullable();
            $table->json('attachments_meta')->nullable(); // metadata de adjuntos
        });

        Schema::table('campaign_maillists', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('name'); // alias legacy de name
            $table->string('mail_subscribe')->nullable();   // notificación admin al subscribirse
            $table->string('mail_unsubscribe')->nullable(); // notificación admin al desuscribirse
            $table->string('mail_daily')->nullable();       // resumen diario admin
            $table->string('send_to')->nullable();          // dirección de notificación admin
            $table->integer('custom_order')->default(0);
            $table->boolean('all_sending_servers')->default(false); // si usar todos los servers o sólo los pivot
            $table->boolean('available')->default(true);
            $table->unsignedBigInteger('cached_subscriber_count')->default(0);
        });

        Schema::table('campaign_subscribers', function (Blueprint $table): void {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('verification_status', 32)->nullable()->index(); // valid|invalid|risky|unknown
            $table->timestamp('verification_at')->nullable();
            $table->string('confirmation_code', 64)->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
        });

        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('name');
            $table->json('categories')->nullable();
            $table->boolean('is_default')->default(false);
        });

        Schema::table('campaign_automations', function (Blueprint $table): void {
            $table->text('description')->nullable();
            $table->timestamp('last_executed_at')->nullable();
        });

        // Webhook HMAC: cada webhook tiene su propio shared secret para que
        // el receptor pueda verificar la autenticidad del body con
        // X-Webhook-Signature: sha256=<hex>.
        Schema::table('campaign_webhooks', function (Blueprint $table): void {
            $table->string('secret', 128)->nullable();
        });
        Schema::table('campaign_email_webhooks', function (Blueprint $table): void {
            $table->string('secret', 128)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['html', 'final_html', 'html_signature', 'score', 'step', 'last_run_at', 'attachments_meta']);
        });

        Schema::table('campaign_maillists', function (Blueprint $table): void {
            $table->dropColumn(['title', 'mail_subscribe', 'mail_unsubscribe', 'mail_daily', 'send_to', 'custom_order', 'all_sending_servers', 'available', 'cached_subscriber_count']);
        });

        Schema::table('campaign_subscribers', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'verification_status', 'verification_at', 'confirmation_code', 'confirmed_at']);
        });

        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->dropColumn(['title', 'categories', 'is_default']);
        });

        Schema::table('campaign_automations', function (Blueprint $table): void {
            $table->dropColumn(['description', 'last_executed_at']);
        });

        Schema::table('campaign_webhooks', function (Blueprint $table): void {
            $table->dropColumn('secret');
        });
        Schema::table('campaign_email_webhooks', function (Blueprint $table): void {
            $table->dropColumn('secret');
        });
    }
};
