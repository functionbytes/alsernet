<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;

class ConversationItem extends Model
{
    use HasCrossDatabaseUserRelation, HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_items';

    protected $fillable = [
        'conversation_id',
        'author_id',
        'user_id',
        'type',
        'body',
        'html_body',
        'attachment_urls',
        'is_internal',
        'metadata',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'attachment_urls' => 'array',
            'metadata' => 'array',
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the conversation this item belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
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
    public function user(): BelongsTo
    {
        return $this->belongsToUser('user_id', 'user');
    }

    /**
     * Get users who have read this message
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class, 'conversation_item_id');
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
    public function isMessage()
    {
        return $this->type === 'message';
    }

    /**
     * Check if this is a system event
     */
    public function isEvent()
    {
        return $this->type !== 'message';
    }

    /**
     * Check if message is from a customer
     */
    public function isFromCustomer()
    {
        return $this->author_id !== null && $this->user_id === null;
    }

    /**
     * Check if message is from an agent
     */
    public function isFromAgent()
    {
        return $this->user_id !== null;
    }

    /**
     * Get readable event type label
     */
    public function getEventLabelAttribute()
    {
        $labels = [
            'message' => 'Mensaje',
            'status_change' => 'Cambio de Estado',
            'assigned' => 'Asignado',
            'unassigned' => 'Desasignado',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
            'archived' => 'Archivado',
            'unarchived' => 'Desarchivado',
            'priority_changed' => 'Prioridad Cambiada',
            'internal_note' => 'Nota Interna',
            'attachment_added' => 'Adjunto Añadido',
            'customer_replied' => 'Respuesta del Cliente',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get badge color for event type
     */
    public function getEventColorAttribute()
    {
        $colors = [
            'message' => 'primary',
            'status_change' => 'info',
            'assigned' => 'success',
            'unassigned' => 'warning',
            'closed' => 'danger',
            'reopened' => 'success',
            'archived' => 'secondary',
            'unarchived' => 'info',
            'priority_changed' => 'warning',
            'internal_note' => 'secondary',
            'attachment_added' => 'info',
            'customer_replied' => 'primary',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Get the sender's name (customer or agent)
     */
    public function getSenderNameAttribute()
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
    public function getSenderAvatarAttribute()
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
    public function getReadCount()
    {
        return $this->reads()->distinct('user_id')->count();
    }

    /**
     * Check if read by a specific user
     */
    public function isReadByUser($userId)
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
    public function getContentAttribute()
    {
        return $this->html_body ?? $this->body;
    }

    /**
     * Get plain text version of body
     */
    public function getPlainTextAttribute()
    {
        if ($this->html_body) {
            return strip_tags($this->html_body);
        }

        return $this->body;
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments()
    {
        return ! empty($this->attachment_urls) && count($this->attachment_urls) > 0;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute()
    {
        return $this->hasAttachments() ? count($this->attachment_urls) : 0;
    }
}
