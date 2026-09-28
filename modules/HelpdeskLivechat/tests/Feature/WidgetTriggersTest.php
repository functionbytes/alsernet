<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Modules\HelpdeskLivechat\Database\Seeders\HelpdeskLivechatPermissionsSeeder;
use Modules\HelpdeskLivechat\Models\WidgetTrigger;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Disparadores proactivos del chat web (live commerce, fase 5).
 */
class WidgetTriggersTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(HelpdeskLivechatPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('helpdesk.livechat.triggers.manage');
    }

    private function payload(int $webId, array $overrides = []): array
    {
        return array_merge([
            'web_id' => $webId,
            'name' => 'Carrito alto',
            'priority' => 80,
            'match' => 'all',
            'action' => 'message',
            'message' => '¿Te ayudo a terminar el pedido?',
            'frequency' => 'once_visitor',
            'is_active' => '1',
            'conditions' => [
                ['type' => 'cart_value', 'op' => 'gte', 'value' => '100'],
                ['type' => 'page_time', 'op' => 'gte', 'value' => '20'],
                ['type' => 'url', 'op' => 'contains', 'value' => '/pedido||/carrito'],
            ],
        ], $overrides);
    }

    public function test_admin_pages_require_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.helpdesk-livechat.triggers.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('settings.helpdesk-livechat.triggers.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('settings.helpdesk-livechat.triggers.create'))->assertOk();
    }

    public function test_admin_creates_trigger(): void
    {
        $web = WebFactory::new()->create();

        $this->actingAs($this->admin)
            ->post(route('settings.helpdesk-livechat.triggers.store'), $this->payload($web->id))
            ->assertRedirect(route('settings.helpdesk-livechat.triggers.index'));

        $trigger = WidgetTrigger::where('web_id', $web->id)->firstOrFail();
        $this->assertSame('message', $trigger->action);
        $this->assertCount(3, $trigger->conditions);
        $this->assertTrue($trigger->is_active);
    }

    public function test_invalid_conditions_are_rejected(): void
    {
        $web = WebFactory::new()->create();

        foreach ([
            ['type' => 'cart_value', 'op' => 'contains', 'value' => '100'],
            ['type' => 'hour_range', 'op' => 'between', 'value' => '14-9'],
            ['type' => 'weekday', 'op' => 'in', 'value' => '0,8'],
            ['type' => 'locale', 'op' => 'eq', 'value' => 'español'],
            ['type' => 'hacker', 'op' => 'eq', 'value' => 'x'],
        ] as $bad) {
            $this->actingAs($this->admin)
                ->post(route('settings.helpdesk-livechat.triggers.store'), $this->payload($web->id, ['conditions' => [$bad]]))
                ->assertSessionHasErrors();
        }

        $this->assertSame(0, WidgetTrigger::where('web_id', $web->id)->count());
    }

    public function test_message_action_requires_message(): void
    {
        $web = WebFactory::new()->create();

        $this->actingAs($this->admin)
            ->post(route('settings.helpdesk-livechat.triggers.store'), $this->payload($web->id, ['message' => '']))
            ->assertSessionHasErrors('message');
    }

    public function test_public_endpoint_returns_only_active_triggers_of_the_channel(): void
    {
        $web = WebFactory::new()->create();
        $other = WebFactory::new()->create();
        WidgetTrigger::create(['web_id' => $web->id, 'name' => 'Activo', 'is_active' => true, 'priority' => 10, 'match' => 'all',
            'conditions' => [['type' => 'page_time', 'op' => 'gte', 'value' => '5']], 'action' => 'open_chat', 'frequency' => 'once_session']);
        WidgetTrigger::create(['web_id' => $web->id, 'name' => 'Inactivo', 'is_active' => false, 'priority' => 99, 'match' => 'all',
            'conditions' => [['type' => 'page_time', 'op' => 'gte', 'value' => '5']], 'action' => 'open_chat', 'frequency' => 'once_session']);
        WidgetTrigger::create(['web_id' => $other->id, 'name' => 'Otro canal', 'is_active' => true, 'priority' => 99, 'match' => 'all',
            'conditions' => [['type' => 'page_time', 'op' => 'gte', 'value' => '5']], 'action' => 'open_chat', 'frequency' => 'once_session']);

        $response = $this->getJson(route('helpdesk-livechat.widget.triggers', ['website_token' => $web->website_token]))
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertArrayNotHasKey('name', $response->json('data.0'));
        $this->assertSame('open_chat', $response->json('data.0.action'));
    }

    public function test_unknown_token_is_404(): void
    {
        $this->getJson(route('helpdesk-livechat.widget.triggers', ['website_token' => 'nope']))->assertNotFound();
    }

    public function test_saving_a_trigger_refreshes_the_widget_cache(): void
    {
        $web = WebFactory::new()->create();
        $url = route('helpdesk-livechat.widget.triggers', ['website_token' => $web->website_token]);
        $this->assertCount(0, $this->getJson($url)->json('data'));

        WidgetTrigger::create(['web_id' => $web->id, 'name' => 'Nuevo', 'is_active' => true, 'priority' => 10, 'match' => 'all',
            'conditions' => [['type' => 'site_time', 'op' => 'gte', 'value' => '60']], 'action' => 'open_chat', 'frequency' => 'once_visitor']);

        $this->assertCount(1, $this->getJson($url)->json('data'));
    }

    public function test_message_translations_are_saved_and_served(): void
    {
        $web = WebFactory::new()->create();

        $this->actingAs($this->admin)
            ->post(route('settings.helpdesk-livechat.triggers.store'), $this->payload($web->id, [
                'messages' => ['en' => 'Need help finishing your order?', 'fr' => '', 'xx' => 'ignorado'],
            ]))->assertRedirect();

        $trigger = WidgetTrigger::where('web_id', $web->id)->firstOrFail();
        $this->assertSame(['en' => 'Need help finishing your order?'], $trigger->messages);

        $data = $this->getJson(route('helpdesk-livechat.widget.triggers', ['website_token' => $web->website_token]))->json('data.0');
        $this->assertSame('Need help finishing your order?', $data['messages']['en']);
    }
}
