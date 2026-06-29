<?php

namespace Modules\Media\Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class SSRFTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('media-url-upload:'.optional(auth()->user())->id);
    }

    public function test_upload_from_url_rejects_loopback_ip(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'http://127.0.0.1/'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_rejects_private_192_range(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'http://192.168.1.1/'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_rejects_private_10_range(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'http://10.0.0.1/'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_rejects_file_scheme(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'file:///etc/passwd'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_rejects_ftp_scheme(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'ftp://example.com/file.jpg'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_rejects_gopher_scheme(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'gopher://example.com/'])
            ->assertUnprocessable();
    }

    public function test_upload_from_url_applies_rate_limit(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        // Clear any existing rate limit for this user
        RateLimiter::clear('media-url-upload:'.$user->id);

        // Exhaust the 5-attempt limit (controller allows 5, rejects on 6th)
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->postJson(route('media.files.upload-url'), ['url' => 'https://example.com/image.jpg']);
        }

        // 6th attempt should be rate-limited
        $this->actingAs($user)
            ->postJson(route('media.files.upload-url'), ['url' => 'https://example.com/image.jpg'])
            ->assertStatus(429);
    }
}
