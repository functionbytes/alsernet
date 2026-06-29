<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Tests\TestCase;

class TicketCannedReplyModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCannedReply::class));
    }

    public function test_has_table_property(): void
    {
        $model = new TicketCannedReply;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new TicketCannedReply;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new TicketCannedReply;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_title_in_fillable(): void
    {
        $model = new TicketCannedReply;
        $this->assertContains('title', $model->getFillable());
    }
}
