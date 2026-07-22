<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use Modules\HelpdeskAnalytics\Services\AnalyticsAggregatorService;

/**
 * Aislamiento por bandeja del dashboard de Analytics: un usuario sin
 * helpdesk.manage solo agrega sobre sus bandejas (AgentInboxCapacity, mismo
 * patrón que Conversation::scopeForAgent, fail-closed); los managers siguen
 * viendo todo. La clave de caché incluye el scope para que la vista global y
 * las restringidas nunca se contaminen entre sí.
 */
class AnalyticsInboxScopingTest extends HelpdeskTestCase
{
    private Inbox $inboxA;

    private Inbox $inboxB;

    private User $restrictedAgent;

    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->inboxA = Inbox::create(['name' => 'Scoping A '.uniqid(), 'channel_type' => Inbox::CHANNEL_WEB, 'is_active' => true]);
        $this->inboxB = Inbox::create(['name' => 'Scoping B '.uniqid(), 'channel_type' => Inbox::CHANNEL_WEB, 'is_active' => true]);

        $this->restrictedAgent = User::factory()->create();
        AgentInboxCapacity::create(['user_id' => $this->restrictedAgent->id, 'inbox_id' => $this->inboxA->id]);

        $this->managerUser = User::factory()->create();
        $this->managerUser->givePermissionTo('helpdesk.manage');
    }

    private function makeConversations(): void
    {
        $customer = Customer::factory()->create();

        Conversation::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxA->id,
        ]);

        Conversation::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxB->id,
        ]);
    }

    public function test_restricted_agent_only_aggregates_conversations_of_their_inboxes(): void
    {
        $this->makeConversations();

        $overview = app(AnalyticsAggregatorService::class)
            ->overview(now()->startOfMonth(), now()->endOfMonth(), $this->restrictedAgent);

        // Bandejas nuevas del test: los conteos del agente restringido son exactos.
        $this->assertSame(2, $overview['conversations']);
        $this->assertSame(2, $overview['open']);
    }

    public function test_manager_with_helpdesk_manage_sees_all_inboxes(): void
    {
        $this->makeConversations();

        $overview = app(AnalyticsAggregatorService::class)
            ->overview(now()->startOfMonth(), now()->endOfMonth(), $this->managerUser);

        // El manager agrega sin restricción: al menos las 5 del test.
        $this->assertGreaterThanOrEqual(5, $overview['conversations']);
    }

    public function test_restricted_agent_without_inboxes_sees_nothing(): void
    {
        $this->makeConversations();

        $orphan = User::factory()->create();

        $overview = app(AnalyticsAggregatorService::class)
            ->overview(now()->startOfMonth(), now()->endOfMonth(), $orphan);

        $this->assertSame(0, $overview['conversations']);
        $this->assertSame(0, $overview['open']);
    }

    public function test_agent_performance_only_lists_agents_of_accessible_inboxes(): void
    {
        $customer = Customer::factory()->create();
        $agentInA = User::factory()->create();
        $agentInB = User::factory()->create();

        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxA->id,
            'assignee_id' => $agentInA->id,
            'closed_at' => now(),
            'first_response_at' => now()->subHour(),
        ]);
        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxB->id,
            'assignee_id' => $agentInB->id,
            'closed_at' => now(),
            'first_response_at' => now()->subHour(),
        ]);

        $service = app(AnalyticsAggregatorService::class);
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $restricted = collect($service->agentPerformance($from, $to, $this->restrictedAgent));
        $restrictedNames = $restricted->pluck('name');

        $this->assertTrue($restrictedNames->contains(trim("{$agentInA->firstname} {$agentInA->lastname}")));
        $this->assertFalse($restrictedNames->contains(trim("{$agentInB->firstname} {$agentInB->lastname}")));

        // El manager ve a ambos agentes.
        $managerNames = collect($service->agentPerformance($from, $to, $this->managerUser))->pluck('name');
        $this->assertTrue($managerNames->contains(trim("{$agentInA->firstname} {$agentInA->lastname}")));
        $this->assertTrue($managerNames->contains(trim("{$agentInB->firstname} {$agentInB->lastname}")));
    }

    public function test_channel_distribution_is_scoped_for_restricted_agents(): void
    {
        $customer = Customer::factory()->create();

        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxA->id,
            'channel' => 'web',
        ]);
        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'inbox_id' => $this->inboxB->id,
            'channel' => 'whatsapp',
        ]);

        $channels = collect(app(AnalyticsAggregatorService::class)
            ->channelDistribution(now()->startOfMonth(), now()->endOfMonth(), $this->restrictedAgent));

        $this->assertSame(1, $channels->sum('count'));
        $this->assertSame('web', $channels->first()['channel']);
    }

    public function test_scoped_and_global_results_do_not_share_cache(): void
    {
        $this->makeConversations();

        $service = app(AnalyticsAggregatorService::class);
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        // Primero cachea la vista restringida; la global no debe reutilizarla.
        $restricted = $service->overview($from, $to, $this->restrictedAgent);
        $global = $service->overview($from, $to, $this->managerUser);

        $this->assertSame(2, $restricted['conversations']);
        $this->assertGreaterThanOrEqual(5, $global['conversations']);
    }

    public function test_ticket_metrics_are_fail_closed_for_restricted_agents(): void
    {
        $metrics = app(AnalyticsAggregatorService::class)
            ->ticketMetrics(now()->startOfMonth(), now()->endOfMonth(), $this->restrictedAgent);

        // Los tickets no tienen bandeja: un usuario restringido no ve métricas
        // globales de tickets (todas a cero).
        $this->assertSame(0, $metrics['total_created']);
        $this->assertSame([], $metrics['by_priority']);
    }
}
