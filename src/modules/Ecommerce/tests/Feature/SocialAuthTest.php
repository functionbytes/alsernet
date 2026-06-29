<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User;
use Mockery;
use Modules\Ecommerce\Models\Customer;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function fakeSocialUser(string $id, string $email, string $name = 'Test User'): void
    {
        $socialUser = Mockery::mock(User::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn('test');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        $driver = Mockery::mock(GoogleProvider::class);
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->andReturn($driver);
    }

    public function test_redirect_for_invalid_provider_returns_404(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->get('/tienda/auth/twitter/redirect');

        $response->assertNotFound();
    }

    public function test_callback_creates_new_customer_when_email_does_not_exist(): void
    {
        $this->fakeSocialUser('google_id_123', 'newuser@example.com', 'New User');

        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->get('/tienda/auth/google/callback');

        $this->assertDatabaseHas('ecommerce_customers', [
            'email' => 'newuser@example.com',
            'provider' => 'google',
            'provider_id' => 'google_id_123',
        ]);
        $response->assertRedirect();
    }

    public function test_callback_links_existing_customer_by_email(): void
    {
        $existing = Customer::factory()->create([
            'email' => 'existing@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        $this->fakeSocialUser('google_id_456', 'existing@example.com');

        $this->withoutMiddleware([ThrottleRequests::class])
            ->get('/tienda/auth/google/callback');

        $existing->refresh();
        $this->assertEquals('google', $existing->provider);
        $this->assertEquals('google_id_456', $existing->provider_id);
    }

    public function test_callback_logs_in_customer(): void
    {
        $this->fakeSocialUser('google_id_789', 'login@example.com');

        $this->withoutMiddleware([ThrottleRequests::class])
            ->get('/tienda/auth/google/callback');

        $this->assertTrue(auth('ecommerce')->check());
        $this->assertEquals('login@example.com', auth('ecommerce')->user()->email);
    }
}
