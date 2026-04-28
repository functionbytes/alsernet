<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Bus\PendingBatch;

class ChainedBatchTruthTest
{
    /**
     * The underlying truth test.
     *
     * @var Closure(PendingBatch): bool
     */
    protected $callback;

    /**
     * Create a new truth test instance.
     *
     * @param  Closure(PendingBatch): bool  $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Invoke the truth test with the given pending batch.
     *
     * @param  PendingBatch  $pendingBatch
     * @return bool
     */
    public function __invoke($pendingBatch)
    {
        return call_user_func($this->callback, $pendingBatch);
    }
}
