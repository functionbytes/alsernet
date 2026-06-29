<?php

namespace Modules\Reviews\Tests\Unit\Listeners;

use Modules\Reviews\Events\ConnectionRevoked;
use Modules\Reviews\Listeners\HandleConnectionRevoked;
use Modules\Reviews\Tests\TestCase;

class HandleConnectionRevokedTest extends TestCase
{
    public function test_handle_deactivates_all_locations_for_the_connection(): void
    {
        $connection = $this->createConnection();

        $this->createLocation($connection);
        $this->createLocation($connection);

        $event = new ConnectionRevoked($connection);

        (new HandleConnectionRevoked)->handle($event);

        $this->assertDatabaseMissing('review_google_locations', [
            'connection_id' => $connection->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('review_google_locations', [
            'connection_id' => $connection->id,
            'is_active' => false,
        ]);
    }

    public function test_handle_does_not_affect_locations_of_other_connections(): void
    {
        $targetConnection = $this->createConnection();
        $otherConnection = $this->createConnection();

        $this->createLocation($targetConnection);
        $otherLocation = $this->createLocation($otherConnection);

        $event = new ConnectionRevoked($targetConnection);

        (new HandleConnectionRevoked)->handle($event);

        $this->assertDatabaseHas('review_google_locations', [
            'id' => $otherLocation->id,
            'is_active' => true,
        ]);
    }

    public function test_handle_works_when_connection_has_no_locations(): void
    {
        $connection = $this->createConnection();

        $event = new ConnectionRevoked($connection);

        (new HandleConnectionRevoked)->handle($event);

        $this->assertDatabaseCount('review_google_locations', 0);
    }
}
