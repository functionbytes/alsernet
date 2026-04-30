<?php

namespace Modules\Chat\Models\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Modules\Chat\Database\Factories\ConversationFactory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\ActivityMessageHandler;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\CsatSurvey;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Models\Groups\Group;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Sla\SlaAppliedConversation;
use Modules\Chat\Models\Sla\SlaPolicy;
use Modules\Chat\Models\Teams\Team;

class Conversation extends Model
{
    use ActivityMessageHandler, BelongsToAccount, HasFactory, Searchable, SoftDeletes;

    protected $table = 'chat_conversations';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ConversationFactory::new();
    }

    protected $fillable = [
        'account_id',
        'inbox_id',
        'customer_id',
        'language',
        'team_id',
        'group_id',
        'sla_id',
        'assignee_id',
        'status_id',
        'priority_id',
        'subject',
        'is_archived',
        'custom_attributes',
        'cached_label_list',
        'first_response_at',
        'assigned_at',
        'resolved_at',
        'closed_at',
        'first_response_sla_breached',
        'resolution_sla_breached',
        'last_activity_at',
        'last_message_at',
        'snoozed_until',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'first_response_sla_breached' => 'boolean',
            'resolution_sla_breached' => 'boolean',
            'custom_attributes' => 'array',
            'first_response_at' => 'datetime',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_message_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    /**
     * Get the account that owns the conversation.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the customer that owns the conversation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the inbox for this conversation.
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * Get the customer inbox for this conversation.
     */
    public function customerInbox(): HasOne
    {
        return $this->hasOne(CustomerInbox::class, 'customer_id', 'customer_id')
            ->where('inbox_id', $this->inbox_id);
    }

    /**
     * Get the chat session that owns the conversation.
     */
    public function conversationSession(): BelongsTo
    {
        return $this->belongsTo(ConversationSession::class, 'session_id');
    }

    /**
     * Get the team assigned to the conversation.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the assigned user (agent).
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the status of the conversation.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    /**
     * Get the priority of the conversation.
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(ConversationPriority::class, 'priority_id');
    }

    /**
     * Get the SLA policy for the conversation.
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_id');
    }

    /**
     * Get the SLA tracking record for this conversation.
     */
    public function slaTracking(): HasOne
    {
        return $this->hasOne(SlaAppliedConversation::class, 'conversation_id');
    }

    /**
     * Get the CSAT survey for this conversation.
     */
    public function csatSurvey(): HasOne
    {
        return $this->hasOne(CsatSurvey::class, 'conversation_id');
    }

    /**
     * Get the group assigned to the conversation.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * Get all messages for this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id');
    }

    /**
     * Get only incoming messages.
     */
    public function incomingMessages(): HasMany
    {
        return $this->messages()->where('message_type', 'incoming');
    }

    /**
     * Get only outgoing messages.
     */
    public function outgoingMessages(): HasMany
    {
        return $this->messages()->where('message_type', 'outgoing');
    }

    /**
     * Get non-private (chat) messages.
     */
    public function chatMessages(): HasMany
    {
        return $this->messages()->where('private', false);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Check if conversation is open.
     */
    public function isOpen(): bool
    {
        return $this->status?->slug === 'open';
    }

    /**
     * Check if conversation is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status?->slug === 'resolved';
    }

    /**
     * Check if conversation is closed.
     */
    public function isClosed(): bool
    {
        return $this->status?->slug === 'closed';
    }

    /**
     * Check if conversation is snoozed.
     */
    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    /**
     * Check if conversation is postponed (alias for snoozed).
     */
    public function isPostponed(): bool
    {
        return $this->isSnoozed();
    }

    /**
     * Mark conversation as open.
     */
    public function markAsOpen(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        $this->update([
            'status_id' => $openStatus?->id,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Mark conversation as resolved.
     */
    public function markAsResolved(): void
    {
        $resolvedStatus = ConversationStatus::where('slug', 'resolved')->first();

        $this->update([
            'status_id' => $resolvedStatus?->id,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark conversation as closed.
     */
    public function markAsClosed(): void
    {
        $closedStatus = ConversationStatus::where('slug', 'closed')->first();

        $this->update([
            'status_id' => $closedStatus?->id,
            'closed_at' => now(),
        ]);
    }

    /**
     * Add labels to conversation.
     */
    public function addLabels(array $labelTitles): void
    {
        $currentLabels = $this->cached_label_list ? explode(',', $this->cached_label_list) : [];
        $newLabels = array_unique(array_merge($currentLabels, $labelTitles));
        $this->update(['cached_label_list' => implode(',', $newLabels)]);
    }

    /**
     * Remove labels from conversation.
     */
    public function removeLabels(array $labelTitles): void
    {
        if (! $this->cached_label_list) {
            return;
        }

        $currentLabels = explode(',', $this->cached_label_list);
        $newLabels = array_diff($currentLabels, $labelTitles);
        $this->update(['cached_label_list' => implode(',', $newLabels)]);
    }

    /**
     * Get array of label titles for this conversation.
     */
    public function getLabels(): array
    {
        if (! $this->cached_label_list) {
            return [];
        }

        return explode(',', $this->cached_label_list);
    }

    /**
     * Close the conversation
     */
    public function close(): void
    {
        $closedStatus = ConversationStatus::where('slug', 'closed')->first();

        $this->update([
            'status_id' => $closedStatus?->id,
            'closed_at' => now(),
        ]);
    }

    /**
     * Reopen the conversation
     */
    public function reopen(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        $this->update([
            'status_id' => $openStatus?->id,
            'closed_at' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Archive the conversation
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive the conversation
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Snooze the conversation until a specific date/time.
     */
    public function snooze(\DateTime $until): void
    {
        $snoozedStatus = ConversationStatus::where('slug', 'snoozed')->first();

        $this->update([
            'snoozed_until' => $until,
            'status_id' => $snoozedStatus?->id,
        ]);
    }

    /**
     * Unsnooze the conversation.
     */
    public function unsnooze(): void
    {
        $openStatus = ConversationStatus::where('slug', 'open')->first();

        $this->update([
            'snoozed_until' => null,
            'status_id' => $openStatus?->id,
        ]);
    }

    /**
     * Scope: Filter by status slug using JOIN (efficient).
     */
    public function scopeWithStatusSlug(Builder $query, string $slug): Builder
    {
        return $query->join('chat_conversation_statuses as cs', 'chat_conversations.status_id', '=', 'cs.id')
            ->where('cs.slug', $slug)
            ->select('chat_conversations.*');
    }

    /**
     * Scope: Filter by priority slug using JOIN (efficient).
     */
    public function scopeWithPrioritySlug(Builder $query, string $slug): Builder
    {
        return $query->join('chat_conversation_priorities as cp', 'chat_conversations.priority_id', '=', 'cp.id')
            ->where('cp.slug', $slug)
            ->select('chat_conversations.*');
    }

    /**
     * Scope: Filter by assigned user.
     */
    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assignee_id', $userId);
    }

    /**
     * Scope: Filter unassigned conversations.
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assignee_id');
    }

    /**
     * Scope: Filter conversations where user is mentioned.
     */
    public function scopeMentioning(Builder $query, int $userId, ?string $userName = null): Builder
    {
        return $query->whereHas('messages', function ($messageQuery) use ($userId, $userName) {
            $messageQuery->where('content', 'like', '%@'.$userId.'%');
            if ($userName) {
                $messageQuery->orWhere('content', 'like', '%@'.$userName.'%');
            }
        });
    }

    /**
     * Scope: Filter unattended conversations (no agent replies, not resolved).
     */
    public function scopeUnattended(Builder $query): Builder
    {
        return $query->whereHas('status', fn ($q) => $q->where('slug', '!=', 'resolved'))
            ->whereDoesntHave('messages', function ($messageQuery) {
                $messageQuery->where('message_type', 'outgoing')
                    ->where('sender_type', User::class);
            });
    }

    /**
     * Scope: Filter by label in cached_label_list.
     */
    public function scopeWithLabelInCachedList(Builder $query, string $label): Builder
    {
        return $query->where('cached_label_list', 'like', '%'.$label.'%');
    }

    /**
     * Scope: Filter by inbox.
     */
    public function scopeByInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    /**
     * Scope: Filter by team.
     */
    public function scopeByTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope: Filter by date range on created_at.
     */
    public function scopeCreatedBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope: Filter by last activity date range.
     */
    public function scopeLastActivityBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('last_activity_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('last_activity_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope: Search across subject, customer fields and message content.
     *
     * Uses FULLTEXT MATCH AGAINST for subject when term >= 3 chars (MariaDB minimum),
     * falls back to LIKE for shorter terms or non-fulltext columns.
     */
    public function scopeTextSearch(Builder $query, string $searchTerm): Builder
    {
        $useFulltext = strlen($searchTerm) >= 3;

        return $query->where(function ($q) use ($searchTerm, $useFulltext) {
            if ($useFulltext) {
                $q->whereRaw('MATCH(chat_conversations.subject) AGAINST(? IN BOOLEAN MODE)', [$searchTerm.'*']);
            } else {
                $q->where('subject', 'like', '%'.$searchTerm.'%');
            }

            $q->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                $customerQuery->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('phone_number', 'like', '%'.$searchTerm.'%');
            });
        });
    }

    /**
     * Scope: Get aggregated filter counts for a single account in one query.
     * Returns a single row with all counts as columns, including mentions and unattended.
     */
    public function scopeAggregatedFilterCounts(Builder $query, int $userId, ?string $userName = null): Builder
    {
        $table = $this->getTable();
        $messagesTable = (new ConversationMessage)->getTable();

        $userClass = User::class;
        $mentionByIdPattern = '%@'.$userId.'%';

        // Build the mention EXISTS subquery — OR in username if provided.
        $mentionCondition = 'm.content LIKE ?';
        $mentionBindings = [$mentionByIdPattern];

        if ($userName !== null) {
            $mentionCondition = '(m.content LIKE ? OR m.content LIKE ?)';
            $mentionBindings = [$mentionByIdPattern, '%@'.$userName.'%'];
        }

        $mentionSubquery = "EXISTS (
            SELECT 1 FROM {$messagesTable} m
            WHERE m.conversation_id = {$table}.id
              AND m.deleted_at IS NULL
              AND {$mentionCondition}
        )";

        $unattendedSubquery = "NOT EXISTS (
            SELECT 1 FROM {$messagesTable} m
            WHERE m.conversation_id = {$table}.id
              AND m.deleted_at IS NULL
              AND m.message_type = 'outgoing'
              AND m.sender_type = ?
        ) AND cs.slug != 'resolved'";

        return $query
            ->leftJoin('chat_conversation_statuses as cs', "{$table}.status_id", '=', 'cs.id')
            ->leftJoin('chat_conversation_priorities as cp', "{$table}.priority_id", '=', 'cp.id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN cs.slug = 'open' THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN cs.slug = 'resolved' THEN 1 ELSE 0 END) as resolved_count")
            ->selectRaw("SUM(CASE WHEN cs.slug = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN cs.slug = 'closed' THEN 1 ELSE 0 END) as closed_count")
            ->selectRaw("SUM(CASE WHEN {$table}.assignee_id = ? THEN 1 ELSE 0 END) as mine_count", [$userId])
            ->selectRaw("SUM(CASE WHEN {$table}.assignee_id IS NULL THEN 1 ELSE 0 END) as unassigned_count")
            ->selectRaw("SUM(CASE WHEN cp.slug = 'urgent' THEN 1 ELSE 0 END) as urgent_count")
            ->selectRaw("SUM(CASE WHEN cp.slug = 'high' THEN 1 ELSE 0 END) as high_count")
            ->selectRaw("SUM(CASE WHEN cp.slug = 'medium' THEN 1 ELSE 0 END) as medium_count")
            ->selectRaw("SUM(CASE WHEN cp.slug = 'low' THEN 1 ELSE 0 END) as low_count")
            ->selectRaw("SUM(CASE WHEN {$mentionSubquery} THEN 1 ELSE 0 END) as mentions_count", $mentionBindings)
            ->selectRaw("SUM(CASE WHEN {$unattendedSubquery} THEN 1 ELSE 0 END) as unattended_count", [$userClass]);
    }

    /**
     * Compute per-label conversation counts using SQL instead of a PHP loop.
     *
     * Returns an array keyed by label title with integer counts.
     * Uses a single GROUP BY query on cached_label_list (comma-separated).
     *
     * Usage: Conversation::forAccount($id)->labelCountsForAccount($id, $labels->pluck('name')->all())
     *
     * @param  string[]  $labelTitles
     * @return array<string, int>
     */
    public static function labelCountsForAccount(int $accountId, array $labelTitles): array
    {
        if (empty($labelTitles)) {
            return [];
        }

        $table = (new self)->getTable();

        // Fetch all non-empty cached_label_list values for the account.
        $cachedLists = DB::table($table)
            ->where('account_id', $accountId)
            ->whereNotNull('cached_label_list')
            ->where('cached_label_list', '!=', '')
            ->whereNull('deleted_at')
            ->pluck('cached_label_list');

        // Count per label title using PHP — kept here so the SQL fetch is a single query.
        // The heavy lifting moves from "load all conversations + PHP loop" to one targeted query.
        $counts = array_fill_keys($labelTitles, 0);

        foreach ($cachedLists as $list) {
            foreach (explode(',', $list) as $title) {
                $title = trim($title);
                if (isset($counts[$title])) {
                    $counts[$title]++;
                }
            }
        }

        return $counts;
    }

    /**
     * Scope: Common eager loads for conversation listings.
     */
    public function scopeWithCommonRelations(Builder $query): Builder
    {
        return $query->with([
            'customer',
            'inbox.channel',
            'assignee',
            'team',
            'status',
            'priority',
            'messages' => fn ($q) => $q->latest()->limit(1),
        ]);
    }
}
