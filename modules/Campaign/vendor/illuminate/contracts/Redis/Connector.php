<?php

namespace Illuminate\Contracts\Redis;

use Illuminate\Redis\Connections\Connection;

interface Connector
{
    /**
     * Create a connection to a Redis cluster.
     *
     * @return Connection
     */
    public function connect(array $config, array $options);

    /**
     * Create a connection to a Redis instance.
     *
     * @return Connection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options);
}
