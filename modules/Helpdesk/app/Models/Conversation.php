<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;
use Modules\Helpdesk\Models\Concerns\HasCustomAttributes;

class Conversation extends Model
{
    use HasCrossDatabaseUserRelation, HasCustomAttributes, HasFactory, HasMessageThread, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversations';

    protected $fillable = [
        'customer_id',
        'subject',
        'priority',
        'channel',
        'external_id',
        'external_sender_id',
        'assigned_at',
        'closed_at',
        'first_response_at',
        'last_message_at',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_message_at' => 'datetime',
            'is_archived' => 'boolean',
            'tags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the status of this conversation
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    /**
     * Get the assignee (support agent)
     * Note: User model is in the default connection, not helpdesk
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsToUser('assignee_id', 'assignee');
    }

    /**
     * Get all messages/items in this conversation
     */
    public function items(): HasMany
    {
        return $this->hasMany(ConversationItem::class, 'conversation_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get only messages (not system events)
     */
    public function messages()
    {
        return $this->items()
            ->where('type', 'message');
    }

    /**
     * Get only system events
     */
    public function events()
    {
        return $this->items()
            ->where('type', '!=', 'message');
    }

    /**
     * Get canned replies available for this conversation
     */
    public function cannedReplies(): HasMany
    {
        return $this->hasMany(CannedReply::class, 'conversation_id');
    }

    /**
     * Get tags assigned to this conversation
     */
    public function conversationTags(): BelongsToMany
    {
        return $this->belongsToMany(
            ConversationTag::class,
            'helpdesk_conversation_tag_pivot',
            'conversation_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Scope: Filter by channel (whatsapp, facebook, instagram, widget, email)
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: Get open conversations
     */
    public function scopeOpen($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('is_open', true));
    }

    /**
     * Scope: Get closed conversations
     */
    public function scopeClosed($query)
    {
        return $query->whereHas('status', fn ($q) => $q->where('is_open', false));
    }

    /**
     * Scope: Get conversations assigned to a user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    /**
     * Scope: Get unassigned conversations
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assignee_id');
    }

    /**
     * Scope: Search by subject or customer name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('subject', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$term}%"));
        });
    }

    /**
     * Get unread messages count for a user
     */
    public function getUnreadCountForUser($userId)
    {
        return $this->messages()
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
            ->count();
    }

    /**
     * Assign conversation to agent
     */
    public function assignTo($userId)
    {
        $this->update([
            'assignee_id' => $userId,
            'assigned_at' => now(),
        ]);

        return $this;
    }

    /**
     * Close conversation
     */
    public function close()
    {
        $closedStatus = Cache::remember('helpdesk:conv-closed-status', 3600, fn () => ConversationStatus::where('is_open', false)->orderBy('order')->first());

        $this->update([
            'status_id' => $closedStatus->id ?? $this->status_id,
            'closed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Reopen conversation
     */
    public function reopen()
    {
        $openStatus = ConversationStatus::where('is_open', true)
            ->orderBy('order')
            ->first();

        $this->update([
            'status_id' => $openStatus->id ?? $this->status_id,
            'closed_at' => null,
        ]);

        return $this;
    }

    /**
     * Get time to first response
     */
    public function getTimeToFirstResponse()
    {
        if (! $this->first_response_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->first_response_at);
    }

    /**
     * Get conversation duration (if closed)
     */
    public function getDuration()
    {
        $end = $this->closed_at ?? now();

        return $this->created_at->diffInMinutes($end);
    }

    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        // Use preloaded count when available (avoids N+1 in list views)
        return $this->messages_count ?? $this->messages()->count();
    }

    /**
     * Get latest message
     */
    public function getLatestMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Returns channel display info: icon, color, label.
     *
     * @return array{icon: string, color: string, label: string}
     */
    public function getChannelInfoAttribute(): array
    {
        return match ($this->channel ?? 'widget') {
            'whatsapp' => ['icon' => 'fab fa-whatsapp',           'color' => '#25D366', 'label' => 'WhatsApp'],
            'facebook' => ['icon' => 'fab fa-facebook-messenger', 'color' => '#0084FF', 'label' => 'Messenger'],
            'instagram' => ['icon' => 'fab fa-instagram',          'color' => '#E1306C', 'label' => 'Instagram'],
            default => ['icon' => 'fas fa-comment-dots',       'color' => '#6c757d', 'label' => 'Widget'],
        };
    }
}
