<?php

namespace Modules\Campaign\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Campaign\Models\CampaignSuppressionList;
use Tests\TestCase;

class ApiSuppressionListTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_can_list_suppression_entries(): void
    {
        $this->authenticate();
        CampaignSuppressionList::create(['uid' => 'a', 'name' => 'Block', 'type' => 'email', 'value' => 'a@a.com']);

        $response = $this->getJson('/api/campaign/suppression-lists');
        $response->assertOk();
        $response->assertJsonPath('data.data.0.value', 'a@a.com');
    }

    public function test_can_create_suppression_entry(): void
    {
        $this->authenticate();
        $response = $this->postJson('/api/campaign/suppression-lists', [
            'name' => 'Block domain',
            'type' => 'domain',
            'value' => 'evil.com',
            'is_global' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('campaign_suppression_lists', ['value' => 'evil.com']);
    }

    public function test_can_check_email_suppression(): void
    {
        $this->authenticate();
        CampaignSuppressionList::create(['uid' => 'b', 'name' => 'Block', 'type' => 'email', 'value' => 'blocked@example.com']);

        $response = $this->postJson('/api/campaign/suppression-lists/check', ['email' => 'blocked@example.com']);
        $response->assertOk();
        $response->assertJsonPath('suppressed', true);
    }
}
