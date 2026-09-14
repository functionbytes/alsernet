<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Models;

use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;
use Tests\TestCase;

class AiAgentKnowledgeBaseModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentKnowledgeBase::class));
    }

    public function test_has_table_property(): void
    {
        $model = new AiAgentKnowledgeBase;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new AiAgentKnowledgeBase;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new AiAgentKnowledgeBase;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_title_in_fillable(): void
    {
        $model = new AiAgentKnowledgeBase;
        $this->assertContains('title', $model->getFillable());
    }
}
