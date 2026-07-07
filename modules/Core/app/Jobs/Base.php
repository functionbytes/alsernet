<?php

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Base implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $failOnTimeout = true;

    public $tries = 1;

    public $maxExceptions = 1;

    public $timeout = 60;

    /**
     * Log por defecto para las subclases que no definan su propio failed():
     * sin esto un fallo era silencioso (tries=1, sin reintento ni rastro).
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(static::class.' failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
