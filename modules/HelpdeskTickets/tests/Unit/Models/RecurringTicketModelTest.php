<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\RecurringTicket;
use Tests\TestCase;

class RecurringTicketModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(RecurringTicket::class));
    }

    public function test_has_table_property(): void
    {
        $model = new RecurringTicket;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new RecurringTicket;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new RecurringTicket;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new RecurringTicket;
        $this->assertContains('name', $model->getFillable());
    }
}
