<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Tests\TestCase;

class GoogleLocationTest extends TestCase
{
    public function test_user_can_view_locations(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.locations.index'));

        $response->assertOk()
            ->assertViewIs('reviews::settings.locations.index')
            ->assertViewHas('locations');
    }

    public function test_user_can_toggle_location_active(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        $response = $this->actingAs($user)
            ->patchJson(route('settings.reviews.locations.update', $location), [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_active' => false,
            ]);

        $this->assertDatabaseHas('review_google_locations', [
            'id' => $location->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_manually_sync_location(): void
    {
        Queue::fake();

        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        $response = $this->actingAs($user)
            ->postJson(route('settings.reviews.locations.sync', $location));

        $response->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(SyncGoogleReviewsJob::class, function ($job) use ($location) {
            return $job->location->id === $location->id;
        });
    }

    public function test_inactive_location_cannot_be_synced(): void
    {
        Queue::fake();

        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);
        $location->update(['is_active' => false]);

        $response = $this->actingAs($user)
            ->postJson(route('settings.reviews.locations.sync', $location));

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'La ubicación está desactivada',
            ]);

        Queue::assertNotPushed(SyncGoogleReviewsJob::class);
    }

    public function test_unauthorized_user_cannot_toggle_location(): void
    {
        $user = $this->createUser();
        $connection = $this->createConnection();
        $location = $this->createLocation($connection);

        $response = $this->actingAs($user)
            ->patchJson(route('settings.reviews.locations.update', $location), [
                'is_active' => false,
            ]);

        $response->assertForbidden();
    }

    public function test_sync_updates_location_stats(): void
    {
        $location = $this->createLocation();

        $this->createReview($location);
        $this->createReview($location);

        $this->assertSame(2, $location->reviews()->count());
    }
}
