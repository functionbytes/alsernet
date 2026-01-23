<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('integration_id')
                ->constrained('webhook_integrations')
                ->onDelete('cascade');

            $table->string('name', 100);
            $table->string('url', 500)->comment('Endpoint destino');
            $table->boolean('is_active')->default(true);

            // Eventos suscritos
            $table->json('subscribed_events')->comment('["order.created", "order.updated"]');

            // Autenticación
            $table->enum('auth_type', ['none', 'bearer', 'basic', 'apikey', 'custom'])
                ->default('none');
            $table->json('auth_config')->nullable()->comment('Credenciales según auth_type');

            // Firma outbound
            $table->string('signing_secret', 255)->nullable()->comment('Secret para firmar outbound');

            // Configuración de entrega
            $table->integer('timeout_ms')->default(10000)->comment('Timeout en ms');
            $table->integer('max_attempts')->default(6)->comment('Máximo de reintentos');
            $table->json('backoff_policy')->nullable()->comment('[1m, 5m, 15m, 1h, 6h, 24h]');

            $table->timestamps();

            $table->index('integration_id', 'idx_subscriptions_integration');
            $table->index('is_active', 'idx_subscriptions_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
