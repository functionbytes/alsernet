<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Models\Automation\Automation;
use Modules\Campaign\Models\CampaignMaillist;
use Tests\TestCase;

class CampaignAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_can_be_created_with_json_data(): void
    {
        $list = CampaignMaillist::create(['name' => 'Test List']);
        $automation = Automation::create([
            'name' => 'Welcome Automation',
            'status' => 'active',
            'mail_list_id' => $list->id,
            'data' => json_encode([
                [
                    'id' => 'trigger',
                    'type' => 'ElementTrigger',
                    'options' => ['key' => 'welcome-new-subscriber', 'type' => 'list-subscription'],
                ],
            ]),
        ]);

        $this->assertDatabaseHas('campaign_automations', ['name' => 'Welcome Automation']);
        $this->assertEquals('active', $automation->fresh()->status);
    }

    public function test_automation_trigger_name_is_extracted_from_data(): void
    {
        $list = CampaignMaillist::create(['name' => 'L']);
        $automation = Automation::create([
            'name' => 'Birthday',
            'status' => 'active',
            'mail_list_id' => $list->id,
            'data' => json_encode([
                [
                    'id' => 'trigger',
                    'type' => 'ElementTrigger',
                    'options' => ['key' => 'say-happy-birthday'],
                ],
            ]),
        ]);

        $data = json_decode($automation->data, true);
        $this->assertEquals('say-happy-birthday', $data[0]['options']['key']);
    }
}
