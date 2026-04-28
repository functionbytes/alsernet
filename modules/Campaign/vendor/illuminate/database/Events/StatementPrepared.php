<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Connection;

class StatementPrepared
{
    /**
     * Create a new event instance.
     *
     * @param  Connection  $connection  The database connection instance.
     * @param  \PDOStatement  $statement  The PDO statement.
     */
    public function __construct(
        public $connection,
        public $statement,
    ) {}
}
