<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Events\ConnectionRevoked;
use Modules\Reviews\Jobs\SyncGoogleLocationsJob;
use Modules\Reviews\Tests\TestCase;

class GoogleConnectionTest extends TestCase
{
    public function test_user_can_view_connections_index(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
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
        $user = $this->createUser(['reviews.manage-connections']);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.connections.create'));

        $response->assertOk()
            ->assertViewIs('reviews::settings.connections.create');
    }

    public function test_user_can_create_connection(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);

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
        $user = $this->createUser(['reviews.manage-connections']);

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
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);

        session(['google_oauth_state' => 'test-state', 'google_connection_id' => $connection->id]);

        $this->fakeGoogleOAuthSuccess();

        Http::fake([
            'www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'email' => 'user@example.com',
                'id' => '123456',
            ], 200),
            'mybusiness.googleapis.com/v4/accounts*' => Http::response([
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
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);

        session(['google_oauth_state' => 'valid-state', 'google_connection_id' => $connection->id]);

        $response = $this->actingAs($user)
            ->get(route('settings.reviews.oauth.callback', [
                'code' => 'auth-code-123',
                'state' => 'invalid-state',
            ]));

        $response->assertRedirect(route('settings.reviews.connections.index'))
            ->assertSessionHas('error');

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::ERROR, $connection->status);
        $this->assertStringContainsString('Invalid OAuth state', $connection->last_error);
    }

    public function test_connection_token_refresh_updates_expiry(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
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

        $service = app(\Modules\Reviews\Services\GoogleAuthService::class);
        $service->refreshTokenIfNeeded($connection);

        $connection = $connection->fresh();
        $this->assertSame('new-access-token', $connection->access_token);
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
        $this->assertTrue($connection->token_expires_at->isFuture());
    }

    public function test_user_can_revoke_connection(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        Event::fake([ConnectionRevoked::class]);

        $response = $this->actingAs($user)
            ->delete(route('settings.reviews.connections.destroy', $connection));

        $response->assertRedirect(route('settings.reviews.connections.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('review_google_connections', [
            'id' => $connection->id,
        ]);

        Event::assertDispatched(ConnectionRevoked::class);
    }

    public function test_revoked_connection_stops_sync(): void
    {
        $user = $this->createUser(['reviews.manage-connections']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $this->actingAs($user)
            ->delete(route('settings.reviews.connections.destroy', $connection));

        $this->assertDatabaseMissing('review_google_connections', ['id' => $connection->id]);
        $this->assertDatabaseMissing('review_google_locations', ['id' => $location->id]);
    }
}
