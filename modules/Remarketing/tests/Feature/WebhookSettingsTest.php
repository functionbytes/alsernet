<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Webhook;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebhookSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_webhooks')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }
    }

    public function test_guest_cannot_access_webhook_index(): void
    {
        $this->get(route('settings.remarketing.webhooks.index'))
            ->assertRedirect();
    }

    public function test_user_with_view_permission_can_access_webhook_index(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view']);
        $this->createStore($user);

        $this->actingAs($user)
            ->get(route('settings.remarketing.webhooks.index'))
            ->assertOk();
    }

    public function test_user_without_view_permission_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.remarketing.webhooks.index'))
            ->assertForbidden();
    }

    public function test_user_with_update_permission_can_create_webhook(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $store = $this->createStore($user);

        $this->actingAs($user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $store->id,
                'url' => 'https://example.com/my-hook',
                'events' => ['campaign.sent'],
            ])
            ->assertRedirect(route('settings.remarketing.webhooks.index'));

        $this->assertDatabaseHas('remarketing_webhooks', [
            'store_id' => $store->id,
            'url' => 'https://example.com/my-hook',
        ]);
    }

    public function test_user_without_update_permission_cannot_create_webhook(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view']);
        $store = $this->createStore($user);

        $this->actingAs($user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $store->id,
                'url' => 'https://example.com/hook',
                'events' => ['order.created'],
            ])
            ->assertForbidden();
    }

    public function test_store_webhook_validates_required_fields(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $this->createStore($user);

        $this->actingAs($user)
            ->post(route('settings.remarketing.webhooks.store'), [])
            ->assertSessionHasErrors(['store_id', 'url', 'events']);
    }

    public function test_store_webhook_validates_url_format(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $store = $this->createStore($user);

        $this->actingAs($user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $store->id,
                'url' => 'not-a-valid-url',
                'events' => ['order.created'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_user_with_update_permission_can_delete_own_webhook(): void
    {
        $user = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $store = $this->createStore($user);
        $webhook = $this->createWebhook($store);

        $this->actingAs($user)
            ->delete(route('settings.remarketing.webhooks.destroy', $webhook))
            ->assertRedirect(route('settings.remarketing.webhooks.index'));

        $this->assertDatabaseMissing('remarketing_webhooks', ['id' => $webhook->id]);
    }

    public function test_user_cannot_delete_webhook_belonging_to_another_user(): void
    {
        $owner = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $other = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);

        $store = $this->createStore($owner);
        $webhook = $this->createWebhook($store);

        $this->actingAs($other)
            ->delete(route('settings.remarketing.webhooks.destroy', $webhook))
            ->assertForbidden();

        $this->assertDatabaseHas('remarketing_webhooks', ['id' => $webhook->id]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createStore(User $user): Store
    {
        return Store::query()->create([
            'user_id' => $user->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);
    }

    private function createWebhook(Store $store, array $attributes = []): Webhook
    {
        return Webhook::query()->create(array_merge([
            'store_id' => $store->id,
            'url' => 'https://example.com/hook-'.uniqid(),
            'events' => ['order.created'],
            'is_active' => true,
        ], $attributes));
    }
}
