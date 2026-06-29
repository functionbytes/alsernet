<?php

namespace Modules\Reviews\Tests\Feature;

use Modules\Reviews\Models\ReviewSavedFilter;
use Modules\Reviews\Tests\TestCase;

/**
 * Tests for the share/unshare behaviour in ReviewSavedFilterController.
 *
 * The share route is: POST /reviews/saved-filters/{savedFilter}/share
 * Authorization is handled by ReviewSavedFilterPolicy (owner-only update).
 * The index route returns shared filters to all authenticated users.
 */
class ReviewSavedFilterSharingTest extends TestCase
{
    // =========================================================================
    // POST /reviews/saved-filters/{savedFilter}/share — toggle on
    // =========================================================================

    public function test_user_can_share_own_filter(): void
    {
        $user = $this->createUser(['reviews.view']);

        $filter = ReviewSavedFilter::factory()->create([
            'user_id' => $user->id,
            'is_shared' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.saved-filters.share', $filter));

        $response->assertOk();
        $this->assertDatabaseHas('review_saved_filters', ['id' => $filter->id, 'is_shared' => true]);
    }

    // =========================================================================
    // POST /reviews/saved-filters/{savedFilter}/share — toggle off
    // =========================================================================

    public function test_user_can_unshare_filter(): void
    {
        $user = $this->createUser(['reviews.view']);

        $filter = ReviewSavedFilter::factory()->create([
            'user_id' => $user->id,
            'is_shared' => true,
            'shared_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('reviews.saved-filters.share', $filter));

        $response->assertOk();
        $this->assertDatabaseHas('review_saved_filters', ['id' => $filter->id, 'is_shared' => false]);
    }

    // =========================================================================
    // POST /reviews/saved-filters/{savedFilter}/share — 403 for non-owner
    // =========================================================================

    public function test_user_cannot_share_other_users_filter(): void
    {
        $userA = $this->createUser(['reviews.view']);
        $userB = $this->createUser(['reviews.view']);

        $filterB = ReviewSavedFilter::factory()->create([
            'user_id' => $userB->id,
            'is_shared' => false,
        ]);

        // Authorization check fires before the activity log — no schema error here
        $response = $this->actingAs($userA)
            ->postJson(route('reviews.saved-filters.share', $filterB));

        $response->assertForbidden();

        $this->assertDatabaseHas('review_saved_filters', ['id' => $filterB->id, 'is_shared' => false]);
    }

    // =========================================================================
    // GET /reviews/saved-filters (index) — shared filters visible to all users
    // =========================================================================

    public function test_shared_filters_visible_to_all_users(): void
    {
        $userA = $this->createUser(['reviews.view']);
        $userB = $this->createUser(['reviews.view']);

        $sharedFilter = ReviewSavedFilter::factory()->create([
            'user_id' => $userA->id,
            'name' => 'Shared by A',
            'is_shared' => true,
            'shared_by' => $userA->id,
        ]);

        $response = $this->actingAs($userB)
            ->getJson(route('reviews.saved-filters.index'));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($sharedFilter->id, $ids);
    }
}
