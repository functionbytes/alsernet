<?php

namespace Modules\Helpdesk\Tests\Unit\Models;

use Modules\Helpdesk\Models\ConversationTag;
use Tests\TestCase;

class ConversationTagModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(ConversationTag::class));
    }

    public function test_has_table_property(): void
    {
        $model = new ConversationTag;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new ConversationTag;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new ConversationTag;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new ConversationTag;
        $this->assertContains('name', $model->getFillable());
    }
}
