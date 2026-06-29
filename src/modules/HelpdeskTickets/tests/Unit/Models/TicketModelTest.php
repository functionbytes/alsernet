<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

class TicketModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(Ticket::class));
    }

    public function test_has_table_property(): void
    {
        $model = new Ticket;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new Ticket;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new Ticket;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_title_in_fillable(): void
    {
        $model = new Ticket;
        $this->assertContains('title', $model->getFillable());
    }
}
