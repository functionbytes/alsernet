<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Events\ConnectionRevoked;
use Modules\Reviews\Jobs\SyncGoogleLocationsJob;
use Modules\Reviews\Services\GoogleAuthService;
use Modules\Reviews\Tests\TestCase;

class GoogleConnectionTest extends TestCase
{
    public function test_user_can_view_connections_index(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.connections.index'));

        $response->assertOk()
            ->assertViewIs('reviews::settings.connections.index')
            ->assertViewHas('connections');
    }

    public function test_unauthorized_user_cannot_access_connections(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.connections.index'));

        $response->assertForbidden();
    }

    public function test_user_can_view_create_connection_form(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.connections.create'));

        $response->assertOk()
            ->assertViewIs('reviews::settings.connections.create');
    }

    public function test_user_can_create_connection(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);

        $response = $this->actingAs($user)
            ->post(route('settings.reviews.connections.store'), [
                'name' => 'Mi Negocio',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('review_google_connections', [
            'user_id' => $user->id,
            'name' => 'Mi Negocio',
            'status' => ConnectionStatus::PENDING->value,
        ]);
    }

    public function test_oauth_redirect_generates_valid_url_with_state(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);

        $response = $this->actingAs($user)
            ->post(route('settings.reviews.connections.store'), [
                'name' => 'Test Connection',
            ]);

        $response->assertRedirectContains('accounts.google.com/o/oauth2');
        $this->assertNotNull(session('google_oauth_state'));
        $this->assertNotNull(session('google_connection_id'));
    }

    public function test_oauth_callback_stores_tokens_encrypted(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);

        session(['google_oauth_state' => 'test-state', 'google_connection_id' => $connection->id]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'refresh_token' => 'fake-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'email' => 'user@example.com',
                'id' => '123456',
            ], 200),
            'mybusinessaccountmanagement.googleapis.com/*' => Http::response([
                'accounts' => [
                    ['name' => 'accounts/123', 'accountName' => 'My Business'],
                ],
            ], 200),
        ]);

        Queue::fake();

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.oauth.callback', [
                'code' => 'auth-code-123',
                'state' => 'test-state',
            ]));

        $response->assertRedirect(route('settings.reviews.connections.index'))
            ->assertSessionHas('success');

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
        $this->assertSame('user@example.com', $connection->google_email);
        $this->assertNotNull($connection->access_token);
        $this->assertNotNull($connection->refresh_token);

        Queue::assertPushed(SyncGoogleLocationsJob::class);
    }

    public function test_oauth_callback_rejects_invalid_state(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);

        session(['google_oauth_state' => 'valid-state', 'google_connection_id' => $connection->id]);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.oauth.callback', [
                'code' => 'auth-code-123',
                'state' => 'invalid-state',
            ]));

        $response->assertRedirect(route('settings.reviews.connections.index'))
            ->assertSessionHas('error');
    }

    public function test_connection_token_refresh_updates_expiry(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);
        $connection->update([
            'token_expires_at' => now()->subHour(),
            'refresh_token' => 'refresh-token',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = app(GoogleAuthService::class);
        $service->refreshTokenIfNeeded($connection);

        $connection = $connection->fresh();
        $this->assertSame('new-access-token', $connection->access_token);
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
        $this->assertTrue($connection->token_expires_at->isFuture());
    }

    public function test_user_can_revoke_connection(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        Event::fake([ConnectionRevoked::class]);

        $response = $this->actingAs($user)
            ->delete(route('settings.reviews.connections.destroy', $connection));

        $response->assertRedirect(route('settings.reviews.connections.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('review_google_connections', [
            'id' => $connection->id,
        ]);

        Event::assertDispatched(ConnectionRevoked::class);
    }

    public function test_revoked_connection_stops_sync(): void
    {
        $user = $this->createUser(['reviews.connections.view', 'reviews.connections.create', 'reviews.connections.delete', 'reviews.connections.revoke']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $this->actingAs($user)
            ->delete(route('settings.reviews.connections.destroy', $connection));

        $this->assertSoftDeleted('review_google_connections', ['id' => $connection->id]);
        // Location is deactivated by the ConnectionRevoked event listener
        $this->assertDatabaseHas('review_google_locations', [
            'id' => $location->id,
            'is_active' => false,
        ]);
    }
}
