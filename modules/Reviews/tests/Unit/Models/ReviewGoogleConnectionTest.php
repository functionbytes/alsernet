<?php

namespace Modules\Reviews\Tests\Unit\Models;

use App\Models\User;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Tests\TestCase;

class ReviewGoogleConnectionTest extends TestCase
{
    public function test_connection_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $connection = ReviewGoogleConnection::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $connection->user);
        $this->assertTrue($connection->user->is($user));
    }

    public function test_connection_has_many_locations(): void
    {
        $connection = ReviewGoogleConnection::factory()->create();
        $location1 = ReviewGoogleLocation::factory()->for($connection, 'connection')->create();
        $location2 = ReviewGoogleLocation::factory()->for($connection, 'connection')->create();

        $this->assertCount(2, $connection->locations);
        $this->assertTrue($connection->locations->contains($location1));
        $this->assertTrue($connection->locations->contains($location2));
    }

    public function test_active_scope_filters_active_connections(): void
    {
        $activeConnection = ReviewGoogleConnection::factory()->active()->create();
        ReviewGoogleConnection::factory()->expired()->create();

        $results = ReviewGoogleConnection::active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($activeConnection));
    }

    public function test_expired_scope_filters_expired_connections(): void
    {
        ReviewGoogleConnection::factory()->active()->create();
        $expiredConnection = ReviewGoogleConnection::factory()->expired()->create();

        $results = ReviewGoogleConnection::expired()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($expiredConnection));
    }

    public function test_is_active_returns_true_for_active_status(): void
    {
        $connection = ReviewGoogleConnection::factory()->active()->create();

        $this->assertTrue($connection->isActive());
    }

    public function test_is_active_returns_false_for_non_active_status(): void
    {
        $connection = ReviewGoogleConnection::factory()->expired()->create();

        $this->assertFalse($connection->isActive());
    }

    public function test_is_expired_returns_true_when_token_expires_at_is_past(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($connection->isExpired());
    }

    public function test_is_expired_returns_false_when_token_expires_at_is_future(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($connection->isExpired());
    }

    public function test_is_expired_returns_false_when_token_expires_at_is_null(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => null,
        ]);

        $this->assertFalse($connection->isExpired());
    }

    public function test_mark_as_active_updates_status_and_clears_error(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'status' => ConnectionStatus::EXPIRED,
            'last_error' => 'Token expired',
        ]);

        $connection->markAsActive();

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
        $this->assertNull($connection->last_error);
    }

    public function test_mark_as_expired_updates_status_and_error_message(): void
    {
        $connection = ReviewGoogleConnection::factory()->active()->create();

        $connection->markAsExpired('Token has expired');

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::EXPIRED, $connection->status);
        $this->assertSame('Token has expired', $connection->last_error);
    }

    public function test_mark_as_expired_can_be_called_without_error_message(): void
    {
        $connection = ReviewGoogleConnection::factory()->active()->create();

        $connection->markAsExpired();

        $this->assertSame(ConnectionStatus::EXPIRED, $connection->fresh()->status);
    }

    public function test_mark_as_revoked_updates_status_and_timestamp(): void
    {
        $connection = ReviewGoogleConnection::factory()->active()->create();

        $connection->markAsRevoked();

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::REVOKED, $connection->status);
        $this->assertNotNull($connection->revoked_at);
    }

    public function test_status_enum_casting_works(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'status' => ConnectionStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(ConnectionStatus::class, $connection->status);
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
    }

    public function test_tokens_are_encrypted(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
        ]);

        $this->assertSame('test-access-token', $connection->access_token);
        $this->assertSame('test-refresh-token', $connection->refresh_token);

        $rawValue = $connection->getAttributes()['access_token'];
        $this->assertNotSame('test-access-token', $rawValue);
    }
}
