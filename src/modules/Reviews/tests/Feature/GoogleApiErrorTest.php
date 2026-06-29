<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Jobs\PublishReviewReplyJob;
use Modules\Reviews\Jobs\SyncGoogleReviewsJob;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Tests\TestCase;

class GoogleApiErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['reviews.google.api.reviews' => 'https://mybusiness.googleapis.com/v4/accounts/456']);
    }

    public function test_sync_when_google_api_returns_500_fails_gracefully(): void
    {
        $location = $this->createLocation();

        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Internal Server Error']], 500),
        ]);

        $threwException = false;

        try {
            SyncGoogleReviewsJob::dispatchSync($location);
        } catch (\Throwable $e) {
            $threwException = true;
        }

        // The job should fail (throw), but it should not have persisted any broken state
        $this->assertTrue($threwException, 'Expected the sync job to throw on a 500 response');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_sync_when_google_api_returns_401_marks_connection_as_expired(): void
    {
        $connection = $this->createConnection();
        $location = $this->createLocation($connection);

        // Fake the token refresh endpoint to also return 401 so refreshTokenIfNeeded fails
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Token has been expired or revoked.',
            ], 401),
            '*' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        // Force token to appear expired so a refresh is attempted
        $connection->update(['token_expires_at' => now()->subHour()]);

        $threwException = false;

        try {
            SyncGoogleReviewsJob::dispatchSync($location);
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertTrue($threwException);

        // The connection should have been marked as expired by GoogleAuthService
        $this->assertSame(
            ConnectionStatus::EXPIRED,
            $connection->fresh()->status
        );
    }

    public function test_sync_when_google_api_times_out_retries_then_fails_with_logged_error(): void
    {
        $location = $this->createLocation();

        // Simulate a connection timeout on every attempt
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tk', 'expires_in' => 3600], 200),
            '*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'Google API connection failed after retries')
                || str_contains($msg, 'SyncGoogleReviewsJob failed'));

        $threwException = false;

        try {
            SyncGoogleReviewsJob::dispatchSync($location);
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertTrue($threwException, 'Expected the sync job to fail after repeated timeouts');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_reply_publish_when_google_api_returns_403_reply_stays_approved(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tk', 'expires_in' => 3600], 200),
            'mybusiness.googleapis.com/v4/*/reviews/*/reply' => Http::response([
                'error' => [
                    'code' => 403,
                    'message' => 'The caller does not have permission',
                ],
            ], 403),
        ]);

        $reply = ReviewReply::factory()->approved()->create();
        $originalStatus = $reply->status;

        try {
            PublishReviewReplyJob::dispatchSync($reply, $this->createUser());
        } catch (\Throwable $e) {
            // Expected exception from Google API 403
        }

        // The reply should remain in APPROVED status (or FAILED), never PUBLISHED
        $freshReply = $reply->fresh();
        $this->assertNotSame(ReplyStatus::PUBLISHED, $freshReply->status);
        $this->assertNull($freshReply->published_at);
    }

    public function test_sync_when_google_api_returns_500_does_not_create_partial_reviews(): void
    {
        $location = $this->createLocation();

        Http::fake([
            '*' => Http::response([
                'error' => ['code' => 500, 'message' => 'Backend Error'],
            ], 500),
        ]);

        try {
            SyncGoogleReviewsJob::dispatchSync($location);
        } catch (\Throwable $e) {
            // Expected
        }

        // No partial reviews should have been written due to the transaction rollback
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('review_moderations', 0);
    }
}
