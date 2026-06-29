<?php

namespace Modules\Seo\Tests\Feature;

use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Tests\TestCase;

class SeoAdvancedRedirectsTest extends TestCase
{
    public function test_regex_redirect_matches_path(): void
    {
        SeoRedirect::factory()->regex()->create([
            'source_path' => '/^\/old\/(\d+)$/',
            'target_path' => '/new/$1',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old/123')->assertRedirect('/new/123');
    }

    public function test_expired_redirect_does_not_apply(): void
    {
        SeoRedirect::factory()->create([
            'source_path' => '/expired-page',
            'target_path' => '/new-page',
            'status_code' => 301,
            'is_active' => true,
            'is_regex' => false,
            'active_until' => now()->subDay(),
        ]);

        $this->get('/expired-page')->assertStatus(404);
    }

    public function test_future_redirect_does_not_apply_yet(): void
    {
        SeoRedirect::factory()->create([
            'source_path' => '/future-page',
            'target_path' => '/new-page',
            'status_code' => 301,
            'is_active' => true,
            'is_regex' => false,
            'active_from' => now()->addDay(),
        ]);

        $this->get('/future-page')->assertStatus(404);
    }

    public function test_wildcard_redirect_matches(): void
    {
        SeoRedirect::factory()->wildcard()->create([
            'source_path' => '/blog/*/old',
            'target_path' => '/blog/new',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/blog/my-post/old')->assertRedirect('/blog/new');
    }
}
