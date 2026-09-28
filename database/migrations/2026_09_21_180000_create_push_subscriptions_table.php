<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suscripciones Web Push (VAPID) por navegador/dispositivo — un usuario
 * puede tener varias (varios navegadores/equipos). Ya la consulta
 * Modules\HelpdeskSocial\Services\WebPushService::isConfigured()/getSubscriptionsForUsers(),
 * que hasta ahora hacía un corte temprano porque la tabla no existía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            // TEXT no se puede indexar directamente en InnoDB sin longitud de
            // prefijo (el endpoint de FCM/Mozilla push service supera el
            // límite de clave utf8mb4) — un hash sha256 fijo de 64 chars
            // hace de sustituto indexable para el UNIQUE de abajo.
            $table->string('endpoint_hash', 64);
            // p256dh / auth: claves que el navegador genera al suscribirse al
            // PushManager — minishlink/web-push las necesita para cifrar el
            // payload (RFC 8291). content_encoding: 'aesgcm' (legado) o
            // 'aes128gcm' (estándar actual, lo que envían los navegadores modernos).
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding')->default('aes128gcm');
            $table->timestamps();

            // Mismo navegador puede volver a suscribirse (mismo endpoint):
            // upsert en vez de duplicar filas obsoletas.
            $table->unique(['user_id', 'endpoint_hash'], 'push_subscriptions_user_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
