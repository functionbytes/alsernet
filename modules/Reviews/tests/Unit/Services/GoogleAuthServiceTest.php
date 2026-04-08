<?php

namespace Modules\Reviews\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Services\GoogleAuthService;
use Modules\Reviews\Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    private GoogleAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GoogleAuthService;
    }

    public function test_get_auth_url_generates_valid_oauth_url(): void
    {
        $state = 'test-state-123';

        $url = $this->service->getAuthUrl($state);

        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $url);
        $this->assertStringContainsString('state='.urlencode($state), $url);
        $this->assertStringContainsString('access_type=offline', $url);
        $this->assertStringContainsString('prompt=consent', $url);
    }

    public function test_get_auth_url_includes_correct_scopes(): void
    {
        config(['reviews.google.scopes' => [
            'https://www.googleapis.com/auth/business.manage',
        ]]);

        $url = $this->service->getAuthUrl('test-state');

        $this->assertStringContainsString('googleapis.com', $url);
    }

    public function test_handle_callback_exchanges_code_for_tokens(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
                'scope' => 'https://www.googleapis.com/auth/business.manage',
            ], 200),
        ]);

        $result = $this->service->handleCallback('test-code');

        $this->assertSame('test-access-token', $result['access_token']);
        $this->assertSame('test-refresh-token', $result['refresh_token']);
        $this->assertSame(3600, $result['expires_in']);
    }

    public function test_handle_callback_throws_exception_when_no_access_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                // no access_token field
            ], 200),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OAuth error: No access token received');

        $this->service->handleCallback('invalid-code');
    }

    public function test_refresh_token_if_needed_skips_refresh_when_token_valid(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->addHour(),
            'access_token' => 'current-token',
        ]);

        Http::fake();

        $this->service->refreshTokenIfNeeded($connection);

        Http::assertNothingSent();
        $this->assertSame('current-token', $connection->fresh()->access_token);
    }

    public function test_refresh_token_if_needed_refreshes_expired_token(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->subHour(),
            'refresh_token' => 'refresh-token',
            'access_token' => 'old-token',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $this->service->refreshTokenIfNeeded($connection);

        $connection = $connection->fresh();
        $this->assertSame('new-token', $connection->access_token);
        $this->assertSame(ConnectionStatus::ACTIVE, $connection->status);
        $this->assertNull($connection->last_error);
    }

    public function test_refresh_token_if_needed_throws_when_no_refresh_token(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->subHour(),
            'refresh_token' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/No refresh token available/');

        $this->service->refreshTokenIfNeeded($connection);
    }

    public function test_refresh_token_if_needed_marks_connection_as_expired_on_failure(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'token_expires_at' => now()->subHour(),
            'refresh_token' => 'refresh-token',
            'status' => ConnectionStatus::ACTIVE,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        try {
            $this->service->refreshTokenIfNeeded($connection);
        } catch (\Exception $e) {
            // Expected exception
        }

        $connection = $connection->fresh();
        $this->assertSame(ConnectionStatus::EXPIRED, $connection->status);
        $this->assertNotNull($connection->last_error);
    }

    public function test_revoke_token_calls_google_api(): void
    {
        $connection = ReviewGoogleConnection::factory()->create([
            'access_token' => 'token-to-revoke',
        ]);

        Http::fake([
            'oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $this->service->revokeToken($connection);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://oauth2.googleapis.com/revoke';
        });

        $this->assertSame(ConnectionStatus::REVOKED, $connection->fresh()->status);
    }

    public function test_get_user_info_fetches_google_account_details(): void
    {
        Http::fake([
            'www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => '123456',
                'email' => 'user@example.com',
                'name' => 'Test User',
            ], 200),
        ]);

        $result = $this->service->getUserInfo('test-access-token');

        $this->assertSame('123456', $result['id']);
        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame('Test User', $result['name']);
    }
}
