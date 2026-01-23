<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskChat\Models\Customers\Customer;

class ConversationItem extends Model
{
    use HasFactory, SoftDeletes;

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
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
    public function isMessage(): bool
    {
        return $this->type === 'message';
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
     * Get the sender's name (customer or agent)
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isFromCustomer()) {
            return $this->author?->name ?? 'Unknown';
        }

        if ($this->isFromAgent()) {
            return $this->user?->name ?? 'Agent';
        }

        return 'System';
    }

    /**
     * Mark message as read by a user
     */
    public function markAsRead(int $userId): ConversationRead
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachment_urls) && count($this->attachment_urls) > 0;
    }
}
