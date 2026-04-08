<?php

namespace Modules\Reviews\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Reviews\Events\ReviewModerationUpdated;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewModeration;

class ReviewModerationService
{
    public function toggleVisibility(Review $review, User $user): ReviewModeration
    {
        $moderation = $this->findOrCreateModeration($review);

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
        $moderation = $this->findOrCreateModeration($review);

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
        $moderation = $this->findOrCreateModeration($review);

        $moderation->update([
            'is_visible' => $data['is_visible'] ?? $moderation->is_visible,
            'is_featured' => $data['is_featured'] ?? $moderation->is_featured,
            'tags' => $data['tags'] ?? $moderation->tags,
            'internal_notes' => $data['internal_notes'] ?? $moderation->internal_notes,
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        $fresh = $moderation->fresh();

        event(new ReviewModerationUpdated($review, $fresh, 'updated'));

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Moderation updated');

        return $fresh;
    }

    public function addTag(Review $review, string $tag, User $user): ReviewModeration
    {
        $moderation = $this->findOrCreateModeration($review);

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
        $moderation = $this->findOrCreateModeration($review);

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
        $moderation = $this->findOrCreateModeration($review);

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
        $moderation = $this->findOrCreateModeration($review);

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

    public function bulkModerate(array $reviewIds, string $action, User $user, ?array $tags = null): int
    {
        $reviews = Review::query()
            ->whereIn('id', $reviewIds)
            ->with('moderation')
            ->get();

        return DB::transaction(function () use ($reviews, $action, $user, $tags): int {
            $count = 0;

            foreach ($reviews as $review) {
                match ($action) {
                    'visible' => $this->setVisible($review, true, $user),
                    'hidden' => $this->setVisible($review, false, $user),
                    'featured' => $this->setFeatured($review, true, $user),
                    'unfeatured' => $this->setFeatured($review, false, $user),
                    'add_tags' => $this->bulkAddTags($review, $tags ?? [], $user),
                    'remove_tags' => $this->bulkRemoveTags($review, $tags ?? [], $user),
                };

                $count++;
            }

            return $count;
        });
    }

    private function bulkAddTags(Review $review, array $tags, User $user): ReviewModeration
    {
        $moderation = $this->findOrCreateModeration($review);

        foreach ($tags as $tag) {
            $moderation->addTag($tag);
        }

        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Bulk tags added: '.implode(', ', $tags));

        return $moderation->fresh();
    }

    private function bulkRemoveTags(Review $review, array $tags, User $user): ReviewModeration
    {
        $moderation = $this->findOrCreateModeration($review);

        foreach ($tags as $tag) {
            $moderation->removeTag($tag);
        }

        $moderation->update([
            'moderated_by' => $user->id,
            'moderated_at' => now(),
        ]);

        activity()
            ->performedOn($moderation)
            ->causedBy($user)
            ->log('Bulk tags removed: '.implode(', ', $tags));

        return $moderation->fresh();
    }

    private function findOrCreateModeration(Review $review): ReviewModeration
    {
        return ReviewModeration::firstOrCreate(
            ['review_id' => $review->id],
            [
                'is_visible' => config('reviews.general.default_moderation_visible', true),
                'is_featured' => false,
            ]
        );
    }
}
