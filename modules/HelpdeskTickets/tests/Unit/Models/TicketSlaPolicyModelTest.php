<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Tests\TestCase;

class TicketSlaPolicyModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketSlaPolicy::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketSlaPolicy;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketSlaPolicy;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketSlaPolicy;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new TicketSlaPolicy;
        $this->assertContains('name', $model->getFillable());
    }
}
