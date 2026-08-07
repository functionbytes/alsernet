<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property ?array<int, array{name: string, size: ?int, mime: ?string}> $attachments
 * @property EmailStatus $status
 * @property ?string $error_message
 * @property ?Carbon $sent_at
 * @property ?Carbon $failed_at
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
        'error_message', 'attachments', 'sent_at', 'failed_at', 'created_at',
    ];

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
        'attachments',
        'status',
        'error_message',
        'sent_at',
        'failed_at',
        'bounced_at',
        'complained_at',
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
            'failed_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
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
            Cache::forget('helpdeskemaillog:stats');
            Cache::forget('helpdeskemaillog:trend');
            Cache::forget('helpdeskemaillog:stale');

            if ($model->wasChanged('module') || $model->wasRecentlyCreated || ! $model->exists) {
                Cache::forget('helpdeskemaillog:modules');
            }
        };

        static::created($invalidateCaches);
        static::updated($invalidateCaches);
        static::deleted($invalidateCaches);
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
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
     * Marca el envío como rebotado (DSN recibido en la bandeja de rebotes, ver
     * Modules\Document\Console\Commands\ProcessEmailBouncesCommand). No pisa un
     * status ya 'bounced'/'complained' anterior con uno menos específico.
     */
    public function markAsBounced(?string $reason = null): void
    {
        $this->update([
            'status' => EmailStatus::Bounced,
            'error_message' => $reason ? Str::limit($reason, 2000) : $this->error_message,
            'bounced_at' => now(),
        ]);
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
