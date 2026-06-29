<?php

namespace Modules\Engagement\Tests\Feature;

use App\Models\User;
use Modules\Engagement\Models\EmailCampaign;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;

class EmailCampaignControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    public function test_index_returns_campaigns(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);
        EmailCampaign::factory()->forInbox($inbox->id)->create(['name' => 'Newsletter']);

        $response = $this->actingAs($this->createUser())
            ->getJson(route('settings.engagement.email-campaigns.index', ['inbox_id' => $inbox->id]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_campaign(): void
    {
        $inbox = Inbox::create(['name' => 'Test', 'is_active' => true]);

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.email-campaigns.store'), [
                'inbox_id' => $inbox->id,
                'name' => 'Campaña test',
                'subject' => 'Asunto test',
                'provider' => 'mailchimp',
                'status' => 'draft',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Campaña test');
    }

    public function test_destroy_soft_deletes_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create();

        $response = $this->actingAs($this->createUser())
            ->deleteJson(route('settings.engagement.email-campaigns.destroy', $campaign));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted($campaign);
    }

    public function test_restore_recovers_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create();
        $campaign->delete();

        $response = $this->actingAs($this->createUser())
            ->postJson(route('settings.engagement.email-campaigns.restore', $campaign->id));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotSoftDeleted($campaign);
    }

    private function createUser()
    {
        return User::factory()->create();
    }
}
