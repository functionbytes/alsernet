<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Database\Factories\Helpdesk\TicketItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Customer;

class TicketItem extends Model
{
    /** @use HasFactory<TicketItemFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_items';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'user_id',
        'type',
        'body',
        'html_body',
        'attachment_urls',
        'is_internal',
        'metadata',
        'sentiment',
        'sentiment_score',
    ];

    protected function casts(): array
    {
        return [
            'attachment_urls' => 'array',
            'metadata' => 'array',
            'is_internal' => 'boolean',
            'sentiment_score' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the ticket this item belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the customer who authored this message (if from customer)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'author_id');
    }

    /**
     * Get the staff user who authored this message (if from agent)
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship
        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        );
    }

    /**
     * Get users who have read this message
     */
    public function reads(): HasMany
    {
        return $this->hasMany(TicketRead::class, 'ticket_item_id');
    }

    /**
     * Scope: Get only messages (not system events)
     */
    public function scopeMessages($query)
    {
        return $query->where('type', 'message');
    }

    /**
     * Scope: Get only system events
     */
    public function scopeEvents($query)
    {
        return $query->where('type', '!=', 'message');
    }

    /**
     * Scope: Get only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Get only external messages
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Get messages from customers
     */
    public function scopeFromCustomer($query)
    {
        return $query->whereNotNull('author_id')->whereNull('user_id');
    }

    /**
     * Scope: Get messages from agents
     */
    public function scopeFromAgent($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Check if this is a message (not a system event)
     */
    public function isMessage(): bool
    {
        return $this->type === 'message';
    }

    /**
     * Check if this is a system event
     */
    public function isEvent(): bool
    {
        return $this->type !== 'message';
    }

    /**
     * Check if message is from a customer
     */
    public function isFromCustomer(): bool
    {
        return $this->author_id !== null && $this->user_id === null;
    }

    /**
     * Check if message is from an agent
     */
    public function isFromAgent(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get readable event type label
     */
    public function getEventLabelAttribute(): string
    {
        $labels = [
            'message' => 'Mensaje',
            'internal_note' => 'Nota Interna',
            'status_change' => 'Cambio de Estado',
            'assigned' => 'Asignado',
            'unassigned' => 'Desasignado',
            'priority_changed' => 'Prioridad Cambiada',
            'category_changed' => 'Categoría Cambiada',
            'sla_warning' => 'Advertencia SLA',
            'sla_breach' => 'Incumplimiento SLA',
            'attachment_added' => 'Adjunto Añadido',
            'customer_replied' => 'Respuesta del Cliente',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get badge color for event type
     */
    public function getEventColorAttribute(): string
    {
        $colors = [
            'message' => 'primary',
            'internal_note' => 'secondary',
            'status_change' => 'info',
            'assigned' => 'success',
            'unassigned' => 'warning',
            'priority_changed' => 'warning',
            'category_changed' => 'info',
            'sla_warning' => 'warning',
            'sla_breach' => 'danger',
            'attachment_added' => 'info',
            'customer_replied' => 'primary',
            'closed' => 'danger',
            'reopened' => 'success',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Get the sender's name (customer or agent)
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isFromCustomer()) {
            return $this->author?->name ?? 'Desconocido';
        }

        if ($this->isFromAgent()) {
            return $this->user?->name ?? 'Agente';
        }

        return 'Sistema';
    }

    /**
     * Get the sender's avatar URL
     */
    public function getSenderAvatarAttribute(): ?string
    {
        if ($this->isFromCustomer()) {
            return $this->author?->getAvatarUrl();
        }

        if ($this->isFromAgent()) {
            return $this->user?->getAvatarUrl();
        }

        return null;
    }

    /**
     * Mark message as read by a user
     */
    public function markAsRead($userId)
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    /**
     * Get read count for this message
     */
    public function getReadCount(): int
    {
        return $this->reads()->distinct('user_id')->count();
    }

    /**
     * Check if read by a specific user
     */
    public function isReadByUser($userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Get list of users who have read this message
     */
    public function getReadByUsers()
    {
        $userIds = $this->reads()
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Get body content (prefer HTML over plain text)
     */
    public function getContentAttribute(): ?string
    {
        return $this->html_body ?? $this->body;
    }

    /**
     * Get plain text version of body
     */
    public function getPlainTextAttribute(): ?string
    {
        if ($this->html_body) {
            return strip_tags($this->html_body);
        }

        return $this->body;
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachment_urls) && count($this->attachment_urls) > 0;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->hasAttachments() ? count($this->attachment_urls) : 0;
    }
}
