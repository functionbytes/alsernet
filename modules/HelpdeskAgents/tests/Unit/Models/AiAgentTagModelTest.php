<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Models;

use Modules\HelpdeskAgents\Models\AiAgentTag;
use Tests\TestCase;

class AiAgentTagModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentTag::class));
    }

    public function test_has_table_property(): void
    {
        $model = new AiAgentTag;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new AiAgentTag;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new AiAgentTag;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new AiAgentTag;
        $this->assertContains('name', $model->getFillable());
    }
}
