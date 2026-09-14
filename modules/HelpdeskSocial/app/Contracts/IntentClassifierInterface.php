<?php

namespace Modules\HelpdeskSocial\Contracts;

use Modules\HelpdeskSocial\Models\SocialComment;

/**
 * Contract for intent classification engines.
 */
interface IntentClassifierInterface
{
    /**
     * The unique identifier for this classifier.
     */
    public function getIdentifier(): string;

    /**
     * Classify a social comment and return the result.
     *
     * @return array<string, mixed>
     */
    public function classify(SocialComment $comment): array;

    /**
     * Check if this classifier is available (e.g., API key configured).
     */
    public function isAvailable(): bool;

    /**
     * Get the confidence threshold for this classifier.
     */
    public function getConfidenceThreshold(): float;
}
