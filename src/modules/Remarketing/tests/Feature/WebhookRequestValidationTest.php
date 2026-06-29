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

class WebhookRequestValidationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_webhooks')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->user = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $this->store = $this->createStore($this->user);
    }

    // ─── StoreWebhookRequest: SSRF / URL validation ───────────────────────────

    public function test_store_rejects_http_url(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $this->store->id,
                'url' => 'http://example.com/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_store_rejects_localhost_url(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $this->store->id,
                'url' => 'https://localhost/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_store_rejects_raw_private_ip_url(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $this->store->id,
                'url' => 'https://192.168.1.100/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_store_rejects_invalid_event(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $this->store->id,
                'url' => 'https://example.com/hook',
                'events' => ['not.a.valid.event'],
            ])
            ->assertSessionHasErrors(['events.0']);
    }

    public function test_store_accepts_wildcard_event(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $this->store->id,
                'url' => 'https://example.com/hook-wildcard',
                'events' => ['*'],
            ])
            ->assertRedirect(route('settings.remarketing.webhooks.index'));
    }

    public function test_store_rejects_store_belonging_to_other_user(): void
    {
        $other = $this->userWithPermissions(['remarketing.settings.view', 'remarketing.settings.update']);
        $otherStore = $this->createStore($other);

        $this->actingAs($this->user)
            ->post(route('settings.remarketing.webhooks.store'), [
                'store_id' => $otherStore->id,
                'url' => 'https://example.com/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['store_id']);
    }

    // ─── UpdateWebhookRequest: SSRF / URL validation ──────────────────────────

    public function test_update_rejects_http_url(): void
    {
        $webhook = $this->createWebhook($this->store);

        $this->actingAs($this->user)
            ->put(route('settings.remarketing.webhooks.update', $webhook), [
                'url' => 'http://example.com/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_update_rejects_localhost_url(): void
    {
        $webhook = $this->createWebhook($this->store);

        $this->actingAs($this->user)
            ->put(route('settings.remarketing.webhooks.update', $webhook), [
                'url' => 'https://localhost/hook',
                'events' => ['campaign.sent'],
            ])
            ->assertSessionHasErrors(['url']);
    }

    public function test_update_rejects_invalid_event(): void
    {
        $webhook = $this->createWebhook($this->store);

        $this->actingAs($this->user)
            ->put(route('settings.remarketing.webhooks.update', $webhook), [
                'url' => 'https://example.com/hook',
                'events' => ['evil.event'],
            ])
            ->assertSessionHasErrors(['events.0']);
    }

    public function test_update_accepts_valid_https_url_and_event(): void
    {
        $webhook = $this->createWebhook($this->store);

        $this->actingAs($this->user)
            ->put(route('settings.remarketing.webhooks.update', $webhook), [
                'url' => 'https://example.com/updated-hook',
                'events' => ['message.clicked', 'message.bounced'],
                'is_active' => true,
            ])
            ->assertRedirect(route('settings.remarketing.webhooks.index'));

        $this->assertDatabaseHas('remarketing_webhooks', [
            'id' => $webhook->id,
            'url' => 'https://example.com/updated-hook',
        ]);
    }

    // ─── Webhook model: secret hidden ─────────────────────────────────────────

    public function test_webhook_secret_is_not_serialized_to_array(): void
    {
        $webhook = $this->createWebhook($this->store, ['secret' => 'supersecret123']);

        $array = $webhook->toArray();

        $this->assertArrayNotHasKey('secret', $array);
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
            'events' => ['campaign.sent'],
            'is_active' => true,
        ], $attributes));
    }
}
