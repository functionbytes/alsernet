<?php

namespace Modules\HelpdeskLivechat\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskLivechat\Models\WidgetSession;
use Modules\HelpdeskLivechat\Services\WidgetSessionService;

/**
 * Resolves a widget session's country off the request path. The first geoip()
 * lookup per IP can hit a remote provider and block the widget's session
 * bootstrap; deferring it keeps the tracking endpoint fast and backfills
 * country_code as soon as the worker resolves it.
 */
class ResolveWidgetSessionGeoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 15;

    public int $backoff = 10;

    public function __construct(
        public readonly int $sessionId,
        public readonly string $ip,
    ) {
        $this->onQueue('default');
    }

    public function handle(WidgetSessionService $sessions): void
    {
        $country = $sessions->resolveCountryForIp($this->ip);

        if ($country === null) {
            return;
        }

        WidgetSession::query()
            ->whereKey($this->sessionId)
            ->update(['country_code' => $country]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('ResolveWidgetSessionGeoJob failed', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
