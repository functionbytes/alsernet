<?php

namespace Modules\HelpdeskSocial\Contracts;

/**
 * Contract for social platform API clients.
 */
interface SocialApiClientInterface
{
    /**
     * Reply to a comment on a post.
     */
    public function replyToComment(string $commentId, string $message, string $accessToken, ?string $platform = null): ?string;

    /**
     * Hide/unhide a comment.
     */
    public function hideComment(string $commentId, bool $hidden, string $accessToken): bool;

    /**
     * Delete a comment.
     */
    public function deleteComment(string $commentId, string $accessToken): bool;

    /**
     * Get comments on a post/media.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getComments(string $postId, string $accessToken, int $limit = 100): array;

    /**
     * Send a direct message.
     */
    public function sendMessage(string $recipientId, array $message, string $accessToken): ?string;

    /**
     * Get user profile by external ID.
     *
     * @return array<string, mixed>
     */
    public function getUserProfile(string $userId, string $accessToken): array;

    /**
     * Exchange a short-lived token for a long-lived one.
     */
    public function exchangeToken(string $shortLivedToken, string $appId, string $appSecret): ?string;
}
