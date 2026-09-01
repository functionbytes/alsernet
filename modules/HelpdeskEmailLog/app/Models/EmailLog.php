<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\HelpdeskEmailLog\Database\Factories\EmailLogFactory;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;

/**
 * @property string $uid
 * @property ?string $mailable_class
 * @property ?string $module
 * @property ?string $entity_type
 * @property ?int $entity_id
 * @property string $from_address
 * @property ?string $from_name
 * @property array<int, string> $to_addresses
 * @property ?array<int, string> $cc_addresses
 * @property ?array<int, string> $bcc_addresses
 * @property ?array<int, string> $reply_to
 * @property ?string $recipients_index
 * @property string $subject
 * @property ?string $message_id
 * @property ?string $body_html
 * @property ?string $body_text
 * @property ?string $raw_headers
 * @property ?array<int, array{name: string, size: ?int, mime: ?string}> $attachments
 * @property EmailStatus $status
 * @property ?string $error_message
 * @property ?Carbon $sent_at
 * @property ?Carbon $delivered_at
 * @property ?Carbon $failed_at
 * @property ?Carbon $bounced_at
 * @property ?Carbon $complained_at
 * @property ?Carbon $suppressed_at
 * @property ?array<string, mixed> $metadata
 * @property ?Carbon $created_at
 */
class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory;

    protected $table = 'email_logs';

    /**
     * Appended to a stored body when it was cut at max_body_bytes (see
     * InspectsMailMessage::bodyOf()). Kept here so both the write path and
     * the resend guards share the same marker.
     */
    public const TRUNCATION_MARKER = "\n<!-- [helpdeskemaillog] contenido truncado -->";

    /**
     * Columns safe to load for list views (excludes the heavy body columns).
     *
     * @var list<string>
     */
    public const LIST_COLUMNS = [
        'id', 'uid', 'mailable_class', 'module', 'entity_type', 'entity_id', 'external_id',
        'from_address', 'from_name', 'to_addresses', 'subject', 'status',
        'error_message', 'attachments', 'sent_at', 'failed_at', 'created_at', 'metadata',
    ];

    /**
     * Caracteres crudos de body_text que EmailLogController::buildListData()
     * trae con `LEFT(body_text, N)` para el extracto de fila (ver
     * bodySnippet()) — de sobra para que, tras colapsar espacios/saltos de
     * línea, quede margen para cortar a un tamaño final legible sin haber
     * cargado el cuerpo completo.
     */
    public const BODY_SNIPPET_RAW_LENGTH = 200;

    /** Longitud final (tras limpiar) del extracto expuesto por bodySnippet(). */
    public const BODY_SNIPPET_LENGTH = 110;

    /**
     * Cache keys backing the emails.index dashboard (stats card, trend chart,
     * stale-queued count, module filter options).
     */
    private const CACHE_KEY_STATS = 'helpdeskemaillog:stats';

    private const CACHE_KEY_TREND = 'helpdeskemaillog:trend';

    private const CACHE_KEY_STALE = 'helpdeskemaillog:stale';

    private const CACHE_KEY_MODULES = 'helpdeskemaillog:modules';

    protected $fillable = [
        'uid',
        'mailable_class',
        'module',
        'entity_type',
        'entity_id',
        'external_id',
        'causer_id',
        'causer_type',
        'from_address',
        'from_name',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'reply_to',
        'subject',
        'message_id',
        'body_html',
        'body_text',
        'raw_headers',
        'attachments',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'failed_at',
        'bounced_at',
        'complained_at',
        'suppressed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'bcc_addresses' => 'array',
            'reply_to' => 'array',
            'attachments' => 'array',
            'metadata' => 'array',
            'status' => EmailStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
            'suppressed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    protected static function booting(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::orderedUuid();
            }

            $model->recipients_index = collect()
                ->merge($model->to_addresses ?? [])
                ->merge($model->cc_addresses ?? [])
                ->merge($model->bcc_addresses ?? [])
                ->filter()
                ->implode(' ');
        });

        $invalidateCaches = function (self $model): void {
            Cache::forget(self::CACHE_KEY_STATS);
            Cache::forget(self::CACHE_KEY_TREND);
            Cache::forget(self::CACHE_KEY_STALE);

            // El filtro de módulos tiene un TTL largo (10 min) porque
            // recalcularlo es un DISTINCT sobre toda la tabla: no vale la pena
            // invalidarlo en cada cambio de estado (sent/failed/bounced/...)
            // de un correo ya existente si su módulo no cambió.
            if ($model->wasChanged('module') || $model->wasRecentlyCreated || ! $model->exists) {
                Cache::forget(self::CACHE_KEY_MODULES);
            }
        };

        static::created($invalidateCaches);
        static::updated($invalidateCaches);
        static::deleted($invalidateCaches);
    }

    /**
     * Invalida las 4 claves del dashboard de una sola vez. Las mutaciones en
     * bloque (bulkDestroy(), PruneEmailLogsCommand) usan query()->update()/
     * delete() directamente, así que nunca disparan los eventos created/
     * updated/deleted de arriba — cada llamador tenía su propio subconjunto
     * de Cache::forget() hardcodeado y desincronizado (bulkDestroy y
     * deleteOldEntries se olvidaban de trend/stale; markStaleQueuedAsFailed
     * no invalidaba nada salvo stats).
     */
    public static function forgetDashboardCaches(): void
    {
        Cache::forget(self::CACHE_KEY_STATS);
        Cache::forget(self::CACHE_KEY_TREND);
        Cache::forget(self::CACHE_KEY_STALE);
        Cache::forget(self::CACHE_KEY_MODULES);
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function opens(): HasMany
    {
        return $this->hasMany(EmailLogOpen::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(EmailLogLink::class);
    }

    /**
     * Clics registrados a través de los enlaces reescritos de este envío
     * (ver LogEmailQueued::injectClickTracking) — hasManyThrough en vez de
     * un FK directo porque el clic pertenece a UN enlace concreto, no
     * directamente al email (un mismo envío puede tener varios enlaces).
     */
    public function clicks(): HasManyThrough
    {
        return $this->hasManyThrough(EmailLogClick::class, EmailLogLink::class);
    }

    public function markAsSent(): void
    {
        $this->update(['status' => EmailStatus::Sent, 'sent_at' => now()]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => EmailStatus::Failed,
            'error_message' => Str::limit($error, 2000),
            'failed_at' => now(),
        ]);
    }

    /**
     * Marca el envío como rebotado (DSN recibido en un buzón de rebotes
     * vigilado — ver Modules\HelpdeskEmailLog\Services\BounceProcessorService
     * — o evento de un proveedor con webhooks). No pisa un status ya
     * 'bounced'/'complained' anterior con uno menos específico.
     *
     * $isHard distingue un rebote permanente (buzón inexistente/deshabilitado
     * — DSN status 5.X.X) de uno temporal (buzón lleno, greylisting — DSN
     * status 4.X.X): la supresión automática de direcciones (lista de
     * supresión) solo debe dispararse sobre rebotes duros, nunca sobre
     * temporales. Se guarda en metadata.bounce_type en vez de una columna
     * nueva (mismo patrón que los flags redacted/truncated que ya viven ahí).
     */
    public function markAsBounced(?string $reason = null, bool $isHard = false): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['bounce_type'] = $isHard ? 'hard' : 'soft';

        $this->update([
            'status' => EmailStatus::Bounced,
            'error_message' => $reason ? Str::limit($reason, 2000) : $this->error_message,
            'bounced_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Tipo de rebote ('hard'/'soft'), o null si nunca se clasificó (filas
     * anteriores a este campo, o correlacionadas por un origen que no
     * distingue hard/soft).
     */
    public function bounceType(): ?string
    {
        return $this->metadata['bounce_type'] ?? null;
    }

    public function markAsComplained(?string $reason = null): void
    {
        $this->update([
            'status' => EmailStatus::Complained,
            'error_message' => $reason ? Str::limit($reason, 2000) : $this->error_message,
            'complained_at' => now(),
        ]);
    }

    /**
     * Marca el envío como bloqueado por la lista de supresión (ver
     * EnforceEmailSuppression) — el mensaje real nunca salió
     * (Mailer::shouldSendMessage() lo canceló), así que nunca transiciona a
     * 'sent' después de esto.
     */
    public function markAsSuppressed(?string $reason = null): void
    {
        $this->update([
            'status' => EmailStatus::Suppressed,
            'error_message' => $reason ? Str::limit($reason, 2000) : $this->error_message,
            'suppressed_at' => now(),
        ]);
    }

    /**
     * Registra, sin bloquear el envío, que uno o más destinatarios (Cc/Bcc,
     * o un To adicional) fueron eliminados del mensaje por estar en la lista
     * de supresión — el envío en sí siguió su curso hacia el resto de
     * destinatarios válidos (ver EnforceEmailSuppression::handle()). Mismo
     * patrón de metadata que bounce_type/redacted/truncated: no hace falta
     * columna nueva para un dato puramente informativo.
     */
    public function addSuppressedRecipientsNote(array $suppressed): void
    {
        $metadata = $this->metadata ?? [];
        $existing = (array) ($metadata['suppressed_recipients'] ?? []);

        $metadata['suppressed_recipients'] = array_values(array_unique([...$existing, ...$suppressed]));

        $this->update(['metadata' => $metadata]);
    }

    /**
     * Whether an open-tracking pixel was actually inserted into this email's
     * body (only true for HelpdeskTickets sends today — see
     * LogEmailQueued::handle()). "0 aperturas" solo es un dato real cuando
     * esto es true; para el resto de correos simplemente nunca hubo píxel.
     */
    public function hasOpenTracking(): bool
    {
        return (bool) ($this->metadata['open_tracking_enabled'] ?? false);
    }

    /**
     * Whether outgoing links in this email's body were rewritten for click
     * tracking (same scope as hasOpenTracking() today — see
     * LogEmailQueued::handle()). "0 clics" solo es un dato real cuando esto
     * es true; para el resto de correos simplemente nunca hubo reescritura.
     */
    public function hasClickTracking(): bool
    {
        return (bool) ($this->metadata['click_tracking_enabled'] ?? false);
    }

    /**
     * The stored body was redacted (sensitive mailable or purged manually),
     * so it no longer represents what was originally sent.
     */
    public function isBodyRedacted(): bool
    {
        return (bool) ($this->metadata['redacted'] ?? false);
    }

    /**
     * The stored body was cut at max_body_bytes. Checks the metadata flag and,
     * for rows created before the flag existed, the truncation marker comment.
     */
    public function isBodyTruncated(): bool
    {
        if ($this->metadata['truncated'] ?? false) {
            return true;
        }

        $marker = trim(self::TRUNCATION_MARKER);

        return ($this->body_html && str_contains($this->body_html, $marker))
            || ($this->body_text && str_contains($this->body_text, $marker));
    }

    /**
     * A resend replays the stored body verbatim, so it is blocked when that
     * body is redacted or truncated (it would leak an incomplete/empty copy).
     */
    public function isResendable(): bool
    {
        return ! $this->isBodyRedacted() && ! $this->isBodyTruncated();
    }

    public function scopeStatus(Builder $query, EmailStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof EmailStatus ? $status->value : $status);
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', EmailStatus::Queued->value);
    }

    public function scopeStaleQueued(Builder $query, int $hours): Builder
    {
        return $query->queued()->where('created_at', '<', now()->subHours($hours));
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', EmailStatus::Sent->value);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', EmailStatus::Failed->value);
    }

    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', EmailStatus::Bounced->value);
    }

    public function scopeSuppressed(Builder $query): Builder
    {
        return $query->where('status', EmailStatus::Suppressed->value);
    }

    /**
     * Tasas de rebote/queja de los últimos $days días. El denominador es
     * "intentos con resultado terminal de entrega" (sent+bounced+complained),
     * NO el total crudo — ese incluiría queued/failed/suppressed, que son
     * fallos del transporte o bloqueos propios, no una señal de reputación
     * del dominio ante el proveedor destino.
     *
     * @return array{attempted: int, bounced: int, complained: int, bounce_rate: float, complaint_rate: float}
     */
    public static function reputationStats(int $days, ?string $fromDomain = null): array
    {
        $since = now()->subDays($days);

        $row = static::query()
            ->whereIn('status', [EmailStatus::Sent->value, EmailStatus::Bounced->value, EmailStatus::Complained->value])
            ->where('created_at', '>=', $since)
            ->when($fromDomain, fn ($q) => $q->where('from_address', 'like', '%@'.$fromDomain))
            ->selectRaw("
                COUNT(*) as attempted,
                SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced,
                SUM(CASE WHEN status = 'complained' THEN 1 ELSE 0 END) as complained
            ")
            ->first();

        $attempted = (int) ($row->attempted ?? 0);
        $bounced = (int) ($row->bounced ?? 0);
        $complained = (int) ($row->complained ?? 0);

        return [
            'attempted' => $attempted,
            'bounced' => $bounced,
            'complained' => $complained,
            'bounce_rate' => $attempted > 0 ? round(($bounced / $attempted) * 100, 2) : 0.0,
            'complaint_rate' => $attempted > 0 ? round(($complained / $attempted) * 100, 2) : 0.0,
        ];
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeForEntity(Builder $query, string $type, int|string $id): Builder
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->status?->color() ?? 'warning');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->status?->label() ?? (string) ($this->attributes['status'] ?? '')
        );
    }

    protected function displayDate(): Attribute
    {
        return Attribute::make(
            get: fn (): Carbon => $this->sent_at ?? $this->failed_at ?? $this->created_at ?? now()
        );
    }

    protected function hasAttachments(): Attribute
    {
        return Attribute::make(get: fn (): bool => ! empty($this->attachments));
    }

    /**
     * Human-readable label for the related entity type (e.g. "Cliente" instead
     * of the raw FQCN). Falls back to the class basename in a readable format.
     */
    protected function entityLabel(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (! $this->entity_type) {
                return null;
            }

            $labels = config('helpdeskemaillog.entity_labels', []);

            return $labels[$this->entity_type] ?? Str::headline(class_basename($this->entity_type));
        });
    }

    /**
     * Extracto corto de texto plano (una línea) para las filas del listado —
     * leído desde el pseudo-columna `body_snippet_raw` que
     * EmailLogController::buildListData() añade con
     * `LEFT(body_text, self::BODY_SNIPPET_RAW_LENGTH)`, nunca desde el cuerpo
     * completo (ver LIST_COLUMNS, que a propósito no carga body_text/body_html).
     *
     * Vacío (null) cuando el envío no tiene body_text — lo que incluye, sin
     * ningún caso especial para ello, a los envíos redactados o purgados:
     * tanto bodyOf() (redacción en origen) como purgeBody() dejan body_text
     * en null, así que "sin extracto" y "sin cuerpo" son aquí exactamente la
     * misma condición — no hace falta mirar metadata['redacted'] aparte.
     *
     * Si solo hay body_html (sin body_text), tampoco hay extracto: limpiar
     * HTML en SQL para ese caso queda fuera de alcance a propósito (el coste
     * de un extracto no debe acercarse al de cargar/parsear el cuerpo).
     */
    protected function bodySnippet(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            $raw = $this->attributes['body_snippet_raw'] ?? null;

            if (! $raw) {
                return null;
            }

            $clean = trim(preg_replace('/\s+/u', ' ', $raw));

            return $clean === '' ? null : Str::limit($clean, self::BODY_SNIPPET_LENGTH);
        });
    }

    /**
     * Best-effort URL to the related entity, or null if there is no safe mapping.
     */
    protected function entityUrl(): Attribute
    {
        return Attribute::make(get: function (): ?string {
            if (! $this->entity_type || ! $this->entity_id) {
                return null;
            }

            $route = config('helpdeskemaillog.entity_routes.'.$this->entity_type);

            if (! $route || ! Route::has($route)) {
                return null;
            }

            try {
                return route($route, $this->entity_id);
            } catch (\Throwable $e) {
                Log::warning('HelpdeskEmailLog: no se pudo generar entity_url.', [
                    'entity_type' => $this->entity_type,
                    'entity_id' => $this->entity_id,
                    'route' => $route,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }
}
