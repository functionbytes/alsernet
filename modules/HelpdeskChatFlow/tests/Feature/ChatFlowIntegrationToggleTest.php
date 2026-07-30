<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Jobs\ExecuteChatFlowNodeJob;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the Settings → Integraciones kill switch
 * (`chatflow.integration_enabled`). Verifies that ConversationItemObserver,
 * the real entry point that triggers chat flow execution on every inbound
 * message, respects the admin toggle: no ExecuteChatFlowNodeJob may be
 * dispatched while the integration is disabled, even when an active flow
 * exists and would otherwise match.
 *
 * The "active flow exists" precondition is simulated by pre-warming
 * ChatFlow::hasActiveFlowsCached() directly instead of persisting a real
 * ChatFlow row: the `edges` column has no array cast on the model, which
 * makes factory-created flows fail to insert in this environment
 * (pre-existing issue, unrelated to this guard).
 */
class ChatFlowIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        Cache::put(ChatFlow::ACTIVE_FLOWS_CACHE_KEY, true, now()->addMinute());
    }

    public function test_inbound_message_does_not_trigger_chatflow_when_integration_disabled(): void
    {
        Setting::set('chatflow.integration_enabled', '0', 'integrations');

        $conversation = Conversation::factory()->create();

        Queue::fake();

        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'is_internal' => false,
            'user_id' => null,
        ]);

        Queue::assertNotPushed(ExecuteChatFlowNodeJob::class);
    }

    public function test_inbound_message_triggers_chatflow_when_integration_enabled_by_default(): void
    {
        $conversation = Conversation::factory()->create();

        Queue::fake();

        ConversationItem::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'is_internal' => false,
            'user_id' => null,
        ]);

        Queue::assertPushed(ExecuteChatFlowNodeJob::class);
    }

    /**
     * Hasta ahora solo el observer (arriba) respetaba el toggle — las rutas
     * HTTP del controller (store/update/destroy/takeOver/publish/...) eran
     * alcanzables aunque la integracion estuviera desactivada. takeOver() es
     * el caso mas grave: podia tomar el control de una conversacion en vivo.
     * Ahora todo el grupo de rutas pasa por integration.enabled:chatflow.
     */
    public function test_takeover_route_returns_404_when_integration_disabled(): void
    {
        $this->seed(ChatFlowPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $user->givePermissionTo('chatflow.update');

        $conversation = Conversation::factory()->create();

        Setting::set('chatflow.integration_enabled', '0', 'integrations');

        $this->actingAs($user)
            ->postJson("/panel/helpdesk/chatflows/takeover/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_takeover_route_reaches_the_controller_when_integration_enabled(): void
    {
        $this->seed(ChatFlowPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $user->givePermissionTo('chatflow.update');

        $conversation = Conversation::factory()->create(['metadata' => ['handled_by_bot' => false]]);

        Setting::set('chatflow.integration_enabled', '1', 'integrations');

        // La conversacion no esta en manos del bot -> 422 propio del
        // controller, NO 404 del middleware: prueba que la request SI llega
        // al controller cuando la integracion esta activa.
        $this->actingAs($user)
            ->postJson("/panel/helpdesk/chatflows/takeover/{$conversation->id}")
            ->assertStatus(422);
    }
}
