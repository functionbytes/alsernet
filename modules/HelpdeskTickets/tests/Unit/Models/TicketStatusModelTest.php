<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class TicketStatusModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketStatus::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketStatus;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketStatus;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketStatus;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new TicketStatus;
        $this->assertContains('name', $model->getFillable());
    }
}
