<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketGroup;
use Tests\TestCase;

class TicketGroupModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketGroup::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketGroup;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketGroup;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketGroup;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new TicketGroup;
        $this->assertContains('name', $model->getFillable());
    }
}
