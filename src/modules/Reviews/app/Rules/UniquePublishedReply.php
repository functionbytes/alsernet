<?php

namespace Modules\Reviews\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Reviews\Models\Review;

/**
 * Validates that a review doesn't already have a published reply.
 *
 * This rule ensures data integrity by preventing duplicate published replies
 * for the same review. Draft and approved replies are allowed.
 */
class UniquePublishedReply implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @param  string  $status  The status of the reply being created/updated (draft, approved, published)
     */
    public function __construct(
        private readonly string $status
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  The attribute name (review_id)
     * @param  mixed  $value  The review ID value
     */
    public function passes($attribute, $value): bool
    {
        // Only validate if trying to publish
        if ($this->status !== 'published') {
            return true;
        }

        // Check if review exists and has a published reply
        $review = Review::find($value);

        if (! $review) {
            return true; // Let 'exists' rule handle this
        }

        // Fail if review already has a published reply
        return ! ($review->reply && $review->reply->isPublished());
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Esta reseña ya tiene una respuesta publicada';
    }
}
