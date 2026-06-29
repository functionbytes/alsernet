<?php

namespace Modules\Reviews\Contracts;

use Carbon\Carbon;
use Modules\Reviews\Models\ReviewReply;

interface ReviewPlatform
{
    /**
     * Machine-readable platform identifier (e.g. 'google', 'facebook', 'yelp').
     */
    public function getName(): string;

    /**
     * Human-readable platform name (e.g. 'Google Business Profile').
     */
    public function getDisplayName(): string;

    /**
     * Whether this platform supports posting replies via API.
     */
    public function supportsReplies(): bool;

    /**
     * Whether this platform supports syncing reviews.
     */
    public function supportsSync(): bool;

    /**
     * Sync reviews from the platform for a given connection.
     *
     * @param  mixed  $connection  Platform connection model
     * @return int Number of reviews synced
     */
    public function syncReviews(mixed $connection, ?Carbon $since): int;

    /**
     * Publish a reply to the platform.
     */
    public function publishReply(ReviewReply $reply): bool;

    /**
     * Revoke/disconnect a platform connection.
     */
    public function revokeConnection(mixed $connection): bool;
}
