<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_histories';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'field_name',
        'old_value',
        'new_value',
        'action_type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────────────────────

    /**
     * Get the ticket this history belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who made this change (cross-database relationship)
     */
    public function user(): ?object
    {
        if (! $this->user_id) {
            return null;
        }

        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        )->first();
    }

    // ────────────────────────────────────────────────────────────────
    // Query Scopes
    // ────────────────────────────────────────────────────────────────

    /**
     * Scope by action type
     */
    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope by field name
     */
    public function scopeByFieldName($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * Order by newest first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ────────────────────────────────────────────────────────────────
    // Static Methods - Creating History Records
    // ────────────────────────────────────────────────────────────────

    /**
     * Log a field change
     */
    public static function logFieldChange(
        Ticket $ticket,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        ?User $user = null
    ): self {
        return static::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'field_name' => $field,
            'old_value' => self::serializeValue($oldValue),
            'new_value' => self::serializeValue($newValue),
            'action_type' => 'updated',
        ]);
    }

    /**
     * Log an action (for non-field changes)
     */
    public static function logAction(
        Ticket $ticket,
        string $action,
        ?User $user = null,
        array $metadata = []
    ): self {
        return static::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'field_name' => $action,
            'action_type' => $action,
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Log ticket creation
     */
    public static function logTicketCreated(Ticket $ticket, ?User $user = null): self
    {
        return static::logAction($ticket, 'created', $user);
    }

    /**
     * Log assignment
     */
    public static function logAssigned(Ticket $ticket, ?User $user = null, ?User $assignedTo = null): self
    {
        return static::logAction($ticket, 'assigned', $user, [
            'assignee_id' => $assignedTo?->id,
            'assignee_name' => $assignedTo?->name,
        ]);
    }

    /**
     * Log unassignment
     */
    public static function logUnassigned(Ticket $ticket, ?User $user = null): self
    {
        return static::logAction($ticket, 'unassigned', $user);
    }

    /**
     * Log status change
     */
    public static function logStatusChange(
        Ticket $ticket,
        TicketStatus $oldStatus,
        TicketStatus $newStatus,
        ?User $user = null
    ): self {
        return static::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'field_name' => 'status_id',
            'old_value' => $oldStatus->id.' ('.$oldStatus->name.')',
            'new_value' => $newStatus->id.' ('.$newStatus->name.')',
            'action_type' => 'status_change',
            'metadata' => [
                'old_status_id' => $oldStatus->id,
                'new_status_id' => $newStatus->id,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // Accessors & Helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * Get formatted label for action type in Spanish
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'created' => 'Ticket creado',
            'updated' => 'Actualizado',
            'deleted' => 'Eliminado',
            'assigned' => 'Asignado',
            'unassigned' => 'Sin asignar',
            'status_change' => 'Estado cambiado',
            'priority_change' => 'Prioridad cambiada',
            'category_change' => 'Categoría cambiada',
            'custom_field_change' => 'Campo personalizado actualizado',
            'note_added' => 'Nota agregada',
            'comment_added' => 'Comentario agregado',
            'mail_sent' => 'Email enviado',
            'mail_received' => 'Email recibido',
            default => $this->action_type,
        };
    }

    /**
     * Get bootstrap color for action type
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action_type) {
            'created' => 'success',
            'deleted' => 'danger',
            'assigned' => 'info',
            'unassigned' => 'warning',
            'status_change' => 'primary',
            'priority_change' => 'warning',
            'category_change' => 'secondary',
            'custom_field_change' => 'info',
            'note_added' => 'light',
            'comment_added' => 'light',
            'mail_sent' => 'success',
            'mail_received' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get description for this history record
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->action_type) {
            'updated' => "{$this->field_name} cambió de '{$this->old_value}' a '{$this->new_value}'",
            'status_change' => "Estado cambió a {$this->new_value}",
            'priority_change' => "Prioridad cambió a {$this->new_value}",
            'category_change' => "Categoría cambió a {$this->new_value}",
            'assigned' => 'Asignado a '.($this->metadata['assignee_name'] ?? 'N/A'),
            'unassigned' => 'Ticket sin asignar',
            default => $this->getActionLabelAttribute(),
        };
    }

    /**
     * Serialize value for storage
     */
    private static function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Prevent updates (immutable record)
     */
    public function save(array $options = []): bool
    {
        // Only allow creates, not updates
        if ($this->exists) {
            return true;
        }

        return parent::save($options);
    }

    /**
     * Prevent updates via update() method
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        // Do not update historical records
        return false;
    }

    /**
     * Prevent deletes
     */
    public function delete(): ?bool
    {
        // Historical records should not be deleted
        return false;
    }
}
