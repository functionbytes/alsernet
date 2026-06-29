<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Services\ScoringService;

class RecalculateScoreJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly string $sessionToken,
        private readonly int $inboxId,
    ) {
        $this->onQueue('helpdesklivechat');
    }

    public function handle(ScoringService $scoringService): void
    {
        $scoringService->recalculate($this->sessionToken, $this->inboxId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RecalculateScoreJob failed', [
            'session_token' => $this->sessionToken,
            'inbox_id' => $this->inboxId,
            'error' => $exception->getMessage(),
        ]);
    }
}
