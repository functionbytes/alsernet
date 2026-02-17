<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Traits\HasAttachments;
use Modules\Attention\Traits\HasUid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Modelo principal del sistema PQRSF simplificado
 *
 * Representa una Petición, Queja, Reclamo, Sugerencia o Felicitación
 */
class Attention extends Model implements HasMedia
{
    use HasAttachments, HasFactory, HasUid, InteractsWithMedia, SoftDeletes {
        HasAttachments::registerMediaCollections insteadof InteractsWithMedia;
    }

    protected $table = 'attentions';

    protected $fillable = [
        // Identificación
        'uid',
        'radicado',

        // Tipo y categorización
        'type_id',
        'category_id',
        'sede_id',

        // Ciudadano
        'customer_firstname',
        'customer_lastname',
        'customer_email',
        'customer_cellphone',
        'customer_dni',
        'customer_address',
        'is_anonymous',

        // Contenido
        'subject',
        'description',

        // Estado y flujo
        'status',
        'department_id',
        'assigned_user_id',

        // Resolución
        'response_type',
        'resolution',
        'resolved_at',
        'closed_at',

        // Satisfacción
        'satisfaction_rating',

        // SLA
        'sla_policy_id',

        // Retención de datos
        'archived_at',

        // Timestamps automáticos: created_at, updated_at
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'status' => AttentionStatus::class,
        'response_type' => ResponseType::class,
        'satisfaction_rating' => 'integer',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'full_name',
    ];

    // =========================================================================
    // MUTATORS - Transformaciones automáticas
    // =========================================================================

    protected function customerFirstname(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    protected function customerLastname(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    // =========================================================================
    // ACCESSORS - Atributos calculados
    // =========================================================================

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getFullNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'ANÓNIMO';
        }

        return trim("{$this->customer_firstname} {$this->customer_lastname}");
    }

    // =========================================================================
    // SCOPES - Queries reutilizables
    // =========================================================================

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByRadicado($query, string $radicado)
    {
        return $query->where('radicado', $radicado);
    }

    public function scopeByStatus($query, AttentionStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', AttentionStatus::RECEIVED);
    }

    public function scopeInProcess($query)
    {
        return $query->where('status', AttentionStatus::IN_PROCESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', AttentionStatus::RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', AttentionStatus::CLOSED);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('radicado', 'like', "%{$search}%")
                ->orWhere('customer_firstname', 'like', "%{$search}%")
                ->orWhere('customer_lastname', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%")
                ->orWhere('customer_dni', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // =========================================================================
    // RELATIONSHIPS - Relaciones con otros modelos
    // =========================================================================

    /**
     * Tipo de atención (P/Q/R/S/F)
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AttentionType::class, 'type_id');
    }

    /**
     * Categoría temática
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AttentionCategory::class, 'category_id');
    }

    /**
     * Sede donde se radicó
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(AttentionSede::class, 'sede_id');
    }

    /**
     * Departamento asignado
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(AttentionDepartment::class, 'department_id');
    }

    /**
     * Usuario responsable asignado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }

    /**
     * Notas internas del PQRSF
     */
    public function notes(): HasMany
    {
        return $this->hasMany(AttentionNote::class, 'attention_id');
    }

    /**
     * Historial de acciones realizadas
     */
    public function actions(): HasMany
    {
        return $this->hasMany(AttentionAction::class, 'attention_id');
    }

    /**
     * Emails enviados relacionados
     */
    public function mails(): HasMany
    {
        return $this->hasMany(AttentionMail::class, 'attention_id');
    }

    /**
     * Encuestas de satisfacción
     */
    public function satisfactionSurveys(): HasMany
    {
        return $this->hasMany(AttentionSatisfaction::class, 'attention_id');
    }

    /**
     * Política SLA asignada
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(AttentionSlaPolicy::class, 'sla_policy_id');
    }

    /**
     * Incumplimientos SLA
     */
    public function breaches(): HasMany
    {
        return $this->hasMany(AttentionSlaBreach::class, 'attention_id');
    }

    // =========================================================================
    // HELPER METHODS - Métodos auxiliares
    // =========================================================================

    /**
     * Generate unique radicado number
     */
    public static function generateRadicado(): string
    {
        $prefix = config('attention.radicado_prefix', 'PQRSF');
        $year = date('Y');

        // Get last radicado of current year
        $lastRadicado = static::where('radicado', 'like', "{$prefix}-{$year}-%")
            ->orderBy('radicado', 'desc')
            ->first();

        if ($lastRadicado) {
            // Extract number and increment
            preg_match('/-(\d+)$/', $lastRadicado->radicado, $matches);
            $number = isset($matches[1]) ? (int) $matches[1] + 1 : 1;
        } else {
            $number = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $number);
    }

    /**
     * Log an action
     */
    public function logAction(string $action, ?string $description = null, ?int $userId = null): AttentionAction
    {
        return $this->actions()->create([
            'action' => $action,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Add internal note
     */
    public function addNote(string $content, ?int $userId = null): AttentionNote
    {
        return $this->notes()->create([
            'content' => $content,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Change status
     */
    public function changeStatus(AttentionStatus $newStatus, ?string $comment = null): void
    {
        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        $this->logAction(
            'status_changed',
            "Estado cambiado de {$oldStatus->label()} a {$newStatus->label()}. {$comment}"
        );
    }

    /**
     * Assign to user
     */
    public function assignTo(int $userId, ?string $comment = null): void
    {
        $this->update(['assigned_user_id' => $userId]);

        $this->logAction(
            'assigned',
            "Asignado al usuario ID: {$userId}. {$comment}"
        );
    }

    /**
     * Assign to department
     */
    public function assignToDepartment(int $departmentId, ?string $comment = null): void
    {
        $this->update(['department_id' => $departmentId]);

        $this->logAction(
            'department_assigned',
            "Asignado al departamento ID: {$departmentId}. {$comment}"
        );
    }

    /**
     * Resolve the attention
     */
    public function resolve(string $resolution, ResponseType $responseType): void
    {
        $this->update([
            'status' => AttentionStatus::RESOLVED,
            'resolution' => $resolution,
            'response_type' => $responseType,
            'resolved_at' => now(),
        ]);

        $this->logAction('resolved', 'PQRSF resuelto');
    }

    /**
     * Close the attention
     */
    public function close(?string $comment = null): void
    {
        $this->update([
            'status' => AttentionStatus::CLOSED,
            'closed_at' => now(),
        ]);

        $this->logAction('closed', "PQRSF cerrado. {$comment}");
    }

    /**
     * Check if can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            AttentionStatus::RECEIVED,
            AttentionStatus::IN_PROCESS,
        ]);
    }

    /**
     * Check if is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === AttentionStatus::RESOLVED;
    }

    /**
     * Check if is closed
     */
    public function isClosed(): bool
    {
        return $this->status === AttentionStatus::CLOSED;
    }

    // =========================================================================
    // DATA RETENTION - Política de retención según Ley 594/2000
    // =========================================================================

    /**
     * Validar si puede ser eliminado permanentemente según Ley 594/2000
     */
    public function canBePermanentlyDeleted(): bool
    {
        if (! config('attention.data_retention.enabled', true)) {
            return true;
        }

        $minYears = config('attention.data_retention.minimum_retention_years', 5);
        $closedAt = $this->closed_at ?? $this->created_at;

        return $closedAt->diffInYears(now()) >= $minYears;
    }

    /**
     * Obtener años transcurridos desde el cierre
     */
    public function getYearsSinceClosed(): int
    {
        $closedAt = $this->closed_at ?? $this->created_at;

        return $closedAt->diffInYears(now());
    }

    /**
     * Verificar si el PQRSF debe ser archivado
     */
    public function shouldBeArchived(): bool
    {
        if ($this->archived_at) {
            return false; // Ya está archivado
        }

        if (! $this->closed_at) {
            return false; // No está cerrado
        }

        $archiveYears = config('attention.data_retention.management_archive_years', 5);

        return $this->closed_at->diffInYears(now()) >= $archiveYears;
    }

    /**
     * Verificar si tiene valor histórico (conservación permanente)
     */
    public function hasHistoricalValue(): bool
    {
        $historicYears = config('attention.data_retention.permanent_archive_years', 15);
        $closedAt = $this->closed_at ?? $this->created_at;

        return $closedAt->diffInYears(now()) >= $historicYears;
    }

    /**
     * Override del forceDelete para validar retención legal
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function forceDelete()
    {
        if (! $this->canBePermanentlyDeleted()) {
            throw new \Exception(
                "No se puede eliminar permanentemente el PQRSF {$this->radicado}. ".
                'La Ley 594/2000 (Ley General de Archivos) requiere retención mínima de '.
                config('attention.data_retention.minimum_retention_years', 5).' años. '.
                "Han transcurrido {$this->getYearsSinceClosed()} año(s) desde su cierre."
            );
        }

        // Log de auditoría antes de eliminar
        $this->logAction(
            'force_deleted',
            "PQRSF eliminado permanentemente. Años transcurridos: {$this->getYearsSinceClosed()}",
            auth()->id()
        );

        return parent::forceDelete();
    }

    /**
     * Archivar el PQRSF
     */
    public function archive(): bool
    {
        if ($this->archived_at) {
            return false; // Ya está archivado
        }

        $result = $this->update(['archived_at' => now()]);

        if ($result) {
            $this->logAction(
                'archived',
                'PQRSF archivado según política de retención (Ley 594/2000)'
            );
        }

        return $result;
    }

    /**
     * Scope para PQRSF archivados
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope para PQRSF no archivados
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope para PQRSF que deberían ser archivados
     */
    public function scopeShouldBeArchived($query)
    {
        $archiveYears = config('attention.data_retention.management_archive_years', 5);
        $cutoffDate = now()->subYears($archiveYears);

        return $query->whereNotNull('closed_at')
            ->where('closed_at', '<=', $cutoffDate)
            ->whereNull('archived_at');
    }

    /**
     * Scope para PQRSF con valor histórico
     */
    public function scopeHistorical($query)
    {
        $historicYears = config('attention.data_retention.permanent_archive_years', 15);
        $cutoffDate = now()->subYears($historicYears);

        return $query->where(function ($q) use ($cutoffDate) {
            $q->where('closed_at', '<=', $cutoffDate)
                ->orWhere(function ($sq) use ($cutoffDate) {
                    $sq->whereNull('closed_at')
                        ->where('created_at', '<=', $cutoffDate);
                });
        });
    }
}
