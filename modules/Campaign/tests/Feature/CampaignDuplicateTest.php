<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Http\Controllers\Api\CampaignDuplicateController;
use Modules\Campaign\Models\Campaign;
use Tests\TestCase;

class CampaignDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_be_duplicated(): void
    {
        $campaign = Campaign::forceCreate([
            'name' => 'Original',
            'subject' => 'S',
            'from_email' => 'a@a.com',
            'from_name' => 'A',
            'reply_to' => 'a@a.com',
        ]);

        $controller = new CampaignDuplicateController;
        $response = $controller->duplicate($campaign->uid);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('Campaign duplicated', $data['message']);
        $this->assertStringContainsString('(copia)', $data['data']['name']);
    }
}
