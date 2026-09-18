<?php

namespace Modules\HelpdeskSocial\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\HelpdeskSocial\Models\SocialComment;

interface SocialCommentRepositoryInterface
{
    public function find(int $id): ?SocialComment;

    public function findByExternalId(string $platform, string $externalId): ?SocialComment;

    public function create(array $data): SocialComment;

    public function update(SocialComment $comment, array $data): SocialComment;

    /**
     * @return Collection<int, SocialComment>
     */
    public function getPendingByAccount(int $accountId): Collection;

    public function getInbox(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getStats(?string $platform = null, ?int $accountId = null): array;

    public function markAsReplied(SocialComment $comment, string $replyBody, ?int $userId = null, ?string $externalReplyId = null, string $replyType = 'manual'): void;

    public function markAsSpam(SocialComment $comment): void;

    public function markAsEscalated(SocialComment $comment): void;
}
