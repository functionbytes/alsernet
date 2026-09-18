<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Models;

use Modules\HelpdeskAgents\Models\AiAgentTool;
use Tests\TestCase;

class AiAgentToolModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentTool::class));
    }

    public function test_has_table_property(): void
    {
        $model = new AiAgentTool;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new AiAgentTool;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new AiAgentTool;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new AiAgentTool;
        $this->assertContains('name', $model->getFillable());
    }
}
