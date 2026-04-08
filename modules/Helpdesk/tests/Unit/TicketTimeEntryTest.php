<?php

namespace Modules\Helpdesk\Tests\Unit;

use Modules\Helpdesk\Models\TicketTimeEntry;
use PHPUnit\Framework\TestCase;

class TicketTimeEntryTest extends TestCase
{
    public function test_formatted_duration_shows_hours_and_minutes(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 150]);

        $this->assertEquals('2h 30m', $entry->formatted_duration);
    }

    public function test_formatted_duration_shows_only_minutes(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 45]);

        $this->assertEquals('45m', $entry->formatted_duration);
    }

    public function test_formatted_duration_shows_only_hours_when_no_remainder(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 60]);

        $this->assertEquals('1h', $entry->formatted_duration);
    }

    public function test_formatted_duration_shows_multiple_hours(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 120]);

        $this->assertEquals('2h', $entry->formatted_duration);
    }

    public function test_formatted_duration_one_minute(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 1]);

        $this->assertEquals('1m', $entry->formatted_duration);
    }

    public function test_formatted_duration_90_minutes_is_1h_30m(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 90]);

        $this->assertEquals('1h 30m', $entry->formatted_duration);
    }

    public function test_formatted_duration_480_minutes_is_8h(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 480]);

        $this->assertEquals('8h', $entry->formatted_duration);
    }

    public function test_formatted_duration_59_minutes_is_59m(): void
    {
        $entry = new TicketTimeEntry(['minutes' => 59]);

        $this->assertEquals('59m', $entry->formatted_duration);
    }
}
