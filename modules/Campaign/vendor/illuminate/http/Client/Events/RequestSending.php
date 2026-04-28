<?php

namespace Illuminate\Http\Client\Events;

use Illuminate\Http\Client\Request;

class RequestSending
{
    /**
     * The request instance.
     *
     * @var Request
     */
    public $request;

    /**
     * Create a new event instance.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
