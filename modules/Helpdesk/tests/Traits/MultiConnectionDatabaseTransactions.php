<?php

namespace Modules\Helpdesk\Tests\Traits;

use Illuminate\Foundation\Testing\DatabaseTransactions;

trait MultiConnectionDatabaseTransactions
{
    use DatabaseTransactions {
        DatabaseTransactions::beginDatabaseTransaction as parentBeginDatabaseTransaction;
    }

    public function beginDatabaseTransaction(): void
    {
        $connections = ['mariadb', 'helpdesk', 'mysql'];

        foreach ($connections as $connection) {
            if (config("database.connections.{$connection}")) {
                $this->app->make('db')->connection($connection)->beginTransaction();
            }
        }

        $this->beforeApplicationDestroyed(function () use ($connections) {
            foreach ($connections as $connection) {
                if (config("database.connections.{$connection}")) {
                    $this->app->make('db')->connection($connection)->rollBack();
                }
            }
        });
    }
}
