<?php

namespace Illuminate\Contracts\Redis;

use Illuminate\Redis\Connections\Connection;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return Connection
     */
    public function connection($name = null);
}
