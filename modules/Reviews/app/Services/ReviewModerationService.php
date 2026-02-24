<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewModeration;

class ReviewModerationService
{
    public function toggleVisibility(Review $review, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->toggleVisibility();
        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Visibility toggled to: '.($moderation->is_visible ? 'visible' : 'hidden'));

        return $moderation->fresh();
    }

    public function toggleFeatured(Review $review, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->toggleFeatured();
        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Featured status toggled to: '.($moderation->is_featured ? 'featured' : 'not featured'));

        return $moderation->fresh();
    }

    public function updateModeration(Review $review, array $data, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->update([
            'is_visible' => $data['is_visible'] ?? $moderation->is_visible,
            'is_featured' => $data['is_featured'] ?? $moderation->is_featured,
            'tags' => $data['tags'] ?? $moderation->tags,
            'internal_notes' => $data['internal_notes'] ?? $moderation->internal_notes,
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Moderation updated');

        return $moderation->fresh();
    }

    public function addTag(Review $review, string $tag, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->addTag($tag);
        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log("Tag added: {$tag}");

        return $moderation->fresh();
    }

    public function removeTag(Review $review, string $tag, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->removeTag($tag);
        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log("Tag removed: {$tag}");

        return $moderation->fresh();
    }

    public function setVisible(Review $review, bool $visible, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->update([
            'is_visible' => $visible,
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Visibility set to: '.($visible ? 'visible' : 'hidden'));

        return $moderation->fresh();
    }

    public function setFeatured(Review $review, bool $featured, User $user): ReviewModeration
    {
        $moderation = $review->moderation ?? $this->createModeration($review);

        $moderation->update([
            'is_featured' => $featured,
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Featured status set to: '.($featured ? 'featured' : 'not featured'));

        return $moderation->fresh();
    }

    public function bulkModerate(array $reviewIds, string $action, User $user): int
    {
        $reviews = Review::query()
            ->whereIn('id', $reviewIds)
            ->with('moderation')
            ->get();

        $count = 0;

        foreach ($reviews as $review) {
            match ($action) {
                'visible' => $this->setVisible($review, true, $user),
                'hidden' => $this->setVisible($review, false, $user),
                'featured' => $this->setFeatured($review, true, $user),
                'unfeatured' => $this->setFeatured($review, false, $user),
            };

            $count++;
        }

        return $count;
    }

    private function createModeration(Review $review): ReviewModeration
    {
        return ReviewModeration::query()->create([
            'review_id' => $review->id,
            'is_visible' => config('reviews.general.default_moderation_visible', true),
            'is_featured' => false,
        ]);
    }
}
