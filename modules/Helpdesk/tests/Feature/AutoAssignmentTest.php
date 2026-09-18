<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Spatie\Permission\Models\Role;

/**
 * Global auto-assignment strategy (#78 ve-auto-assign): service strategies +
 * runtime-editable config endpoint.
 */
class AutoAssignmentTest extends HelpdeskTestCase
{
    private AutoAssignmentService $service;

    private Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoAssignmentService::class);
        $this->inbox = Inbox::create(['name' => 'Test Inbox', 'channel_type' => Inbox::CHANNEL_WEB]);
    }

    private function inboxAgent(): User
    {
        $user = User::factory()->create();
        AgentInboxCapacity::create([
            'user_id' => $user->id, 'inbox_id' => $this->inbox->id,
            'max_concurrent' => 5, 'accepts_new' => true,
        ]);

        return $user;
    }

    private function conversation(?int $assigneeId = null): Conversation
    {
        $conversation = Conversation::factory()->create([
            'inbox_id' => $this->inbox->id,
            'status_id' => $this->openStatus->id,
            'assignee_id' => $assigneeId,
        ]);

        return $conversation;
    }

    // ─── service ─────────────────────────────────────────────────────────────

    public function test_config_defaults_to_config_file(): void
    {
        $this->assertSame('round_robin', $this->service->config()['strategy']);
        $this->assertSame('off', $this->service->config()['retry']);
    }

    public function test_save_config_persists_to_settings(): void
    {
        $this->service->saveConfig('least_load', '5', 'supervisor', true);

        $config = $this->service->config();
        $this->assertSame('least_load', $config['strategy']);
        $this->assertSame('5', $config['retry']);
        $this->assertSame('supervisor', $config['fallback']);
        $this->assertTrue($config['enabled']);
    }

    public function test_enabled_falls_back_to_config_when_never_set(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', true);
        $this->assertTrue($this->service->isEnabled());

        config()->set('helpdesk.auto_assignment.enabled', false);
        $this->assertFalse($this->service->isEnabled());
    }

    public function test_stored_enabled_overrides_config_default(): void
    {
        config()->set('helpdesk.auto_assignment.enabled', false);
        $this->service->saveConfig('round_robin', 'off', 'queue', true);

        $this->assertTrue($this->service->isEnabled());
    }

    public function test_manual_strategy_resolves_no_agent(): void
    {
        $this->service->saveConfig('manual', 'off', 'queue');

        $this->assertNull($this->service->resolveAgent($this->conversation()));
    }

    public function test_round_robin_picks_an_available_inbox_agent(): void
    {
        $agent = $this->inboxAgent();
        $this->service->saveConfig('round_robin', 'off', 'queue');

        $this->assertSame($agent->id, $this->service->resolveAgent($this->conversation()));
    }

    public function test_least_load_picks_agent_with_fewest_open(): void
    {
        $busy = $this->inboxAgent();
        $free = $this->inboxAgent();
        // El agente "busy" ya tiene una conversación abierta asignada.
        $this->conversation($busy->id);

        $this->service->saveConfig('least_load', 'off', 'queue');

        $this->assertSame($free->id, $this->service->resolveAgent($this->conversation()));
    }

    public function test_resolves_null_when_inbox_has_no_agents(): void
    {
        $this->service->saveConfig('round_robin', 'off', 'queue');

        $this->assertNull($this->service->resolveAgent($this->conversation()));
    }

    private function agentWithPresence(string $presence): User
    {
        $user = $this->inboxAgent();
        AgentSettings::create([
            'user_id' => $user->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => $presence,
        ]);

        return $user;
    }

    public function test_away_and_busy_agents_are_excluded_from_auto_assignment(): void
    {
        $this->service->saveConfig('round_robin', 'off', 'queue');

        // Único agente del inbox ausente → nadie recibe auto-asignación.
        $this->agentWithPresence(AgentSettings::PRESENCE_AWAY);
        $this->assertNull($this->service->resolveAgent($this->conversation()));

        // En descanso tampoco es elegible.
        $this->agentWithPresence(AgentSettings::PRESENCE_BUSY);
        $this->assertNull($this->service->resolveAgent($this->conversation()));

        // Un compañero disponible sí lo es.
        $available = $this->agentWithPresence(AgentSettings::PRESENCE_AVAILABLE);
        $this->assertSame($available->id, $this->service->resolveAgent($this->conversation()));
    }

    public function test_manual_strategy_does_not_fall_back_to_supervisor(): void
    {
        Role::firstOrCreate(['name' => 'helpdesk-admin', 'guard_name' => 'web']);
        $supervisor = User::factory()->create();
        $supervisor->assignRole('helpdesk-admin');

        $this->service->saveConfig('manual', 'off', 'supervisor');

        $conversation = $this->conversation();
        $this->service->applyFallback($conversation);

        $this->assertNull($conversation->fresh()->assignee_id);
    }

    public function test_supervisor_fallback_assigns_to_an_admin_role(): void
    {
        Role::firstOrCreate(['name' => 'helpdesk-admin', 'guard_name' => 'web']);
        $supervisor = User::factory()->create();
        $supervisor->assignRole('helpdesk-admin');

        $this->service->saveConfig('round_robin', 'off', 'supervisor');

        $conversation = $this->conversation();
        $this->service->applyFallback($conversation);

        $this->assertSame($supervisor->id, $conversation->fresh()->assignee_id);
    }

    // ─── endpoint ────────────────────────────────────────────────────────────

    public function test_show_endpoint_returns_config(): void
    {
        $this->manager->givePermissionTo('helpdesk.manage');

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.auto-assignment.show'))
            ->assertOk()
            ->assertJsonStructure(['strategy', 'retry', 'fallback', 'enabled']);
    }

    public function test_update_endpoint_persists_strategy(): void
    {
        $this->manager->givePermissionTo('helpdesk.manage');

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.auto-assignment.update'), [
                'strategy' => 'skills', 'retry' => '2', 'fallback' => 'queue',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('skills', Setting::get('auto_assign.strategy'));
    }

    public function test_update_endpoint_rejects_invalid_strategy(): void
    {
        $this->manager->givePermissionTo('helpdesk.manage');

        $this->actingAs($this->manager)
            ->putJson(route('manager.helpdesk.auto-assignment.update'), [
                'strategy' => 'nonsense', 'retry' => 'off', 'fallback' => 'queue',
            ])
            ->assertStatus(422);
    }
}
