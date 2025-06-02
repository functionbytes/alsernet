<?php


namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnException extends Model
{
    protected $table = 'return_exceptions';

    protected $fillable = [
        'return_request_id',
        'return_inspection_id',
        'exception_type',
        'description',
        'severity',
        'resolution',
        'compensation_amount',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'requires_escalation',
        'escalated_to',
        'escalated_at'
    ];

    protected $casts = [
        'compensation_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'requires_escalation' => 'boolean'
    ];

    // Tipos de excepción
    const TYPE_LOST_IN_TRANSIT = 'lost_in_transit';
    const TYPE_DAMAGED_BY_CARRIER = 'damaged_by_carrier';
    const TYPE_WRONG_PRODUCT = 'wrong_product';
    const TYPE_MISSING_PARTS = 'missing_parts';
    const TYPE_COUNTERFEIT = 'counterfeit';
    const TYPE_USED_AS_NEW = 'used_as_new';
    const TYPE_QUANTITY_MISMATCH = 'quantity_mismatch';

    // Niveles de severidad
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Tipos de resolución
    const RESOLUTION_PENDING = 'pending';
    const RESOLUTION_COMPENSATION = 'compensation';
    const RESOLUTION_REPLACEMENT = 'replacement';
    const RESOLUTION_INVESTIGATION = 'investigation';
    const RESOLUTION_ESCALATED = 'escalated';
    const RESOLUTION_CLOSED = 'closed';

    // Relaciones
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(ReturnInspection::class, 'return_inspection_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function escalatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('resolution', self::RESOLUTION_PENDING);
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeRequiresEscalation($query)
    {
        return $query->where('requires_escalation', true)
            ->whereNull('escalated_at');
    }

    /**
     * Determinar si requiere escalación automática
     */
    public function shouldAutoEscalate(): bool
    {
        // Escalar automáticamente si:
        // 1. Severidad crítica
        if ($this->severity === self::SEVERITY_CRITICAL) {
            return true;
        }

        // 2. Compensación alta
        if ($this->compensation_amount > config('returns.escalation_threshold', 1000)) {
            return true;
        }

        // 3. Tipos específicos
        $escalateTypes = [
            self::TYPE_COUNTERFEIT,
            self::TYPE_LOST_IN_TRANSIT
        ];

        if (in_array($this->exception_type, $escalateTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Escalar excepción
     */
    public function escalate($userId): bool
    {
        return $this->update([
            'requires_escalation' => true,
            'escalated_to' => $userId,
            'escalated_at' => now(),
            'resolution' => self::RESOLUTION_ESCALATED
        ]);
    }

    /**
     * Resolver excepción
     */
    public function resolve($resolution, $userId, $notes = null, $compensationAmount = null): bool
    {
        $data = [
            'resolution' => $resolution,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ];

        if ($compensationAmount !== null) {
            $data['compensation_amount'] = $compensationAmount;
        }

        return $this->update($data);
    }

    /**
     * Obtener etiqueta del tipo
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_LOST_IN_TRANSIT => 'Perdido en tránsito',
            self::TYPE_DAMAGED_BY_CARRIER => 'Dañado por transportista',
            self::TYPE_WRONG_PRODUCT => 'Producto incorrecto',
            self::TYPE_MISSING_PARTS => 'Partes faltantes',
            self::TYPE_COUNTERFEIT => 'Producto falsificado',
            self::TYPE_USED_AS_NEW => 'Usado vendido como nuevo',
            self::TYPE_QUANTITY_MISMATCH => 'Cantidad incorrecta'
        ];

        return $labels[$this->exception_type] ?? 'Otro';
    }

    /**
     * Obtener color de severidad
     */
    public function getSeverityColor(): string
    {
        $colors = [
            self::SEVERITY_LOW => 'info',
            self::SEVERITY_MEDIUM => 'warning',
            self::SEVERITY_HIGH => 'danger',
            self::SEVERITY_CRITICAL => 'dark'
        ];

        return $colors[$this->severity] ?? 'secondary';
    }
}


