<?php

namespace Modules\HelpdeskSocial\Contracts;

use Modules\HelpdeskSocial\Models\SocialComment;

/**
 * Contract for auto-reply engines.
 */
interface AutoReplyEngineInterface
{
    /**
     * Evaluate rules/actions against a comment.
     *
     * @return array<string, mixed>|null
     */
    public function evaluate(SocialComment $comment): ?array;

    /**
     * Check if auto-reply is enabled globally.
     */
    public function isEnabled(): bool;
}
