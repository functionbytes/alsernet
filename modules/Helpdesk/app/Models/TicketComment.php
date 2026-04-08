<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketComment extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_comments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'author_id',
        'body',
        'html_body',
        'is_internal',
        'attachment_urls',
        'edited_by',
        'edited_at',
        'edit_reason',
        'mentioned_user_ids',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'attachment_urls' => 'array',
            'mentioned_user_ids' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────────────────────

    /**
     * Get the ticket this comment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the agent who wrote this comment (cross-database relationship)
     */
    public function user()
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
        );
    }

    /**
     * Get the customer who wrote this comment
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'author_id');
    }

    /**
     * Get the agent who edited this comment (cross-database relationship)
     */
    public function editor()
    {
        if (! $this->edited_by) {
            return null;
        }

        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'edited_by',
            'id',
            'editor'
        );
    }

    /**
     * Get the email message associated with this comment
     */
    public function mail(): BelongsTo
    {
        return $this->belongsTo(TicketMail::class, 'ticket_comment_id');
    }

    /**
     * Get mentioned users (via mentioned_user_ids array)
     */
    public function mentionedUsers(): BelongsToMany
    {
        // This uses a custom pivot, querying from the array
        $userIds = $this->mentioned_user_ids ?? [];

        return User::query()
            ->setConnection('mysql')
            ->whereIn('id', $userIds);
    }

    // ────────────────────────────────────────────────────────────────
    // Query Scopes
    // ────────────────────────────────────────────────────────────────

    /**
     * Get only internal comments
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Get only external comments (visible to customers)
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Get comments from agents
     */
    public function scopeFromAgent($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Get comments from customers
     */
    public function scopeFromCustomer($query)
    {
        return $query->whereNotNull('author_id');
    }

    /**
     * Get comments with attachments
     */
    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachment_urls')
            ->where('attachment_urls', '!=', '[]');
    }

    /**
     * Get comments that have been edited
     */
    public function scopeEdited($query)
    {
        return $query->whereNotNull('edited_at');
    }

    /**
     * Order by newest first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Order by oldest first
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ────────────────────────────────────────────────────────────────
    // Accessors & Type Checking
    // ────────────────────────────────────────────────────────────────

    /**
     * Check if comment is from a customer
     */
    public function isFromCustomer(): bool
    {
        return $this->author_id !== null && $this->user_id === null;
    }

    /**
     * Check if comment is from an agent
     */
    public function isFromAgent(): bool
    {
        return $this->user_id !== null && $this->author_id === null;
    }

    /**
     * Check if comment is internal (agent-only)
     */
    public function isInternal(): bool
    {
        return $this->is_internal === true;
    }

    /**
     * Check if comment is visible to customers
     */
    public function isExternal(): bool
    {
        return $this->is_internal === false;
    }

    /**
     * Check if comment has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachment_urls);
    }

    /**
     * Check if comment has been edited
     */
    public function hasBeenEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /**
     * Get number of attachments
     */
    public function getAttachmentCountAttribute(): int
    {
        return count($this->attachment_urls ?? []);
    }

    /**
     * Get sender name (customer or agent or system)
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isFromAgent()) {
            return $this->user?->name ?? 'Sistema';
        }

        if ($this->isFromCustomer()) {
            return $this->author?->name ?? 'Cliente';
        }

        return 'Sistema';
    }

    /**
     * Get sender avatar URL
     */
    public function getSenderAvatarAttribute(): ?string
    {
        if ($this->isFromAgent()) {
            return $this->user?->avatar_url;
        }

        if ($this->isFromCustomer()) {
            return $this->author?->avatar_url;
        }

        return null;
    }

    /**
     * Get comment content (prefer HTML over plain text)
     */
    public function getContentAttribute(): string
    {
        return $this->html_body ?? $this->body ?? '';
    }

    /**
     * Get plain text version (strips HTML)
     */
    public function getPlainTextAttribute(): string
    {
        $content = $this->body ?? $this->html_body ?? '';

        if ($this->html_body) {
            $content = strip_tags($this->html_body);
        }

        return $content;
    }

    /**
     * Get summary (first 100 chars)
     */
    public function getSummaryAttribute(): string
    {
        $text = $this->getPlainTextAttribute();

        return strlen($text) > 100 ? substr($text, 0, 100).'...' : $text;
    }

    // ────────────────────────────────────────────────────────────────
    // Actions
    // ────────────────────────────────────────────────────────────────

    /**
     * Mark comment as edited
     */
    public function markAsEdited(?int $editedBy = null, string $reason = ''): void
    {
        $this->update([
            'edited_by' => $editedBy ?? auth()->id(),
            'edited_at' => now(),
            'edit_reason' => $reason ?: null,
        ]);
    }

    /**
     * Add @mentions to comment
     */
    public function addMentions(array $userIds): void
    {
        $current = $this->mentioned_user_ids ?? [];
        $merged = array_unique(array_merge($current, $userIds));

        $this->update(['mentioned_user_ids' => $merged]);
    }

    /**
     * Remove @mentions from comment
     */
    public function removeMentions(array $userIds): void
    {
        $current = $this->mentioned_user_ids ?? [];
        $filtered = array_diff($current, $userIds);

        $this->update(['mentioned_user_ids' => ! empty($filtered) ? $filtered : null]);
    }

    /**
     * Get all mentioned user IDs
     */
    public function getMentionedUserIds(): array
    {
        return $this->mentioned_user_ids ?? [];
    }

    /**
     * Add attachments to comment
     */
    public function addAttachments(array $urls): void
    {
        $current = $this->attachment_urls ?? [];
        $merged = array_unique(array_merge($current, $urls));

        $this->update(['attachment_urls' => $merged]);
    }

    /**
     * Remove attachment from comment
     */
    public function removeAttachment(string $url): void
    {
        $current = $this->attachment_urls ?? [];
        $filtered = array_diff($current, [$url]);

        $this->update(['attachment_urls' => ! empty($filtered) ? $filtered : null]);
    }

    /**
     * Notify mentioned users about this comment
     */
    public function notifyMentionedUsers(): void
    {
        $userIds = $this->getMentionedUserIds();

        if (empty($userIds)) {
            return;
        }

        // Load user models
        $users = User::query()
            ->setConnection('mysql')
            ->whereIn('id', $userIds)
            ->get();

        foreach ($users as $user) {
            // Send notification (implement based on your notification system)
            // Notification::send($user, new MentionedInTicketComment($this->ticket, $this));
        }
    }

    /**
     * Make this comment visible to customer
     */
    public function makeExternal(): void
    {
        $this->update(['is_internal' => false]);
    }

    /**
     * Make this comment internal/private
     */
    public function makeInternal(): void
    {
        $this->update(['is_internal' => true]);
    }

    // ────────────────────────────────────────────────────────────────
    // Validation
    // ────────────────────────────────────────────────────────────────

    /**
     * Validate comment before save
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $comment) {
            // Ensure at least one of user_id or author_id is set
            if (! $comment->user_id && ! $comment->author_id) {
                throw new \InvalidArgumentException('Comment must have either user_id or author_id');
            }

            // Ensure both are not set simultaneously
            if ($comment->user_id && $comment->author_id) {
                throw new \InvalidArgumentException('Comment cannot have both user_id and author_id');
            }

            // Ensure content exists
            if (! $comment->body && ! $comment->html_body) {
                throw new \InvalidArgumentException('Comment must have either body or html_body');
            }
        });
    }
}
