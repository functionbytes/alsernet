<?php

namespace Modules\Engagement\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Tests\TestCase;
use Modules\Helpdesk\Models\Inbox;
use Spatie\Permission\Models\Permission;

class IntegrationLookupControllerTest extends TestCase
{
    private Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        $web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi',
            'welcome_tagline' => 'Hello',
        ]);

        $this->inbox = Inbox::create([
            'name' => 'Test',
            'channel_type' => 'web',
            'channel_id' => $web->id,
            'is_active' => true,
        ]);

        Permission::firstOrCreate(['name' => 'helpdesk.conversations.view', 'guard_name' => 'web']);
    }

    private function lookupUrl(): string
    {
        return route('manager.engagement.customer-data.lookup');
    }

    private function userWithPermission(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('helpdesk.conversations.view');

        return $user;
    }

    private function createIntegration(string $platform): PlatformIntegration
    {
        return PlatformIntegration::query()->create([
            'inbox_id' => $this->inbox->id,
            'platform' => $platform,
            'store_url' => 'https://shop.example.com',
            'webhook_secret' => Str::random(64),
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson($this->lookupUrl(), [])->assertUnauthorized();
    }

    public function test_authenticated_without_permission_returns_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => ['email' => 'x@y.com'],
            ])
            ->assertForbidden();
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['inbox_id', 'actions', 'lookup']);
    }

    public function test_validates_lookup_requires_email_or_external_id(): void
    {
        $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lookup.email']);
    }

    public function test_validates_actions_must_be_known(): void
    {
        $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['unknown_action'],
                'lookup' => ['email' => 'x@y.com'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['actions.0']);
    }

    public function test_returns_grouped_results_per_platform(): void
    {
        $this->createIntegration('prestashop');

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => ['id' => 1]], 200),
        ]);

        $response = $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => ['email' => 'x@y.com'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $response->assertJsonPath('data.prestashop.profile.ok', true);
    }

    public function test_filter_by_platform_only_calls_one_source(): void
    {
        $this->createIntegration('prestashop');
        $this->createIntegration('erp');

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => []], 200),
        ]);

        $response = $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => ['email' => 'x@y.com'],
                'platform' => 'prestashop',
            ])
            ->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('prestashop', $data);
        $this->assertArrayNotHasKey('erp', $data);
    }

    public function test_meta_contains_inbox_id_and_platforms(): void
    {
        $this->createIntegration('prestashop');

        Http::fake([
            '*alsernet_chat/api.php' => Http::response(['ok' => true, 'data' => []], 200),
        ]);

        $response = $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => ['email' => 'x@y.com'],
            ])
            ->assertOk();

        $this->assertSame($this->inbox->id, $response->json('meta.inbox_id'));
        $this->assertContains('prestashop', $response->json('meta.platforms'));
    }

    public function test_returns_empty_data_when_no_integrations_exist(): void
    {
        $response = $this->actingAs($this->userWithPermission())
            ->postJson($this->lookupUrl(), [
                'inbox_id' => $this->inbox->id,
                'actions' => ['profile'],
                'lookup' => ['email' => 'x@y.com'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([], $response->json('data'));
    }
}
