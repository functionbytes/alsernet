<?php

namespace Modules\Page\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\PageView;

class RecordPageViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 30;

    public function __construct(
        private readonly int $pageId,
        private readonly string $ip,
        private readonly string $userAgent,
        private readonly string $referrer,
        private readonly string $locale,
        private readonly string $sessionId,
    ) {
        $this->onQueue('pages');
    }

    public function handle(): void
    {
        $now = now();

        PageView::create([
            'page_id' => $this->pageId,
            'session_id' => $this->sessionId ?: null,
            'ip_hash' => hash('sha256', $this->ip),
            'locale' => $this->locale ?: null,
            'referrer' => $this->referrer ?: null,
            'referrer_domain' => PageView::extractReferrerDomain($this->referrer),
            'device_type' => PageView::detectDevice($this->userAgent),
            'browser' => PageView::extractBrowser($this->userAgent),
            'country_code' => null,
            'viewed_date' => $now->toDateString(),
            'viewed_at' => $now,
        ]);
    }
}
