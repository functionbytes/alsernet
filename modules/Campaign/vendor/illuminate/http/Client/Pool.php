<?php

namespace Illuminate\Http\Client;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Utils;

/**
 * @mixin Factory
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * The handler function for the Guzzle client.
     *
     * @var callable
     */
    protected $handler;

    /**
     * The pool of requests.
     *
     * @var array<array-key, PendingRequest>
     */
    protected $pool = [];

    /**
     * Create a new requests pool.
     */
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory;
        $this->handler = Utils::chooseHandler();
    }

    /**
     * Add a request to the pool with a numeric index.
     *
     * @return PendingRequest|Promise
     */
    public function newRequest()
    {
        return $this->pool[] = $this->asyncRequest();
    }

    /**
     * Add a request to the pool with a key.
     *
     * @return PendingRequest
     */
    public function as(string $key)
    {
        return $this->pool[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return PendingRequest
     */
    protected function asyncRequest()
    {
        return $this->factory->setHandler($this->handler)->async();
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return array<array-key, PendingRequest>
     */
    public function getRequests()
    {
        return $this->pool;
    }

    /**
     * Add a request to the pool with a numeric index and forward the method call to the request.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return PendingRequest|Promise
     */
    public function __call($method, $parameters)
    {
        return $this->newRequest()->{$method}(...$parameters);
    }
}
