<?php

namespace Modules\HelpdeskCampaigns\Tests\Unit\Models;

use Modules\HelpdeskCampaigns\Models\Campaign;
use Tests\TestCase;

class CampaignModelTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(Campaign::class));
    }

    public function test_has_table_property(): void
    {
        $model = new Campaign;
        $this->assertNotEmpty($model->getTable());
    }

    public function test_uses_helpdesk_connection(): void
    {
        $model = new Campaign;
        $this->assertEquals('helpdesk', $model->getConnectionName());
    }

    public function test_has_fillable(): void
    {
        $model = new Campaign;
        $this->assertNotEmpty($model->getFillable());
    }

    public function test_name_in_fillable(): void
    {
        $model = new Campaign;
        $this->assertContains('name', $model->getFillable());
    }

    public function test_template_id_in_fillable(): void
    {
        $model = new Campaign;
        $this->assertContains('template_id', $model->getFillable());
    }
}
