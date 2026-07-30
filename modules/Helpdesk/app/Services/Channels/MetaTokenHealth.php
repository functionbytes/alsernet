<?php

namespace Modules\Helpdesk\Services\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Notifications\MetaTokenInvalidNotification;

/**
 * Salud de los tokens de Meta por canal. Centraliza la detección de token
 * caducado/inválido (error 190) que hacen WhatsAppBusinessService y
 * MetaGraphChannelDriver, y CIERRA EL BUCLE: marca un flag legible en caché,
 * loguea, y avisa a los managers UNA sola vez por ventana (no en cada envío).
 */
class MetaTokenHealth
{
    private const PREFIX = 'helpdesk:meta:token-invalid:';

    /** Claves de canal usadas en los flags (slug del channelLabel). */
    public const CHANNELS = ['whatsapp', 'facebook-messenger', 'instagram'];

    /**
     * @param  array<string, mixed>|null  $error
     */
    public static function flagInvalid(string $channel, ?array $error = null): void
    {
        $key = self::PREFIX.$channel;
        $firstDetection = ! Cache::has($key);

        Cache::put($key, now()->toIso8601String(), now()->addDay());

        Log::error("Meta [{$channel}]: token caducado o inválido (code 190) — requiere re-autenticación", [
            'error' => $error,
        ]);

        if ($firstDetection) {
            self::notifyManagers($channel);
        }
    }

    public static function isInvalid(string $channel): bool
    {
        return Cache::has(self::PREFIX.$channel);
    }

    /**
     * Estado de todos los canales: valor = ISO8601 de la primera detección, o null si OK.
     *
     * @return array<string, string|null>
     */
    public static function statuses(): array
    {
        $out = [];

        foreach (self::CHANNELS as $channel) {
            $out[$channel] = Cache::get(self::PREFIX.$channel);
        }

        return $out;
    }

    public static function clear(string $channel): void
    {
        Cache::forget(self::PREFIX.$channel);
    }

    private static function notifyManagers(string $channel): void
    {
        try {
            $recipients = User::role('helpdesk-manager')->get();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new MetaTokenInvalidNotification($channel));
            }
        } catch (\Throwable $e) {
            // p. ej. el rol no existe todavía: no debe romper el envío que disparó esto.
            Log::warning('MetaTokenHealth: no se pudo notificar a managers', ['error' => $e->getMessage()]);
        }
    }
}
