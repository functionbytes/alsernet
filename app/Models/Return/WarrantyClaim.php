<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'warranty_id',
        'user_id',
        'issue_category',
        'issue_description',
        'issue_occurred_date',
        'symptoms',
        'status',
        'priority',
        'estimated_repair_cost',
        'resolution_description',
        'assigned_to',
        'assigned_at',
        'assigned_department',
        'manufacturer_claim_id',
        'manufacturer_status',
        'manufacturer_response',
        'submitted_to_manufacturer_at',
        'manufacturer_response_at',
        'resolution_type',
        'resolution_cost',
        'customer_satisfaction_notes',
        'customer_rating',
        'resolved_at',
        'resolved_by',
        'status_history',
        'communication_log',
        'attachments',
        'response_due_date',
        'resolution_due_date',
        'sla_met',
        'total_resolution_hours',
    ];

    protected $casts = [
        'issue_occurred_date' => 'date',
        'assigned_at' => 'datetime',
        'submitted_to_manufacturer_at' => 'datetime',
        'manufacturer_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'response_due_date' => 'datetime',
        'resolution_due_date' => 'datetime',
        'estimated_repair_cost' => 'decimal:2',
        'resolution_cost' => 'decimal:2',
        'sla_met' => 'boolean',
        'symptoms' => 'array',
        'manufacturer_response' => 'array',
        'status_history' => 'array',
        'communication_log' => 'array',
        'attachments' => 'array',
    ];

    /**
     * Estados de reclamo
     */
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_REPAIR = 'in_repair';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Prioridades
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Tipos de resolución
     */
    const RESOLUTION_REPAIR = 'repair';
    const RESOLUTION_REPLACE = 'replace';
    const RESOLUTION_REFUND = 'refund';
    const RESOLUTION_REJECT = 'reject';

    /**
     * Relación con garantía
     */
    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    /**
     * Relación con usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario asignado
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Usuario que resolvió
     */
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope para reclamos activos
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED
        ]);
    }

    /**
     * Scope para reclamos pendientes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_IN_REPAIR
        ]);
    }

    /**
     * Scope para reclamos por prioridad
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope para reclamos vencidos
     */
    public function scopeOverdue($query)
    {
        return $query->where('resolution_due_date', '<', now())
            ->whereNotIn('status', [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
                self::STATUS_REJECTED
            ]);
    }

    /**
     * Verificar si está activo
     */
    public function isActive(): bool
    {
        return !in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED
        ]);
    }

    /**
     * Verificar si está vencido
     */
    public function isOverdue(): bool
    {
        return $this->resolution_due_date &&
            $this->resolution_due_date < now() &&
            $this->isActive();
    }

    /**
     * Cambiar estado del reclamo
     */
    public function changeStatus(string $newStatus, User $user = null, string $notes = ''): bool
    {
        $oldStatus = $this->status;

        // Actualizar historial
        $history = $this->status_history ?? [];
        $history[] = [
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $user?->id,
            'changed_at' => now(),
            'notes' => $notes,
        ];

        $this->update([
            'status' => $newStatus,
            'status_history' => $history,
        ]);

        // Acciones específicas por estado
        match ($newStatus) {
            self::STATUS_APPROVED => $this->onApproved($user),
            self::STATUS_IN_REPAIR => $this->onInRepair($user),
            self::STATUS_COMPLETED => $this->onCompleted($user),
            self::STATUS_REJECTED => $this->onRejected($user, $notes),
            default => null,
        };

        return true;
    }

    /**
     * Asignar reclamo a usuario
     */
    public function assignTo(User $user, string $department = null): bool
    {
        $this->update([
            'assigned_to' => $user->id,
            'assigned_at' => now(),
            'assigned_department' => $department,
        ]);

        $this->addCommunicationLog([
            'type' => 'assignment',
            'message' => "Reclamo asignado a {$user->name}",
            'user_id' => $user->id,
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Enviar a fabricante
     */
    public function submitToManufacturer(): array
    {
        $manufacturer = $this->warranty->manufacturer;

        if (!$manufacturer) {
            return [
                'success' => false,
                'message' => 'No hay fabricante asociado a la garantía',
            ];
        }

        $result = $manufacturer->createWarrantyClaim($this);

        if ($result['success']) {
            $this->update([
                'manufacturer_claim_id' => $result['manufacturer_claim_id'],
                'manufacturer_status' => $result['status'] ?? 'submitted',
                'submitted_to_manufacturer_at' => now(),
            ]);

            $this->addCommunicationLog([
                'type' => 'manufacturer_submission',
                'message' => 'Reclamo enviado al fabricante',
                'manufacturer_claim_id' => $result['manufacturer_claim_id'],
                'timestamp' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Agregar comunicación al log
     */
    public function addCommunicationLog(array $entry): void
    {
        $log = $this->communication_log ?? [];
        $log[] = $entry;
        $this->update(['communication_log' => $log]);
    }

    /**
     * Generar número de reclamo único
     */
    public static function generateClaimNumber(): string
    {
        do {
            $number = 'CLM-' . now()->format('Y') . '-' . strtoupper(uniqid());
        } while (self::where('claim_number', $number)->exists());

        return $number;
    }

    /**
     * Acciones cuando se aprueba
     */
    protected function onApproved(User $user = null): void
    {
        // Enviar al fabricante si es posible
        if ($this->warranty->manufacturer && $this->warranty->manufacturer->can_handle_claims) {
            $this->submitToManufacturer();
        }

        $this->addCommunicationLog([
            'type' => 'status_change',
            'message' => 'Reclamo aprobado',
            'user_id' => $user?->id,
            'timestamp' => now(),
        ]);
    }

    /**
     * Acciones cuando entra en reparación
     */
    protected function onInRepair(User $user = null): void
    {
        $this->addCommunicationLog([
            'type' => 'status_change',
            'message' => 'Producto en reparación',
            'user_id' => $user?->id,
            'timestamp' => now(),
        ]);
    }

    /**
     * Acciones cuando se completa
     */
    protected function onCompleted(User $user = null): void
    {
        $totalHours = $this->created_at->diffInHours(now());
        $slaHours = 72; // Configurable

        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user?->id,
            'total_resolution_hours' => $totalHours,
            'sla_met' => $totalHours <= $slaHours,
        ]);

        $this->addCommunicationLog([
            'type' => 'completion',
            'message' => 'Reclamo completado',
            'user_id' => $user?->id,
            'resolution_hours' => $totalHours,
            'timestamp' => now(),
        ]);
    }

    /**
     * Acciones cuando se rechaza
     */
    protected function onRejected(User $user = null, string $reason = ''): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user?->id,
            'resolution_description' => $reason,
        ]);

        $this->addCommunicationLog([
            'type' => 'rejection',
            'message' => 'Reclamo rechazado',
            'reason' => $reason,
            'user_id' => $user?->id,
            'timestamp' => now(),
        ]);
    }
}
