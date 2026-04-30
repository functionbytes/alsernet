<?php

namespace Modules\Chat\Models\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Chat\Database\Factories\ConversationMessageFactory;
use Modules\Chat\Models\Accounts\Account;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ConversationMessage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Searchable, SoftDeletes;

    protected $table = 'chat_conversation_messages';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ConversationMessageFactory::new();
    }

    protected $fillable = [
        'account_id',
        'conversation_id',
        'inbox_id',
        'message_type',
        'content',
        'sender_type',
        'sender_id',
        'private',
        'content_type',
        'source_id',
        'status',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'private' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if message is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if message is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Scope for unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read messages.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Get the account that owns the message.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the sender (User or Customers).
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if message is incoming (from customer).
     */
    public function isIncoming(): bool
    {
        return $this->message_type === 'incoming';
    }

    /**
     * Check if message is outgoing (from agent).
     */
    public function isOutgoing(): bool
    {
        return $this->message_type === 'outgoing';
    }

    /**
     * Check if message is from a user (agent).
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === User::class || $this->isOutgoing();
    }

    /**
     * Check if message is an activity message.
     */
    public function isActivity(): bool
    {
        return $this->message_type === 'activity';
    }

    /**
     * Check if message is private (internal note).
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * Check if message has attachments via Spatie Media Library.
     */
    public function hasAttachments(): bool
    {
        return $this->getMedia('attachments')->isNotEmpty();
    }

    /**
     * Scope a query to only include incoming messages.
     */
    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'incoming');
    }

    /**
     * Scope a query to only include outgoing messages.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'outgoing');
    }

    /**
     * Scope a query to only include chat messages (non-private).
     */
    public function scopeChat($query)
    {
        return $query->where('private', false);
    }
}
