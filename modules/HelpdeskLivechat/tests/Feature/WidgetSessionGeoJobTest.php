<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskLivechat\Jobs\ResolveWidgetSessionGeoJob;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\WidgetSessionService;
use Tests\TestCase;

/**
 * The widget session bootstrap must not block on geoip(): the first lookup per
 * IP can hit a remote provider. Country resolution is deferred to a queued job.
 */
class WidgetSessionGeoJobTest extends TestCase
{
    use DatabaseTransactions;

    /** @var string[] */
    protected $connectionsToTransact = ['helpdesk'];

    private function heartbeatRequest(string $ip): Request
    {
        return Request::create('https://shop.test/page', 'POST', server: ['REMOTE_ADDR' => $ip]);
    }

    public function test_public_ip_session_defers_geo_to_a_queued_job(): void
    {
        Queue::fake();

        $token = 'geo-pub-'.bin2hex(random_bytes(6));
        $session = app(WidgetSessionService::class)->heartbeat(
            $token,
            'https://shop.test/page',
            'Home',
            $this->heartbeatRequest('8.8.8.8'),
        );

        // country_code is not resolved inline — it stays null until the job runs.
        $this->assertNull($session->country_code);

        Queue::assertPushed(
            ResolveWidgetSessionGeoJob::class,
            fn (ResolveWidgetSessionGeoJob $job) => $job->sessionId === $session->id && $job->ip === '8.8.8.8',
        );
    }

    public function test_private_ip_session_does_not_dispatch_geo_job(): void
    {
        Queue::fake();

        $token = 'geo-priv-'.bin2hex(random_bytes(6));
        app(WidgetSessionService::class)->heartbeat(
            $token,
            'https://shop.test/page',
            'Home',
            $this->heartbeatRequest('127.0.0.1'),
        );

        Queue::assertNotPushed(ResolveWidgetSessionGeoJob::class);
    }

    public function test_geo_job_backfills_country_code(): void
    {
        // Pre-seed the geoip cache so the job resolves without calling geoip().
        Cache::put('helpdesklivechat:geoip:'.md5('8.8.8.8'), 'US', now()->addDay());

        $session = WidgetSession::create([
            'session_token' => 'geo-fill-'.bin2hex(random_bytes(6)),
            'current_url' => 'https://shop.test/page',
            'ip_address' => '8.8.8.8',
            'country_code' => null,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        (new ResolveWidgetSessionGeoJob($session->id, '8.8.8.8'))
            ->handle(app(WidgetSessionService::class));

        $this->assertSame('US', $session->fresh()->country_code);
    }

    public function test_resolve_country_returns_null_for_private_ip(): void
    {
        $this->assertNull(
            app(WidgetSessionService::class)->resolveCountryForIp('192.168.1.5')
        );
    }
}
