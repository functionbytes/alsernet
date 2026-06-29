<?php

namespace Modules\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class Mention extends Model
{
    use HasFactory;

    protected $table = 'social_mentions';

    protected $fillable = [
        'account_id',
        'post_id',
        'social_account_id',
        'listening_keyword_id',
        'type',
        'external_id',
        'external_parent_id',
        'author_id',
        'author_username',
        'author_name',
        'author_avatar',
        'content',
        'media_url',
        'external_url',
        'is_read',
        'is_archived',
        'is_replied',
        'sentiment',
        'reply_content',
        'replied_by',
        'replied_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the account that owns the mention
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the post this mention belongs to
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the social account this mention came from
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Get the user who replied to this mention
     */
    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * Get the listening keyword that found this mention
     */
    public function listeningKeyword(): BelongsTo
    {
        return $this->belongsTo(SocialListeningKeyword::class, 'listening_keyword_id');
    }

    /**
     * Scope: Unread mentions
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Read mentions
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Not archived
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Archived
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: Replied
     */
    public function scopeReplied($query)
    {
        return $query->where('is_replied', true);
    }

    /**
     * Scope: Not replied
     */
    public function scopeNotReplied($query)
    {
        return $query->where('is_replied', false);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by network
     */
    public function scopeFromNetwork($query, string $network)
    {
        return $query->whereHas('socialAccount', function ($q) use ($network) {
            $q->where('network', $network);
        });
    }

    /**
     * Mark mention as read
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark mention as unread
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Archive mention
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive mention
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Mark as replied
     */
    public function markAsReplied(User $user, string $replyContent): void
    {
        $this->update([
            'is_replied' => true,
            'replied_by' => $user->id,
            'reply_content' => $replyContent,
            'replied_at' => now(),
            'is_read' => true, // Auto-mark as read when replied
        ]);
    }

    /**
     * Get network icon class
     */
    public function getNetworkIconAttribute(): string
    {
        return match ($this->socialAccount->network->value) {
            'facebook' => 'ti-brand-facebook text-primary',
            'instagram' => 'ti-brand-instagram text-danger',
            'twitter' => 'ti-brand-x text-dark',
            'linkedin' => 'ti-brand-linkedin text-info',
            default => 'ti-share',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'comment' => 'ti-message-circle',
            'mention' => 'ti-at',
            'message' => 'ti-mail',
            'reply' => 'ti-corner-up-left',
            'share' => 'ti-share',
            default => 'ti-message',
        };
    }

    /**
     * Get sentiment color
     */
    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'positive' => 'success',
            'negative' => 'danger',
            'neutral' => 'secondary',
            default => 'secondary',
        };
    }
}
