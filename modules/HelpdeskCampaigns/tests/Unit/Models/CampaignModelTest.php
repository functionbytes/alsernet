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

    // ==================== State machine ====================

    public function test_valid_transitions_are_allowed(): void
    {
        $valid = [
            ['draft', 'active'],
            ['draft', 'scheduled'],
            ['draft', 'pending_approval'],
            ['pending_approval', 'draft'],
            ['pending_approval', 'active'],
            ['pending_approval', 'scheduled'],
            ['scheduled', 'active'],
            ['scheduled', 'ended'],
            ['active', 'paused'],
            ['active', 'ended'],
            ['paused', 'active'],
            ['paused', 'ended'],
        ];

        foreach ($valid as [$from, $to]) {
            $this->assertTrue(
                $this->campaignWithStatus($from)->canTransitionTo($to),
                "Se esperaba que {$from} → {$to} fuera válida."
            );
        }
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $invalid = [
            ['ended', 'active'],   // resume/publish de una finalizada
            ['ended', 'paused'],
            ['ended', 'draft'],
            ['ended', 'ended'],
            ['draft', 'paused'],
            ['draft', 'ended'],
            ['active', 'active'],
            ['active', 'draft'],
            ['paused', 'paused'],
            ['scheduled', 'paused'],
        ];

        foreach ($invalid as [$from, $to]) {
            $this->assertFalse(
                $this->campaignWithStatus($from)->canTransitionTo($to),
                "Se esperaba que {$from} → {$to} fuera inválida."
            );
        }
    }

    public function test_unknown_status_allows_no_transitions(): void
    {
        $this->assertFalse($this->campaignWithStatus('legacy_weird_status')->canTransitionTo('active'));
    }

    public function test_transition_map_covers_every_status(): void
    {
        $this->assertSame(
            ['draft', 'pending_approval', 'scheduled', 'active', 'paused', 'ended'],
            array_keys(Campaign::STATUS_TRANSITIONS)
        );
    }

    private function campaignWithStatus(string $status): Campaign
    {
        return (new Campaign)->forceFill(['status' => $status]);
    }
}
