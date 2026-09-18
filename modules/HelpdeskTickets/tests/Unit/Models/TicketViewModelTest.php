<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketView;
use Tests\TestCase;

class TicketViewModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketView::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketView;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketView;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketView;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new TicketView;
        $this->assertContains('name', $model->getFillable());
    }
}
