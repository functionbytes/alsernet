<?php

namespace Modules\Blog\Services;

use Modules\Blog\Enums\CommentStatus;
use Modules\Blog\Events\BlogCommentCreated;
use Modules\Blog\Events\CommentApproved;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostComment;

class CommentService
{
    private const SPAM_KEYWORDS = [
        'casino', 'poker', 'viagra', 'cialis', 'payday loan',
        'buy cheap', 'free money', 'click here', 'earn money fast',
        'weight loss', 'make money online',
    ];

    public function submitComment(BlogPost $post, array $data): BlogPostComment
    {
        $isSpam = $this->detectSpam($data);

        $parentId = $this->resolveParentId($data['parent_id'] ?? null, $post->id);

        $comment = BlogPostComment::create([
            'blog_post_id' => $post->id,
            'parent_id' => $parentId,
            'author_name' => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'content' => function_exists('clean_html') ? clean_html($data['content']) : strip_tags($data['content']),
            'status' => $isSpam ? CommentStatus::Spam : CommentStatus::Pending,
            'ip_address' => $data['ip_address'] ?? null,
        ]);

        if (! $isSpam) {
            BlogCommentCreated::dispatch($comment);
        }

        return $comment;
    }

    public function approveComment(BlogPostComment $comment): void
    {
        $comment->update(['status' => CommentStatus::Approved]);

        CommentApproved::dispatch($comment);
    }

    public function markAsSpam(BlogPostComment $comment): void
    {
        $comment->update(['status' => CommentStatus::Spam]);
    }

    public function deleteComment(BlogPostComment $comment): void
    {
        $comment->delete();
    }

    public function detectSpam(array $data): bool
    {
        if (! empty($data['website'])) {
            return true;
        }

        $content = $data['content'] ?? '';

        $urlCount = preg_match_all('/https?:\/\/\S+/i', $content);
        if ($urlCount > 3) {
            return true;
        }

        $lowerContent = mb_strtolower($content);
        foreach (self::SPAM_KEYWORDS as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{total: int, pending: int, approved: int, spam: int}
     */
    public function getStats(): array
    {
        $row = BlogPostComment::query()
            ->selectRaw('COUNT(*) as total, SUM(status=?) as pending, SUM(status=?) as approved, SUM(status=?) as spam', [CommentStatus::Pending->value, CommentStatus::Approved->value, CommentStatus::Spam->value])
            ->first();

        return ['total' => (int) $row->total, 'pending' => (int) $row->pending, 'approved' => (int) $row->approved, 'spam' => (int) $row->spam];
    }

    private function resolveParentId(?string $parentId, int $postId): ?int
    {
        if (! $parentId) {
            return null;
        }

        $parent = BlogPostComment::query()
            ->where('id', $parentId)
            ->where('blog_post_id', $postId)
            ->where('status', CommentStatus::Approved)
            ->first();

        return $parent?->id;
    }
}
