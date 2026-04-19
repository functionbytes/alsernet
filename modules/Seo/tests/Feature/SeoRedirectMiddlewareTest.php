<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Tests\TestCase;

class SeoRedirectMiddlewareTest extends TestCase
{
    public function test_simple_redirect_fires_on_matching_path(): void
    {
        SeoRedirect::create([
            'source_path' => '/old-url',
            'target_path' => '/new-url',
            'status_code' => 301,
            'is_active' => true,
            'is_regex' => false,
            'is_wildcard' => false,
        ]);

        $this->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(301);
    }

    public function test_inactive_redirect_is_ignored(): void
    {
        SeoRedirect::create([
            'source_path' => '/inactive-url',
            'target_path' => '/elsewhere',
            'status_code' => 301,
            'is_active' => false,
            'is_regex' => false,
            'is_wildcard' => false,
        ]);

        $this->get('/inactive-url')->assertStatus(404);
    }

    public function test_redirect_respects_status_code(): void
    {
        SeoRedirect::create([
            'source_path' => '/temp-url',
            'target_path' => '/new-temp',
            'status_code' => 302,
            'is_active' => true,
            'is_regex' => false,
            'is_wildcard' => false,
        ]);

        $this->get('/temp-url')
            ->assertRedirect('/new-temp')
            ->assertStatus(302);
    }

    public function test_redirect_cache_is_invalidated_on_delete(): void
    {
        $redirect = SeoRedirect::create([
            'source_path' => '/will-delete',
            'target_path' => '/somewhere',
            'status_code' => 301,
            'is_active' => true,
            'is_regex' => false,
            'is_wildcard' => false,
            'hits_count' => 0,
        ]);

        // Prime the cache by hitting the redirect
        $this->get('/will-delete')->assertRedirect('/somewhere');
        $this->assertTrue(Cache::has(SeoRedirect::cacheKey('/will-delete')));

        // Delete the redirect and ensure cache is flushed
        $redirect->delete();
        $this->assertFalse(Cache::has(SeoRedirect::cacheKey('/will-delete')));
    }

    public function test_wildcard_and_regex_caches_are_flushed_on_save(): void
    {
        // Prime caches
        SeoRedirect::cachedWildcards();
        SeoRedirect::cachedRegex();
        $this->assertTrue(Cache::has(SeoRedirect::CACHE_KEY_WILDCARDS));
        $this->assertTrue(Cache::has(SeoRedirect::CACHE_KEY_REGEX));

        // Creating a new redirect should flush both
        SeoRedirect::create([
            'source_path' => '/another',
            'target_path' => '/elsewhere',
            'status_code' => 301,
            'is_active' => true,
            'is_regex' => false,
            'is_wildcard' => false,
        ]);

        $this->assertFalse(Cache::has(SeoRedirect::CACHE_KEY_WILDCARDS));
        $this->assertFalse(Cache::has(SeoRedirect::CACHE_KEY_REGEX));
    }
}
