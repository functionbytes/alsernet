<?php

namespace Modules\HelpdeskChat\Models\Conversations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Concerns\ActivityMessageHandler;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Customers\Customer;
use Modules\HelpdeskChat\Models\Teams\Team;

class Conversation extends Model
{
    use ActivityMessageHandler, HasFactory, Searchable, SoftDeletes;

    protected $table = 'helpdesk_conversations';

    protected $fillable = [
        'account_id',
        'inbox_id',
        'contact_id',
        'contact_inbox_id',
        'helpdesk_customer_id',
        'language',
        'team_id',
        'helpdesk_group_id',
        'sla_policy_id',
        'assignee_id',
        'status_id',
        'status',
        'subject',
        'priority',
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
        return $this->belongsTo(Customer::class, 'helpdesk_customer_id');
    }

    /**
     * Get the inbox for this conversation.
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * Get the contact for this conversation.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact inbox for this conversation.
     */
    public function contactInbox(): BelongsTo
    {
        return $this->belongsTo(ContactInbox::class);
    }

    /**
     * Get the chat session that owns the conversation.
     */
    public function conversationSession(): BelongsTo
    {
        return $this->belongsTo(ConversationSession::class);
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
     * Get the group assigned to the conversation.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'helpdesk_group_id');
    }

    /**
     * Get the tags associated with the conversation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ConversationTag::class,
            'helpdesk_conversation_tag',
            'conversation_id',
            'helpdesk_conversation_tag_id'
        )->withTimestamps();
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
        return $this->status === 'open';
    }

    /**
     * Check if conversation is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if conversation is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Mark conversation as open.
     */
    public function markAsOpen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Mark conversation as resolved.
     */
    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark conversation as closed.
     */
    public function markAsClosed(): void
    {
        $this->update([
            'status' => 'closed',
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
        // Find a closed status (or create logic to set one)
        $closedStatus = ConversationStatus::where('is_open', false)->first();

        $this->update([
            'status_id' => $closedStatus?->id,
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Reopen the conversation
     */
    public function reopen(): void
    {
        // Find an open status
        $openStatus = ConversationStatus::where('is_open', true)->first();

        $this->update([
            'status_id' => $openStatus?->id,
            'status' => 'open',
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
        $this->update([
            'snoozed_until' => $until,
            'status' => 'snoozed',
        ]);
    }

    /**
     * Unsnooze the conversation.
     */
    public function unsnooze(): void
    {
        $this->update([
            'snoozed_until' => null,
            'status' => 'open',
        ]);
    }
}
