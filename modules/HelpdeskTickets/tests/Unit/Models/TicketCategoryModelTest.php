<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketCategory;
use Tests\TestCase;

class TicketCategoryModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCategory::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketCategory;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketCategory;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketCategory;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new TicketCategory;
        $this->assertContains('name', $model->getFillable());
    }
}
