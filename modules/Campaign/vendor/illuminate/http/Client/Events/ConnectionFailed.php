<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     *
     * @var Request
     */
    public $request;

    /**
     * The exception instance.
     *
     * @var ConnectionException
     */
    public $exception;

    /**
     * Create a new event instance.
     */
    public function __construct(Request $request, ConnectionException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
