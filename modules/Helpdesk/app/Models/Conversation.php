<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Concerns\HasMessageThread;
use Modules\Helpdesk\Database\Factories\ConversationFactory;
use Modules\Helpdesk\Events\ConversationAssigned;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;
use Modules\Helpdesk\Models\Concerns\HasCustomAttributes;
use Modules\Helpdesk\Notifications\ConversationAssignedNotification;
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
        'brand_id',
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
        'last_customer_message_at',
        'tags',
        'is_spam',
        'is_archived',
        'is_public',
        'snoozed_until',
        'snoozed_by',
        'sla_warned_at',
        'sla_policy_id',
        'sla_first_response_due_at',
        'sla_resolution_due_at',
        'sla_first_response_breached',
        'sla_resolution_breached',
        'sla_paused_at',
        'sla_paused_duration_minutes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_message_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'sla_warned_at' => 'datetime',
            'sla_first_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_first_response_breached' => 'boolean',
            'sla_resolution_breached' => 'boolean',
            'sla_paused_duration_minutes' => 'integer',
            'is_archived' => 'boolean',
            'is_spam' => 'boolean',
            'tags' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'snoozed_until' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_id', 'priority', 'assignee_id', 'closed_at', 'snoozed_until', 'customer_id'])
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
     *
     * @return HasMany<ConversationItem, $this>
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
     * Último mensaje de la conversación. Relación eager-loadable
     * (with('lastMessage')) para evitar el N+1 de getLatestMessage() en los
     * listados del inbox.
     */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(ConversationItem::class, 'conversation_id')
            ->ofMany(['created_at' => 'max', 'id' => 'max'], function ($query) {
                $query->where('type', 'message');
            });
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
     * Agents who are participants (observers) of this conversation.
     * Uses the pivot table on the helpdesk connection.
     */
    public function participants(): BelongsToMany
    {
        // User is on the default connection; we query via the helpdesk pivot table.
        $instance = (new User)->setConnection(null);

        return $this->newBelongsToMany(
            $instance->newQuery(),
            $this,
            'helpdesk_conversation_participants',
            'conversation_id',
            'user_id',
            'id',
            'id',
            'participants'
        )->withTimestamps()->withPivot('added_at');
    }

    /**
     * Add a user as a conversation participant (idempotent).
     */
    public function addParticipant(int $userId): void
    {
        $this->participants()->syncWithoutDetaching([$userId]);
    }

    /**
     * Remove a user from conversation participants.
     */
    public function removeParticipant(int $userId): void
    {
        $this->participants()->detach($userId);
    }

    /**
     * Skills required to handle this conversation (used by SkillsRoutingService).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(
            Skill::class,
            'helpdesk_conversation_skills',
            'conversation_id',
            'skill_id'
        );
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
     * ¿Sigue abierta la ventana de servicio de 24h de WhatsApp? Solo aplica al
     * canal whatsapp: fuera de ella Meta rechaza el texto libre y exige una
     * plantilla (HSM). Otros canales devuelven true (no tienen esta restricción).
     */
    public function isWhatsAppWindowOpen(): bool
    {
        if ($this->channel !== 'whatsapp') {
            return true;
        }

        return $this->last_customer_message_at !== null
            && $this->last_customer_message_at->greaterThan(now()->subHours(24));
    }

    /**
     * Scope: Get snoozed conversations (snoozed_until is set and in the future)
     */
    public function scopeSnoozed(Builder $query): Builder
    {
        return $query->whereNotNull('snoozed_until')
            ->where('snoozed_until', '>', now());
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
     * Scope: Exclude conversations currently handled by a chatbot (they stay in
     * history but out of the agent inbox until the bot hands off to an agent).
     *
     * `metadata` is null on any conversation that was never touched by
     * chatbot/bot-handoff logic (the common case). `NOT JSON_CONTAINS(NULL, ...)`
     * evaluates to NULL in SQL — not true — so a plain whereJsonDoesntContain()
     * silently dropped every conversation with null metadata from the inbox.
     * The explicit whereNull() branch keeps them included.
     */
    public function scopeWithoutActiveBot($query)
    {
        return $query->where(
            fn ($q) => $q->whereNull('metadata')
                ->orWhereJsonDoesntContain('metadata->handled_by_bot', true)
        );
    }

    /**
     * Scope: Only conversations the chatbot is (or was) handling without a handoff.
     */
    public function scopeHandledByBot($query)
    {
        return $query->whereJsonContains('metadata->handled_by_bot', true);
    }

    /**
     * Flag this conversation as handled by the chatbot, hiding it from the agent
     * inbox while the bot is in control.
     */
    public function markHandledByBot(): void
    {
        $this->forceFill([
            'metadata' => array_merge($this->metadata ?? [], ['handled_by_bot' => true]),
        ])->save();
    }

    /**
     * Release the conversation from the bot (handoff to an agent), so it appears
     * in the inbox again.
     */
    public function releaseFromBot(): void
    {
        $metadata = $this->metadata ?? [];

        if (($metadata['handled_by_bot'] ?? false) === false) {
            return;
        }

        $metadata['handled_by_bot'] = false;
        $this->forceFill(['metadata' => $metadata])->save();
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
     * Scope: restrict to inboxes the given agent may access.
     *
     * Reuses the inbox-isolation mechanism of ConversationPolicy::canAccessInbox():
     * managers (helpdesk.manage) see everything; agents only see conversations in
     * inboxes assigned via AgentInboxCapacity. A restricted agent with no assigned
     * inboxes sees nothing (fail-closed).
     */
    public function scopeForAgent(Builder $query, User $user): Builder
    {
        if ($user->hasPermissionTo('helpdesk.manage')) {
            return $query;
        }

        $inboxIds = AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->pluck('inbox_id');

        return $query->whereIn('inbox_id', $inboxIds);
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

        if ($userId && $assignee = User::find($userId)) {
            ConversationAssigned::dispatch($this, $assignee, auth()->id());
        }

        return $this;
    }

    /**
     * Assign the conversation to a group (no individual agent): broadcast an inbox
     * change and notify every group member so they see it in real time.
     */
    public function assignToGroup($groupId)
    {
        $this->update([
            'group_id' => $groupId,
            'assigned_at' => now(),
        ]);

        $group = Group::with('users')->find($groupId);

        if (! $group) {
            return $this;
        }

        foreach ($group->users as $member) {
            event(new InboxItemChanged($this->id, $member->id, 'assigned'));
            $member->notify(new ConversationAssignedNotification($this));
        }

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
     * Public so controllers can trigger it after actions that don't go through
     * a model method (archive/unarchive, spam, snooze, delete, restore).
     */
    public function broadcastInboxChanged(string $changeType): void
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
        // Usa la relación precargada (with('lastMessage')) si está disponible,
        // evitando una query por fila en los listados del inbox.
        if ($this->relationLoaded('lastMessage')) {
            return $this->lastMessage;
        }

        // The items() relation defines orderBy('created_at', 'asc'); we must
        // reorder() that out before chaining latest() or MySQL keeps the first
        // ASC clause and we get the *oldest* message instead of the newest.
        return $this->messages()->reorder()->latest('created_at')->latest('id')->first();
    }

    /**
     * Returns channel display info: icon, color, label.
     * Prefers the inbox's own icon/color when the relation is loaded.
     *
     * @return array{icon: string, color: string, label: string}
     */
    public function getChannelInfoAttribute(): array
    {
        $channelDefaults = match ($this->channel ?? 'web') {
            'whatsapp' => ['icon' => 'fab fa-whatsapp', 'color' => '#25D366', 'label' => 'WhatsApp'],
            'facebook' => ['icon' => 'fab fa-facebook-messenger', 'color' => '#0084FF', 'label' => 'Messenger'],
            'instagram' => ['icon' => 'fab fa-instagram', 'color' => '#E1306C', 'label' => 'Instagram'],
            default => ['icon' => 'fas fa-comment-dots', 'color' => '#6c757d', 'label' => 'Web'],
        };

        $inbox = $this->relationLoaded('inbox') ? $this->inbox : null;

        return [
            'icon' => ($inbox?->icon) ?? $channelDefaults['icon'],
            'color' => ($inbox?->color) ?? $channelDefaults['color'],
            'label' => $channelDefaults['label'],
        ];
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

        $inbox = $this->relationLoaded('inbox') ? $this->inbox : null;
        if ($inbox?->icon !== null) {
            $ch['icon'] = $inbox->icon;
        }

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

        $latest = $this->getLatestMessage();
        $latestBody = $latest?->body ?? '';
        $cleanBody = trim(strip_tags((string) $latestBody));

        // If body is empty but there are attachments, show a friendly attachment preview.
        if ($cleanBody === '' && $latest && ! empty($latest->attachment_urls)) {
            $atts = $latest->attachment_urls;
            $first = is_array($atts) ? ($atts[0] ?? null) : null;
            $firstObj = is_array($first) ? $first : ['url' => is_string($first) ? $first : ''];
            $url = $firstObj['url'] ?? '';
            $attName = (string) ($firstObj['name'] ?? '');
            $mime = (string) ($firstObj['mime_type'] ?? $firstObj['mime'] ?? '');
            $ext = $url ? strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) : '';
            $mimeType = explode('/', $mime)[0] ?? '';

            // Voice notes — names like "voz-", "audio-", "voice-" or "recording-"
            // are recorded as audio even if the mime/ext is webm (browser bug).
            $looksLikeVoiceNote = (bool) preg_match('/^(voz|audio|voice|recording|grabaci[oó]n)[-_]/iu', $attName);

            $type = match (true) {
                $looksLikeVoiceNote => 'audio',
                $mimeType === 'audio' => 'audio',
                $mimeType === 'image' => 'image',
                $mimeType === 'video' && in_array($ext, ['mp4', 'mov', 'avi', 'mkv'], true) => 'video',
                in_array($ext, ['mp3', 'ogg', 'wav', 'oga', 'm4a', 'aac', 'opus'], true) => 'audio',
                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'], true) => 'image',
                in_array($ext, ['mp4', 'mov', 'avi', 'mkv'], true) => 'video',
                $ext === 'webm' => 'audio', // most webm in helpdesk are voice notes
                default => 'file',
            };

            $count = is_array($atts) ? count($atts) : 1;
            $cleanBody = match ($type) {
                'audio' => '🎵 Audio'.($count > 1 ? ' · '.$count.' archivos' : ''),
                'image' => '📷 Imagen'.($count > 1 ? ' · '.$count.' archivos' : ''),
                'video' => '🎥 Vídeo'.($count > 1 ? ' · '.$count.' archivos' : ''),
                default => '📎 '.($count > 1 ? $count.' archivos' : 'Archivo'),
            };
        }

        $preview = $cleanBody !== '' ? $cleanBody : ($this->subject ?? '');
        $preview = mb_strimwidth($preview, 0, 90, '…');

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
            'is_snoozed' => $this->snoozed_until !== null && $this->snoozed_until->isFuture(),
            'snoozed_until' => $this->snoozed_until?->toIso8601String(),
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

        // Prefiere el read precargado (with('reads')) para evitar una query por fila.
        if ($this->relationLoaded('reads')) {
            $read = $this->reads->firstWhere('user_id', $userId)?->read_at;
        } else {
            $read = \DB::connection($this->connection)
                ->table('helpdesk_conversation_reads')
                ->where('conversation_id', $this->id)
                ->where('user_id', $userId)
                ->value('read_at');
        }

        $lastAt = $this->last_message_at ?? $this->updated_at;

        if (! $lastAt) {
            return 0;
        }

        if ($read && Carbon::parse($read)->greaterThanOrEqualTo($lastAt)) {
            return 0;
        }

        // Prefiere el count precargado (withCount as incoming_messages_count).
        $incoming = $this->incoming_messages_count
            ?? $this->items()->where('type', 'message')->whereNull('user_id')->count();

        return min(99, max(1, (int) $incoming));
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
        // ::new() (y no `new ConversationFactory`) para que se aplique configure()
        // — el afterCreating que asigna el status abierto por defecto en tests.
        return ConversationFactory::new();
    }
}
