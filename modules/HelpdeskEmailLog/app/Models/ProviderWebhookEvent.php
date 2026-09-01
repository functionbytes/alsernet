<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Un renglón por cada providerEventId ya procesado — ver
 * EmailProviderWebhookController::receive() y la migración
 * create_email_provider_events_table para el porqué (webhooks de proveedor
 * se reintentan y llegan duplicados). Ampliado (ver
 * add_payload_and_correlation_to_email_provider_events_table) para además
 * servir de registro de auditoría/depuración: qué llegó (payload), a qué
 * EmailLog correlacionó (email_log_id, si a alguno) y cuándo se procesó
 * (processed_at).
 *
 * @property string $provider
 * @property ?string $provider_event_id
 * @property ?array<string, mixed> $payload
 * @property ?string $event_type
 * @property int $duplicate_count
 * @property ?int $email_log_id
 * @property ?Carbon $processed_at
 * @property ?Carbon $created_at
 */
class ProviderWebhookEvent extends Model
{
    protected $table = 'email_provider_events';

    public $timestamps = false;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'payload',
        'event_type',
        'duplicate_count',
        'email_log_id',
        'processed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'duplicate_count' => 'integer',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * withTrashed(): el email correlacionado puede seguir en la papelera
     * (recuperable 30 días, ver EmailLog::class) — sin esto, el scope global
     * de SoftDeletes ocultaría la relación aunque email_log_id siga
     * apuntando a una fila real.
     */
    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class)->withTrashed();
    }

    /**
     * Registra un evento entrante. Primera vez que se ve este
     * provider+provider_event_id → crea la fila (con payload/tipo) y
     * devuelve el modelo, para que el caller pueda completarla después con
     * email_log_id/processed_at una vez corra el correlador. Ya visto →
     * NO se vuelve a insertar (evita reprocesar el mismo bounce/complaint
     * dos veces), solo se cuenta el reintento en duplicate_count y se
     * devuelve null.
     *
     * $providerEventId puede venir null (el proveedor no lo mandó, p.ej. un
     * evento de Mailrelay sin 'id') — sin una clave estable no hay nada que
     * deduplicar, así que se sintetiza una única por evento (nunca choca,
     * así que nunca cuenta como duplicado: es exactamente el mismo
     * comportamiento que ya tenía este caso antes de esta migración, solo
     * que ahora SÍ queda un registro con su payload).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function recordIfNew(string $provider, ?string $providerEventId, string $eventType, array $payload): ?self
    {
        $eventId = ($providerEventId !== null && $providerEventId !== '')
            ? $providerEventId
            : 'no-id:'.(string) Str::uuid();

        try {
            return self::query()->create([
                'provider' => $provider,
                'provider_event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Código de violación de restricción única (23000 en
            // MySQL/MariaDB/SQLite). Solo puede chocar un providerEventId
            // REAL (el sintético de arriba nunca colisiona): el proveedor
            // reintentó la entrega de un evento ya visto.
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            self::query()
                ->where('provider', $provider)
                ->where('provider_event_id', $eventId)
                ->increment('duplicate_count');

            return null;
        }
    }
}
