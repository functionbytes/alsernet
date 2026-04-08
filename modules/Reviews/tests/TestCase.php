<?php

namespace Modules\Reviews\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewModeration;
use Modules\Reviews\Models\ReviewReply;
use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake Google API calls by default
        Http::preventStrayRequests();
    }

    protected function createUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    protected function createConnection(?User $user = null): ReviewGoogleConnection
    {
        return ReviewGoogleConnection::factory()
            ->for($user ?? $this->createUser())
            ->create();
    }

    protected function createLocation(?ReviewGoogleConnection $connection = null): ReviewGoogleLocation
    {
        return ReviewGoogleLocation::factory()
            ->for($connection ?? $this->createConnection(), 'connection')
            ->create();
    }

    protected function createReview(?ReviewGoogleLocation $location = null): Review
    {
        return Review::factory()
            ->for($location ?? $this->createLocation(), 'location')
            ->create();
    }

    protected function createModeration(?Review $review = null, array $attributes = []): ReviewModeration
    {
        return ReviewModeration::factory()
            ->for($review ?? $this->createReview())
            ->create($attributes);
    }

    protected function createReply(?Review $review = null, ?User $user = null): ReviewReply
    {
        return ReviewReply::factory()
            ->for($review ?? $this->createReview())
            ->for($user ?? $this->createUser(), 'createdBy')
            ->create();
    }

    protected function fakeGoogleOAuthSuccess(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);
    }

    protected function fakeGoogleOAuthError(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 400),
        ]);
    }

    protected function fakeGoogleReviewsResponse(array $reviews = []): void
    {
        Http::fake([
            'mybusiness.googleapis.com/v4/*/reviews*' => Http::response([
                'reviews' => $reviews,
                'totalReviewCount' => count($reviews),
                'averageRating' => 4.5,
            ], 200),
        ]);
    }

    protected function fakeGoogleAccountsResponse(array $accounts = []): void
    {
        Http::fake([
            'mybusiness.googleapis.com/v4/accounts*' => Http::response([
                'accounts' => $accounts,
            ], 200),
        ]);
    }

    protected function fakeGoogleLocationsResponse(array $locations = []): void
    {
        Http::fake([
            'mybusiness.googleapis.com/v4/*/locations*' => Http::response([
                'locations' => $locations,
            ], 200),
        ]);
    }

    protected function fakeGoogleReplySuccess(): void
    {
        Http::fake([
            'mybusiness.googleapis.com/v4/*/reviews/*/reply' => Http::response([
                'comment' => 'Thank you for your review!',
                'updateTime' => now()->toIso8601String(),
            ], 200),
        ]);
    }

    protected function fakeGoogleReplyError(): void
    {
        Http::fake([
            'mybusiness.googleapis.com/v4/*/reviews/*/reply' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'The caller does not have permission',
                ],
            ], 403),
        ]);
    }

    protected function createGoogleReviewData(array $overrides = []): array
    {
        return array_merge([
            'reviewId' => 'AIe9_BF'.fake()->uuid(),
            'reviewer' => [
                'displayName' => fake()->name(),
                'profilePhotoUrl' => fake()->imageUrl(),
            ],
            'starRating' => 'FIVE',
            'comment' => fake()->paragraph(),
            'createTime' => now()->toIso8601String(),
            'updateTime' => now()->toIso8601String(),
        ], $overrides);
    }
}
