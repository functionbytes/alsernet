<?php

namespace Modules\Forms\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Services\FormPageCacheInvalidator;

class InvalidateFormPagesCacheJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 5;

    public function __construct(
        public readonly int $formId,
        public readonly ?string $slug,
    ) {
        $this->onQueue('default');
    }

    public function handle(FormPageCacheInvalidator $invalidator): void
    {
        $invalidator->invalidateByIds($this->formId, $this->slug);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('InvalidateFormPagesCacheJob failed', [
            'form_id' => $this->formId,
            'error' => $exception->getMessage(),
        ]);
    }

    public function uniqueId(): string
    {
        return 'forms:invalidate:'.$this->formId;
    }
}
