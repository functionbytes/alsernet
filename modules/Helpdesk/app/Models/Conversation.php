<?php

namespace Modules\Helpdesk\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\Helpdesk\Database\Factories\ConversationFactory;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;
use Modules\Helpdesk\Models\Concerns\HasCustomAttributes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Conversation extends Model
{
    use HasCrossDatabaseUserRelation, HasCustomAttributes, HasFactory, HasMessageThread, LogsActivity, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversations';

    protected $fillable = [
        'customer_id',
        'inbox_id',
        'assignee_id',
        'status_id',
        'subject',
        'priority',
        'channel',
        'external_id',
        'external_sender_id',
        'group_id',
        'assigned_at',
        'closed_at',
        'first_response_at',
        'last_message_at',
        'tags',
        'is_spam',
        'is_archived',
        'is_public',
        'snoozed_until',
        'snoozed_by',
        'sla_warned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_message_at' => 'datetime',
            'sla_warned_at' => 'datetime',
            'is_archived' => 'boolean',
            'is_spam' => 'boolean',
            'tags' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'priority', 'assignee_id', 'closed_at', 'snoozed_until'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('helpdesk');
    }

    /**
     * Get the status of this conversation
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    /**
     * Inbox the conversation belongs to (polymorphic via Inbox->channel).
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    /**
     * Customer who initiated the conversation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
     * Get drafts for this conversation (one per user)
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(ConversationDraft::class, 'conversation_id');
    }

    /**
     * Get canned replies available for this conversation
     */
    public function cannedReplies(): HasMany
    {
        return $this->hasMany(CannedReply::class, 'conversation_id');
    }

    /**
     * Per-user read receipts. Used by ?unread=1 inbox filter.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class, 'conversation_id');
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
     * Scope: Filter by channel (whatsapp, facebook, instagram, web, email)
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

        $this->broadcastInboxChanged('assigned');

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

        $this->broadcastInboxChanged('status_changed');

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

        $this->broadcastInboxChanged('status_changed');

        return $this;
    }

    /**
     * Broadcast an inbox change to all relevant agents (assignee + auth user).
     */
    protected function broadcastInboxChanged(string $changeType): void
    {
        $userIds = array_filter(array_unique([
            $this->assignee_id,
            auth()->id(),
        ]));

        foreach ($userIds as $userId) {
            event(new InboxItemChanged($this->id, $userId, $changeType));
        }
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
        return match ($this->channel ?? 'web') {
            'whatsapp' => ['icon' => 'fab fa-whatsapp',           'color' => '#25D366', 'label' => 'WhatsApp'],
            'facebook' => ['icon' => 'fab fa-facebook-messenger', 'color' => '#0084FF', 'label' => 'Messenger'],
            'instagram' => ['icon' => 'fab fa-instagram',          'color' => '#E1306C', 'label' => 'Instagram'],
            default => ['icon' => 'fas fa-comment-dots',       'color' => '#6c757d', 'label' => 'Web'],
        };
    }

    /**
     * Map conversation model to the array shape expected by the inbox conv-item partial.
     *
     * @return array{
     *   id: int, name: string, initials: string, color: string,
     *   channel: string, channelIcon: string, time: string,
     *   preview: string, sla: array{0: string, 1: string},
     *   priority: ?string, unread: int, urgent: bool, on: bool
     * }
     */
    public function toInboxArray(?int $selectedId = null): array
    {
        $name = $this->customer?->name ?? $this->subject ?? 'Sin nombre';
        $initials = collect(preg_split('/\s+/', trim($name)))
            ->take(2)
            ->map(fn ($w) => mb_substr($w, 0, 1))
            ->implode('');

        $colorIndex = (($this->customer_id ?? $this->id ?? 1) - 1) % 8 + 1;

        $channelMap = [
            'whatsapp' => ['code' => 'wa', 'icon' => 'fab fa-whatsapp'],
            'facebook' => ['code' => 'fb', 'icon' => 'fab fa-facebook-messenger'],
            'instagram' => ['code' => 'ig', 'icon' => 'fab fa-instagram'],
            'email' => ['code' => 'email', 'icon' => 'far fa-envelope'],
            'web' => ['code' => 'web', 'icon' => 'far fa-comment-dots'],
        ];
        $ch = $channelMap[$this->channel ?? 'web'] ?? $channelMap['web'];

        $lastAt = $this->last_message_at ?? $this->updated_at ?? $this->created_at;
        if (! $lastAt) {
            $time = '—';
        } elseif ($lastAt->isToday()) {
            $minutes = (int) round($lastAt->diffInMinutes(now()));
            if ($minutes < 1) {
                $time = 'ahora';
            } elseif ($minutes < 60) {
                $time = $minutes.'m';
            } else {
                $time = $lastAt->format('H:i');
            }
        } else {
            $time = $lastAt->format('d M');
        }

        $preview = $this->getLatestMessage()?->body ?? $this->subject ?? '';
        $preview = mb_strimwidth(strip_tags((string) $preview), 0, 90, '…');

        return [
            'id' => $this->id,
            'name' => mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1),
            'initials' => mb_strtoupper($initials ?: '?'),
            'color' => 'c'.$colorIndex,
            'channel' => $ch['code'],
            'channelIcon' => $ch['icon'],
            'time' => $time,
            'preview' => e($preview),
            'sla' => $this->slaStatusForInbox($lastAt),
            'priority' => in_array($this->priority, ['low', 'normal', 'high', 'urgent'], true) ? $this->priority : null,
            'unread' => $this->unreadCountForInbox(),
            'urgent' => $this->priority === 'urgent',
            'on' => $selectedId !== null && $this->id === $selectedId,
        ];
    }

    /**
     * Safe unread count for inbox cards. The legacy getUnreadCountForUser()
     * relies on item-level reads which are not tracked in the current schema,
     * so we infer from helpdesk_conversation_reads (conversation-level pivot).
     */
    protected function unreadCountForInbox(): int
    {
        $userId = auth()->id();

        if (! $userId) {
            return 0;
        }

        $read = \DB::connection($this->connection)
            ->table('helpdesk_conversation_reads')
            ->where('conversation_id', $this->id)
            ->where('user_id', $userId)
            ->value('read_at');

        $lastAt = $this->last_message_at ?? $this->updated_at;

        if (! $lastAt) {
            return 0;
        }

        if ($read && Carbon::parse($read)->greaterThanOrEqualTo($lastAt)) {
            return 0;
        }

        return min(99, max(1, (int) $this->items()->where('type', 'message')->where('user_id', null)->count()));
    }

    /**
     * Calculate a tuple [status, label] for the SLA badge: ok / warn / breach.
     *
     * @return array{0: string, 1: string}
     */
    protected function slaStatusForInbox(?CarbonInterface $lastAt): array
    {
        if (! $lastAt) {
            return ['ok', '—'];
        }

        $minutes = (int) round($lastAt->diffInMinutes(now()));
        $label = match (true) {
            $minutes < 60 => $minutes.'m',
            $minutes < 1440 => intdiv($minutes, 60).'h',
            default => intdiv($minutes, 1440).'d',
        };

        return [
            $minutes < 15 ? 'ok' : ($minutes < 60 ? 'warn' : 'breach'),
            $label,
        ];
    }

    protected static function newFactory(): ConversationFactory
    {
        return new ConversationFactory;
    }
}
