<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Un renglón por cada providerEventId ya procesado — ver
 * EmailProviderWebhookController::receive() y la migración
 * create_email_provider_events_table para el porqué (webhooks de proveedor
 * se reintentan y llegan duplicados).
 *
 * @property string $provider
 * @property string $provider_event_id
 */
class ProviderWebhookEvent extends Model
{
    protected $table = 'email_provider_events';

    public $timestamps = false;

    protected $fillable = ['provider', 'provider_event_id'];

    /**
     * Intenta registrar $eventId como visto por primera vez para $provider.
     * Devuelve true si es la primera vez (hay que procesarlo), false si ya
     * se había visto (duplicado — el proveedor reintentó la entrega).
     *
     * Atómico gracias al UNIQUE(provider, provider_event_id): dos peticiones
     * concurrentes para el mismo evento chocan en el índice y solo una gana,
     * a diferencia de un exists()+create() que dejaría una ventana de carrera.
     */
    public static function markSeenIfNew(string $provider, string $eventId): bool
    {
        try {
            self::query()->create([
                'provider' => $provider,
                'provider_event_id' => $eventId,
                'created_at' => now(),
            ]);

            return true;
        } catch (QueryException $e) {
            // Código de violación de restricción única (23000 en
            // MySQL/MariaDB/SQLite) — cualquier otro error de BD debe seguir
            // reventando, no tragarse silenciosamente.
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }
    }
}
