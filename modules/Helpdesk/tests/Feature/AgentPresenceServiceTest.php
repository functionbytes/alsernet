<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Jobs\ReturnAgentToAvailableJob;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Services\AgentPresenceService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for AgentPresenceService and the presence endpoints.
 *
 * hasAvailableAgentsForInbox() is the gate that decides whether the livechat
 * widget shows "online" or "offline" to real customers. "Online" requires BOTH
 * a fresh helpdesk_agent_settings.last_heartbeat_at row AND a live Redis TTL
 * key (heartbeat() sets both) — real Redis is used here (not faked) since the
 * gate's whole point is to check that TTL.
 *
 * Also covers the away-mode feature (#60): getSettings(), setState() persisting
 * away-mode preferences, reassignActiveConversations() releasing open
 * conversations to the queue, and the presence/me + presence/state endpoints.
 */
class AgentPresenceServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private AgentPresenceService $service;

    private Inbox $inbox;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing usa CACHE_STORE=array/QUEUE_CONNECTION=sync a
        // proposito (evita depender de Redis en la mayoria de tests) y por
        // eso no define REDIS_PASSWORD — pero este servicio usa el facade
        // Redis directamente (TTL real), asi que aqui si hace falta.
        config(['database.redis.default.password' => '6cWRY1PUmiwYciQxJXkg']);

        $this->service = app(AgentPresenceService::class);
        // Inbox no tiene factory registrada pese a usar HasFactory — create()
        // directo con los 2 campos NOT NULL sin default (uid se autogenera
        // en booted()).
        $this->inbox = Inbox::create(['name' => 'Test Inbox', 'channel_type' => Inbox::CHANNEL_WEB]);
    }

    protected function tearDown(): void
    {
        Redis::del('helpdesk:presence:agents:'.($this->agentUserId ?? 0));

        parent::tearDown();
    }

    private ?int $agentUserId = null;

    /**
     * Marks the agent "online" the same way heartbeat() does: a live Redis
     * TTL key plus a fresh last_heartbeat_at row.
     */
    private function makeOnlineAgent(string $presenceState, ?int $inboxId = null): User
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;

        AgentSettings::query()->create([
            'user_id' => $user->id,
            'presence_state' => $presenceState,
            'last_heartbeat_at' => now(),
        ]);

        Redis::setex('helpdesk:presence:agents:'.$user->id, 90, 1);

        if ($inboxId !== null) {
            AgentInboxCapacity::query()->create([
                'user_id' => $user->id,
                'inbox_id' => $inboxId,
                'max_concurrent' => 5,
                'accepts_new' => true,
            ]);
        }

        return $user;
    }

    /**
     * A user that can reach the manager panel (routes/managers.php requires
     * can:helpdesk.view). Mirrors ConversationsControllerTest's setup.
     */
    private function managerAgent(): User
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']));
        $this->agentUserId = $user->id;

        return $user;
    }

    public function test_returns_false_when_no_agents_are_online(): void
    {
        $this->assertFalse($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    public function test_returns_false_when_online_agent_is_not_assigned_to_this_inbox(): void
    {
        $otherInbox = Inbox::create(['name' => 'Other Inbox', 'channel_type' => Inbox::CHANNEL_WEB]);
        $this->makeOnlineAgent(AgentSettings::PRESENCE_AVAILABLE, $otherInbox->id);

        $this->assertFalse($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    public function test_returns_false_when_assigned_agent_is_away(): void
    {
        $this->makeOnlineAgent(AgentSettings::PRESENCE_AWAY, $this->inbox->id);

        $this->assertFalse($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    public function test_returns_false_when_heartbeat_row_is_fresh_but_redis_ttl_expired(): void
    {
        $user = User::factory()->create();

        AgentSettings::query()->create([
            'user_id' => $user->id,
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'last_heartbeat_at' => now(),
        ]);

        AgentInboxCapacity::query()->create([
            'user_id' => $user->id,
            'inbox_id' => $this->inbox->id,
            'max_concurrent' => 5,
            'accepts_new' => true,
        ]);

        // Sin heartbeat() real, no hay clave Redis viva — getOnlineAgents()
        // exige AMBAS señales, no solo el timestamp en BD.
        $this->assertFalse($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    public function test_returns_true_when_assigned_agent_is_available(): void
    {
        $this->makeOnlineAgent(AgentSettings::PRESENCE_AVAILABLE, $this->inbox->id);

        $this->assertTrue($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    public function test_returns_true_when_assigned_agent_is_busy(): void
    {
        $this->makeOnlineAgent(AgentSettings::PRESENCE_BUSY, $this->inbox->id);

        $this->assertTrue($this->service->hasAvailableAgentsForInbox($this->inbox->id));
    }

    // ─── getSettings + away-mode preferences (#60 ve-away-mode) ──────────────

    public function test_get_settings_returns_defaults_when_no_row_exists(): void
    {
        $user = User::factory()->create();

        $settings = $this->service->getSettings($user->id);

        $this->assertSame(AgentSettings::PRESENCE_OFFLINE, $settings['raw_state']);
        $this->assertSame('', $settings['away_message']);
        $this->assertSame('manual', $settings['auto_return']);
        $this->assertSame('keep', $settings['reassign']);
    }

    public function test_set_state_persists_away_preferences(): void
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;

        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY, [
            'away_message' => 'Vuelvo en una hora.',
            'auto_return' => '1h',
            'reassign' => 'team',
        ]);

        $settings = $this->service->getSettings($user->id);

        $this->assertSame(AgentSettings::PRESENCE_AWAY, $settings['raw_state']);
        $this->assertSame('Vuelvo en una hora.', $settings['away_message']);
        $this->assertSame('1h', $settings['auto_return']);
        $this->assertSame('team', $settings['reassign']);
    }

    public function test_set_state_saves_preferences_even_when_state_is_unchanged(): void
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;

        // Marca "away" con un mensaje inicial.
        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY, ['away_message' => 'Primer mensaje']);
        // El estado no cambia, pero el mensaje sí debe actualizarse: el
        // early-return de "mismo estado" no debe saltarse el guardado de prefs.
        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY, ['away_message' => 'Mensaje nuevo']);

        $this->assertSame('Mensaje nuevo', $this->service->getSettings($user->id)['away_message']);
    }

    // ─── reassignActiveConversations (#60 "reasignar mis activas") ────────────

    public function test_reassign_releases_open_conversations_but_leaves_closed_and_others(): void
    {
        $open = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $closed = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#9ca3af', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );

        $agent = User::factory()->create();
        $other = User::factory()->create();
        $this->agentUserId = $agent->id;

        $agentOpen = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);
        $agentClosed = Conversation::factory()->create([
            'status_id' => $closed->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);
        $otherOpen = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $other->id, 'inbox_id' => $this->inbox->id,
        ]);

        $released = $this->service->reassignActiveConversations($agent->id);

        $this->assertSame(1, $released);
        $this->assertNull($agentOpen->fresh()->assignee_id, 'la conversación abierta del agente se libera');
        $this->assertSame($agent->id, $agentClosed->fresh()->assignee_id, 'la cerrada del agente NO se toca');
        $this->assertSame($other->id, $otherOpen->fresh()->assignee_id, 'la de otro agente NO se toca');
    }

    public function test_reassign_to_specific_agent_hands_off_open_conversations(): void
    {
        $open = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $agent = User::factory()->create();
        $target = User::factory()->create();
        $this->agentUserId = $agent->id;

        $agentOpen = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);

        $released = $this->service->reassignActiveConversations($agent->id, $target->id);

        $this->assertSame(1, $released);
        $this->assertSame($target->id, $agentOpen->fresh()->assignee_id, 'la conversación pasa al agente elegido, no queda sin asignar');
    }

    // ─── Endpoint HTTP (AgentPresenceController) ──────────────────────────────

    public function test_me_endpoint_returns_current_settings(): void
    {
        $agent = $this->managerAgent();
        $this->service->setState($agent->id, AgentSettings::PRESENCE_BUSY, [
            'away_message' => 'Ocupado', 'auto_return' => '4h', 'reassign' => 'team',
        ]);

        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.presence.me'))
            ->assertOk()
            ->assertJson([
                'raw_state' => AgentSettings::PRESENCE_BUSY,
                'away_message' => 'Ocupado',
                'auto_return' => '4h',
                'reassign' => 'team',
            ]);
    }

    public function test_state_endpoint_releases_conversations_when_going_away_with_reassign_team(): void
    {
        $open = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $agent = $this->managerAgent();
        $conv = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'reassign' => 'team',
                'away_message' => 'Fuera un rato',
                'auto_return' => '1h',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'state' => AgentSettings::PRESENCE_AWAY, 'reassigned' => 1]);

        $this->assertNull($conv->fresh()->assignee_id);
    }

    public function test_state_endpoint_reassigns_to_specific_agent(): void
    {
        $open = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $agent = $this->managerAgent();
        $target = User::factory()->create();
        $conv = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'reassign' => 'agent',
                'reassign_agent_id' => $target->id,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'state' => AgentSettings::PRESENCE_AWAY, 'reassigned' => 1]);

        $this->assertSame($target->id, $conv->fresh()->assignee_id);
        $this->assertSame('agent', $this->service->getSettings($agent->id)['reassign']);
        $this->assertSame($target->id, $this->service->getSettings($agent->id)['reassign_agent_id']);
    }

    public function test_state_endpoint_rejects_reassign_agent_without_agent_id(): void
    {
        $agent = $this->managerAgent();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'reassign' => 'agent',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reassign_agent_id']);
    }

    public function test_state_endpoint_keeps_conversations_when_reassign_is_keep(): void
    {
        $open = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $agent = $this->managerAgent();
        $conv = Conversation::factory()->create([
            'status_id' => $open->id, 'assignee_id' => $agent->id, 'inbox_id' => $this->inbox->id,
        ]);

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'reassign' => 'keep',
            ])
            ->assertOk()
            ->assertJson(['reassigned' => 0]);

        $this->assertSame($agent->id, $conv->fresh()->assignee_id);
    }

    public function test_state_endpoint_rejects_invalid_state(): void
    {
        $agent = $this->managerAgent();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), ['state' => 'invalid-state'])
            ->assertStatus(422);
    }

    // ─── auto-return (#60 "volver automáticamente") ──────────────────────────

    public function test_state_endpoint_schedules_auto_return_when_not_manual(): void
    {
        Queue::fake();
        $agent = $this->managerAgent();

        $response = $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'auto_return' => '1h',
            ])
            ->assertOk();

        $this->assertNotNull($response->json('auto_return_at'));
        Queue::assertPushed(ReturnAgentToAvailableJob::class);
    }

    public function test_state_endpoint_does_not_schedule_auto_return_when_manual(): void
    {
        Queue::fake();
        $agent = $this->managerAgent();

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.presence.state'), [
                'state' => AgentSettings::PRESENCE_AWAY,
                'auto_return' => 'manual',
            ])
            ->assertOk()
            ->assertJson(['auto_return_at' => null]);

        Queue::assertNotPushed(ReturnAgentToAvailableJob::class);
    }

    public function test_auto_return_tomorrow_uses_agent_timezone(): void
    {
        Queue::fake();
        $agent = $this->managerAgent();
        // 2026-07-06 20:00 UTC = 16:00 EDT (America/New_York, UTC-4 en julio).
        Carbon::setTestNow('2026-07-06 20:00:00');

        try {
            $response = $this->actingAs($agent)
                ->postJson(route('manager.helpdesk.presence.state'), [
                    'state' => AgentSettings::PRESENCE_AWAY,
                    'auto_return' => 'tomorrow',
                    'timezone' => 'America/New_York',
                ])
                ->assertOk();

            // Mañana 09:00 EDT = 2026-07-07 13:00 UTC.
            $returnAt = Carbon::parse($response->json('auto_return_at'));
            $this->assertSame('2026-07-07 13:00:00', $returnAt->utc()->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_return_job_flips_agent_back_to_available(): void
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;
        $stamp = '2026-07-06T15:00:00+00:00';
        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY, ['auto_return_at' => $stamp]);

        (new ReturnAgentToAvailableJob($user->id, $stamp))->handle($this->service);

        $this->assertSame(AgentSettings::PRESENCE_AVAILABLE, $this->service->getSettings($user->id)['raw_state']);
    }

    public function test_return_job_is_noop_when_agent_already_available(): void
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;
        $this->service->setState($user->id, AgentSettings::PRESENCE_AVAILABLE);

        (new ReturnAgentToAvailableJob($user->id, 'any-stamp'))->handle($this->service);

        $this->assertSame(AgentSettings::PRESENCE_AVAILABLE, $this->service->getSettings($user->id)['raw_state']);
    }

    public function test_return_job_is_noop_when_agent_rescheduled(): void
    {
        $user = User::factory()->create();
        $this->agentUserId = $user->id;
        // El agente re-programó su vuelta con un timestamp nuevo.
        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY, ['auto_return_at' => 'new-stamp']);

        // Un job viejo (timestamp distinto) no debe devolverlo a disponible.
        (new ReturnAgentToAvailableJob($user->id, 'old-stamp'))->handle($this->service);

        $this->assertSame(AgentSettings::PRESENCE_AWAY, $this->service->getSettings($user->id)['raw_state']);
    }

    // ─── getAgentsList (presencia en vivo — dots de agentes) ─────────────────

    public function test_get_agents_list_returns_state_and_name_without_error(): void
    {
        // Regresión: antes petaba con "Unknown column 'name'" (User usa
        // firstname/lastname + accessor, no una columna name).
        $user = User::factory()->create();
        $this->agentUserId = $user->id;
        $this->service->setState($user->id, AgentSettings::PRESENCE_AWAY);

        $found = collect($this->service->getAgentsList())->firstWhere('user_id', $user->id);

        $this->assertNotNull($found);
        $this->assertSame(AgentSettings::PRESENCE_AWAY, $found['presence_state']);
        $this->assertArrayHasKey('name', $found);
    }
}
