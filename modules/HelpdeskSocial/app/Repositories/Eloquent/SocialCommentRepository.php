<?php

namespace Modules\HelpdeskSocial\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialCommentRepositoryInterface;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentRepository implements SocialCommentRepositoryInterface
{
    public function find(int $id): ?SocialComment
    {
        return SocialComment::find($id);
    }

    public function findByExternalId(string $platform, string $externalId): ?SocialComment
    {
        return SocialComment::where('platform', $platform)
            ->where('external_comment_id', $externalId)
            ->first();
    }

    public function create(array $data): SocialComment
    {
        return SocialComment::create($data);
    }

    public function update(SocialComment $comment, array $data): SocialComment
    {
        $comment->update($data);

        return $comment->fresh();
    }

    public function getPendingByAccount(int $accountId): Collection
    {
        return SocialComment::where('social_account_id', $accountId)
            ->pending()
            ->notSpam()
            ->get();
    }

    public function getInbox(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = SocialComment::with(['socialAccount', 'intent'])
            ->notSpam();

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['intent'])) {
            $query->where('intent', $filters['intent']);
        }

        if (! empty($filters['account_id'])) {
            $query->where('social_account_id', $filters['account_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    public function getStats(?string $platform = null, ?int $accountId = null): array
    {
        $query = SocialComment::query();

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($accountId) {
            $query->where('social_account_id', $accountId);
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'replied' => (clone $query)->whereNotNull('replied_at')->count(),
            'auto_replied' => (clone $query)->where('reply_type', 'auto')->count(),
            'escalated' => (clone $query)->where('status', 'escalated')->count(),
            'spam' => (clone $query)->where('is_spam', true)->count(),
            'intents' => (clone $query)
                ->selectRaw('intent, count(*) as count')
                ->whereNotNull('intent')
                ->groupBy('intent')
                ->pluck('count', 'intent'),
        ];
    }

    public function markAsReplied(SocialComment $comment, string $replyBody, ?int $userId = null, ?string $externalReplyId = null, string $replyType = 'manual'): void
    {
        $comment->update([
            'status' => 'replied',
            'reply_type' => $replyType,
            'reply_body' => $replyBody,
            'replied_at' => now(),
            'replied_by_user_id' => $userId,
            'external_reply_id' => $externalReplyId,
        ]);
    }

    public function markAsSpam(SocialComment $comment): void
    {
        $comment->update([
            'is_spam' => true,
            'status' => 'spam',
        ]);
    }

    public function markAsEscalated(SocialComment $comment): void
    {
        $comment->update([
            'status' => 'escalated',
        ]);
    }
}
