<?php

namespace Modules\HelpdeskSocial\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAccountsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('helpdesksocial.view');
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');
    }

    public function test_can_list_social_accounts(): void
    {
        SocialAccount::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/helpdesk/social/accounts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_social_account(): void
    {
        $data = [
            'name' => 'Test Page',
            'platform' => 'facebook',
            'external_id' => '123456789',
            'username' => 'testpage',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/helpdesk/social/accounts', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Page');

        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'name' => 'Test Page',
            'platform' => 'facebook',
        ]);
    }

    public function test_can_update_social_account(): void
    {
        $account = SocialAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/helpdesk/social/accounts/{$account->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_social_account(): void
    {
        $account = SocialAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/helpdesk/social/accounts/{$account->id}");

        $response->assertOk();
        $this->assertSoftDeleted('helpdesk_social_accounts', ['id' => $account->id]);
    }
}
