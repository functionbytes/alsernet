<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Models;

use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Tests\TestCase;

class AiAgentFlowModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentFlow::class));
    }

    public function test_has_table_property(): void
    {
        $model = new AiAgentFlow;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new AiAgentFlow;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new AiAgentFlow;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new AiAgentFlow;
        $this->assertContains('name', $model->getFillable());
    }
}
