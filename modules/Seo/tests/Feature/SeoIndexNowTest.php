<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\Seo\Services\IndexNowService;
use Modules\Seo\Tests\TestCase;

class SeoIndexNowTest extends TestCase
{
    public function test_key_file_is_served_when_key_matches(): void
    {
        Config::set('seohelper.indexnow.key', 'abc123def456');
        Config::set('seohelper.indexnow.enabled', true);

        $this->get('/abc123def456.txt')
            ->assertOk()
            ->assertSee('abc123def456');
    }

    public function test_key_file_returns_404_for_wrong_key(): void
    {
        Config::set('seohelper.indexnow.key', 'correct_key_abc123');

        $this->get('/wrong_key_abc123.txt')->assertNotFound();
    }

    public function test_service_reports_disabled_when_no_key(): void
    {
        Config::set('seohelper.indexnow.enabled', true);
        Config::set('seohelper.indexnow.key', '');

        $this->assertFalse(app(IndexNowService::class)->enabled());
    }

    public function test_service_rejects_urls_from_other_hosts(): void
    {
        Config::set('app.url', 'https://mysite.com');
        Config::set('seohelper.indexnow.enabled', true);
        Config::set('seohelper.indexnow.key', 'validkey123456');

        $result = app(IndexNowService::class)->submit(['https://different.com/page']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('do not match configured host', $result['message']);
    }

    public function test_service_submits_urls_to_endpoint(): void
    {
        Config::set('app.url', 'https://mysite.com');
        Config::set('seohelper.indexnow.enabled', true);
        Config::set('seohelper.indexnow.key', 'validkey123456');

        Http::fake([
            'api.indexnow.org/*' => Http::response('', 200),
        ]);

        $result = app(IndexNowService::class)->submit(['https://mysite.com/page1', 'https://mysite.com/page2']);

        $this->assertTrue($result['ok']);
        $this->assertEquals(2, $result['submitted']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.indexnow.org/indexnow'
                && $body['host'] === 'mysite.com'
                && count($body['urlList']) === 2;
        });
    }

    public function test_admin_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('settings.seo.indexnow.index'))
            ->assertForbidden();
    }

    public function test_admin_shows_status(): void
    {
        Config::set('seohelper.indexnow.enabled', true);
        Config::set('seohelper.indexnow.key', 'my_site_key_abc123');

        $user = $this->createUser(['Seo.indexnow.index']);

        $this->actingAs($user)
            ->get(route('settings.seo.indexnow.index'))
            ->assertOk()
            ->assertViewIs('Seo::settings.indexnow.index')
            ->assertViewHas('status');
    }

    public function test_manual_submit_requires_submit_permission(): void
    {
        $user = $this->createUser(['Seo.indexnow.index']);

        $this->actingAs($user)
            ->post(route('settings.seo.indexnow.submit'), ['urls' => 'https://x.com/a'])
            ->assertForbidden();
    }
}
