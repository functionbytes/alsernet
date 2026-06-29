<?php

namespace Modules\Reviews\Tests\Feature;

use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Tests\TestCase;

class ReviewModerationTest extends TestCase
{
    public function test_user_can_toggle_review_visibility(): void
    {
        $user = $this->createUser(['reviews.moderate']);
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'is_visible' => false,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('review_moderations', [
            'review_id' => $review->id,
            'is_visible' => false,
        ]);
    }

    public function test_user_can_feature_review(): void
    {
        $user = $this->createUser(['reviews.moderate']);
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'is_featured' => true,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('review_moderations', [
            'review_id' => $review->id,
            'is_featured' => true,
        ]);
    }

    public function test_user_can_add_tags_to_review(): void
    {
        $user = $this->createUser(['reviews.moderate']);
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'tags' => ['excelente-servicio', 'cliente-frecuente'],
            ]);

        $response->assertOk();

        $moderation = $moderation->fresh();
        $this->assertContains('excelente-servicio', $moderation->tags);
        $this->assertContains('cliente-frecuente', $moderation->tags);
    }

    public function test_user_can_add_internal_notes(): void
    {
        $user = $this->createUser(['reviews.moderate']);
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'internal_notes' => 'Cliente VIP, priorizar respuesta',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('review_moderations', [
            'review_id' => $review->id,
            'internal_notes' => 'Cliente VIP, priorizar respuesta',
        ]);
    }

    public function test_moderation_requires_permission(): void
    {
        $user = $this->createUser();
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'is_visible' => false,
            ]);

        $response->assertForbidden();
    }

    public function test_moderation_logs_activity(): void
    {
        $user = $this->createUser(['reviews.moderate']);
        $review = $this->createReview();
        $moderation = $this->createModeration($review);

        $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'is_visible' => false,
            ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ReviewModeration::class,
            'subject_id' => $moderation->id,
        ]);
    }

    public function test_bulk_visibility_update(): void
    {
        $user = $this->createUser(['reviews.moderate']);

        $review1 = $this->createReview();
        $review2 = $this->createReview();
        $review3 = $this->createReview();

        $this->createModeration($review1);
        $this->createModeration($review2);
        $this->createModeration($review3);

        foreach ([$review1, $review2, $review3] as $review) {
            $this->actingAs($user)
                ->patchJson(route('reviews.moderate', $review), [
                    'is_visible' => false,
                ]);
        }

        $this->assertDatabaseCount('review_moderations', 3);
        $this->assertSame(0, ReviewModeration::query()->where('is_visible', true)->count());
    }

    public function test_unauthorized_user_cannot_moderate(): void
    {
        $user = $this->createUser();
        $review = $this->createReview();

        $response = $this->actingAs($user)
            ->patchJson(route('reviews.moderate', $review), [
                'is_visible' => false,
            ]);

        $response->assertForbidden();
    }
}
