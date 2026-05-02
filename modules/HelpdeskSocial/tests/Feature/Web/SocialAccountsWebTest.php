<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialAccountsWebTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_with_permission_can_store_account(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/accounts', [
                'name' => 'Demo Page',
                'platform' => 'facebook',
                'external_id' => '1234567890',
                'username' => 'demopage',
                'profile_url' => 'https://facebook.com/demopage',
                'comments_enabled' => true,
                'messages_enabled' => true,
                'auto_reply_enabled' => false,
            ]);

        $response->assertRedirect(route('helpdesksocial.accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'name' => 'Demo Page',
            'platform' => 'facebook',
            'external_id' => '1234567890',
            'connected_by_user_id' => $this->user->id,
        ]);
    }

    public function test_store_account_requires_valid_data(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');

        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/accounts', []);

        $response->assertSessionHasErrors(['name', 'platform', 'external_id']);
    }

    public function test_unauthorized_user_cannot_store_account(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/panel/helpdesk/social/accounts', [
                'name' => 'Demo Page',
                'platform' => 'facebook',
                'external_id' => '1234567890',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_update_account(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');
        $account = SocialAccount::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/accounts/{$account->id}", [
                'name' => 'Updated Name',
                'username' => 'newusername',
                'profile_url' => 'https://facebook.com/newusername',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('helpdesksocial.accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('helpdesk_social_accounts', [
            'id' => $account->id,
            'name' => 'Updated Name',
            'username' => 'newusername',
        ]);
    }

    public function test_unauthorized_user_cannot_update_account(): void
    {
        $account = SocialAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->put("/panel/helpdesk/social/accounts/{$account->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_destroy_account(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');
        $account = SocialAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/accounts/{$account->id}");

        $response->assertRedirect(route('helpdesksocial.accounts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_accounts', ['id' => $account->id]);
    }

    public function test_authorized_user_can_destroy_account(): void
    {
        $this->user->givePermissionTo('helpdesksocial.manage-accounts');

        $account = SocialAccount::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/panel/helpdesk/social/accounts/{$account->id}");

        $response->assertRedirect(route('helpdesksocial.accounts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('helpdesk_social_accounts', ['id' => $account->id]);
    }
}
