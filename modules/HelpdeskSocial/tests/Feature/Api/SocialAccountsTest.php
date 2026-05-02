<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAccountsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.manage-accounts',
        ]);
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_accounts(): void
    {
        SocialAccount::factory()->count(3)->create();

        $response = $this->getJson('/api/helpdesk/social/accounts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_account(): void
    {
        $response = $this->postJson('/api/helpdesk/social/accounts', [
            'name' => 'Test Page',
            'platform' => 'facebook',
            'external_id' => '123456789',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.platform', 'facebook');

        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'platform' => 'facebook',
            'external_id' => '123456789',
        ]);
    }

    public function test_can_show_account(): void
    {
        $account = SocialAccount::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/accounts/{$account->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $account->id);
    }

    public function test_can_update_account(): void
    {
        $account = SocialAccount::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/helpdesk/social/accounts/{$account->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'id' => $account->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_account(): void
    {
        $account = SocialAccount::factory()->create();

        $response = $this->deleteJson("/api/helpdesk/social/accounts/{$account->id}");

        $response->assertOk();
        $this->assertSoftDeleted('helpdesk_social_accounts', ['id' => $account->id]);
    }

    public function test_can_toggle_account_active(): void
    {
        $account = SocialAccount::factory()->create(['is_active' => true]);

        $response = $this->postJson("/api/helpdesk/social/accounts/{$account->id}/toggle");

        $response->assertOk();
        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }
}
