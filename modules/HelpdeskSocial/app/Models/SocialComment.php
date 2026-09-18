<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSocial\Database\Factories\SocialCommentFactory;
use Modules\HelpdeskSocial\Events\SocialCommentEscalated;

class SocialComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_social_comments';

    protected $fillable = [
        'social_account_id',
        'helpdesk_conversation_id',
        'platform',
        'external_comment_id',
        'external_post_id',
        'external_parent_id',
        'external_user_id',
        'author_name',
        'author_username',
        'body',
        'intent',
        'intent_confidence',
        'urgency',
        'keywords',
        'sentiment',
        'metadata',
        'posted_at',
        'social_conversation_id',
        'social_sla_policy_id',
        'escalated_ticket_number',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'sentiment' => 'array',
            'metadata' => 'array',
            'is_hidden' => 'boolean',
            'is_spam' => 'boolean',
            'is_mention' => 'boolean',
            'intent_confidence' => 'decimal:2',
            'replied_at' => 'datetime',
            'posted_at' => 'datetime',
            'first_response_at' => 'datetime',
            'sla_response_deadline' => 'datetime',
            'sla_resolution_deadline' => 'datetime',
            'sla_response_breached' => 'boolean',
            'sla_resolution_breached' => 'boolean',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'helpdesk_conversation_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignTo(?int $userId): void
    {
        $this->forceFill(['assigned_to_user_id' => $userId])->save();
    }

    public function socialConversation(): BelongsTo
    {
        return $this->belongsTo(SocialConversation::class, 'social_conversation_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SocialSlaPolicy::class, 'social_sla_policy_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(SocialTag::class, 'helpdesk_social_comment_tag', 'social_comment_id', 'social_tag_id')
            ->withPivot('tagged_by_user_id', 'created_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SocialCommentNote::class, 'social_comment_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(SocialCommentNote::class, 'social_comment_id')->where('type', 'internal');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(SocialApprovalRequest::class, 'social_comment_id');
    }

    public function intent(): MorphOne
    {
        return $this->morphOne(SocialIntent::class, 'classifiable');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNotSpam($query)
    {
        return $query->where('is_spam', false);
    }

    public function scopeForPost($query, string $postId)
    {
        return $query->where('external_post_id', $postId);
    }

    public function scopeReplied($query)
    {
        return $query->whereNotNull('replied_at');
    }

    public function markAsReplied(string $replyBody, ?int $userId = null, ?string $externalReplyId = null, string $replyType = 'manual'): void
    {
        $updates = [
            'status' => 'replied',
            'reply_type' => $replyType,
            'reply_body' => $replyBody,
            'replied_at' => now(),
            'replied_by_user_id' => $userId,
            'external_reply_id' => $externalReplyId,
        ];

        if (! $this->first_response_at) {
            $updates['first_response_at'] = now();
            $updates['sla_response_breached'] = $this->sla_response_deadline && now()->gt($this->sla_response_deadline);
        }

        $this->forceFill($updates)->save();
    }

    public function markAsSpam(): void
    {
        $this->forceFill([
            'is_spam' => true,
            'status' => 'spam',
        ])->save();
    }

    public function markAsEscalated(?int $assignedToUserId = null): void
    {
        $this->forceFill([
            'status' => 'escalated',
            'assigned_to_user_id' => $assignedToUserId,
        ])->save();

        SocialCommentEscalated::dispatch($this);
    }

    protected static function newFactory(): SocialCommentFactory
    {
        return SocialCommentFactory::new();
    }
}
