<?php

namespace Modules\Mailing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Base implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // To immediately fail, prevent it from being tried again,
    // resulting in confusing exception like "Job X has been tried too many times or timeout"
    public $failOnTimeout = true;

    public $tries = 1;

    public $maxExceptions = 1;
}
