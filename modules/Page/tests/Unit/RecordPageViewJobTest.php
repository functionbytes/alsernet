<?php

namespace Modules\Page\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Jobs\RecordPageViewJob;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageView;
use Tests\TestCase;

class RecordPageViewJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeJob(array $overrides = []): RecordPageViewJob
    {
        $page = Page::factory()->create();

        return new RecordPageViewJob(
            pageId: $overrides['pageId'] ?? $page->id,
            ip: $overrides['ip'] ?? '192.168.1.1',
            userAgent: $overrides['userAgent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            referrer: $overrides['referrer'] ?? '',
            locale: $overrides['locale'] ?? 'es',
            sessionId: $overrides['sessionId'] ?? 'session-abc-123',
        );
    }

    public function test_job_creates_page_view_record(): void
    {
        $page = Page::factory()->create();

        $job = new RecordPageViewJob(
            pageId: $page->id,
            ip: '10.0.0.1',
            userAgent: 'Mozilla/5.0 Chrome/120',
            referrer: '',
            locale: 'es',
            sessionId: 'test-session',
        );

        $job->handle();

        $this->assertDatabaseCount('page_views', 1);
        $this->assertDatabaseHas('page_views', [
            'page_id' => $page->id,
            'locale' => 'es',
            'session_id' => 'test-session',
        ]);
    }

    public function test_ip_is_hashed_with_sha256(): void
    {
        $ip = '192.168.100.200';

        $this->makeJob(['ip' => $ip])->handle();

        $this->assertDatabaseHas('page_views', [
            'ip_hash' => hash('sha256', $ip),
        ]);
    }

    public function test_raw_ip_is_not_stored(): void
    {
        $ip = '203.0.113.42';

        $this->makeJob(['ip' => $ip])->handle();

        $view = PageView::first();
        $this->assertNotEquals($ip, $view->ip_hash);
        $this->assertEquals(64, strlen($view->ip_hash));
    }

    public function test_detects_desktop_user_agent(): void
    {
        $desktopUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';

        $this->makeJob(['userAgent' => $desktopUa])->handle();

        $this->assertDatabaseHas('page_views', ['device_type' => 'desktop']);
    }

    public function test_detects_mobile_device(): void
    {
        $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148';

        $this->makeJob(['userAgent' => $mobileUa])->handle();

        $this->assertDatabaseHas('page_views', ['device_type' => 'mobile']);
    }

    public function test_detects_tablet_device(): void
    {
        $tabletUa = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

        $this->makeJob(['userAgent' => $tabletUa])->handle();

        $this->assertDatabaseHas('page_views', ['device_type' => 'tablet']);
    }

    public function test_detects_android_mobile(): void
    {
        $androidUa = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Mobile Safari/537.36';

        $this->makeJob(['userAgent' => $androidUa])->handle();

        $this->assertDatabaseHas('page_views', ['device_type' => 'mobile']);
    }

    public function test_extracts_referrer_domain_correctly(): void
    {
        $referrer = 'https://www.google.com/search?q=test';

        $this->makeJob(['referrer' => $referrer])->handle();

        $this->assertDatabaseHas('page_views', [
            'referrer' => $referrer,
            'referrer_domain' => 'www.google.com',
        ]);
    }

    public function test_referrer_domain_is_null_when_no_referrer(): void
    {
        $this->makeJob(['referrer' => ''])->handle();

        $view = PageView::first();
        $this->assertNull($view->referrer);
        $this->assertNull($view->referrer_domain);
    }

    public function test_detects_chrome_browser(): void
    {
        $chromeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $this->makeJob(['userAgent' => $chromeUa])->handle();

        $this->assertDatabaseHas('page_views', ['browser' => 'Chrome']);
    }

    public function test_detects_firefox_browser(): void
    {
        $firefoxUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0';

        $this->makeJob(['userAgent' => $firefoxUa])->handle();

        $this->assertDatabaseHas('page_views', ['browser' => 'Firefox']);
    }

    public function test_detects_edge_browser(): void
    {
        $edgeUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';

        $this->makeJob(['userAgent' => $edgeUa])->handle();

        $this->assertDatabaseHas('page_views', ['browser' => 'Edge']);
    }

    public function test_viewed_date_matches_today(): void
    {
        $this->makeJob()->handle();

        $view = PageView::first();
        $this->assertNotNull($view);
        $this->assertEquals(now()->toDateString(), $view->viewed_date->toDateString());
    }
}
