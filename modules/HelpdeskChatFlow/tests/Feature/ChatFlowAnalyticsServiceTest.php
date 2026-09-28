<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAnalyticsService;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * buildDropOff() (subquery instead of plucking every session id), buildCsatTrend()
 * (weekly buckets over the csat_score_value index) and buildNodeLatency() +
 * buildHttpFailureAlerts() (per-node duration/failure aggregation and the
 * flaky-webhook alert threshold).
 */
class ChatFlowAnalyticsServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private ChatFlowAnalyticsService $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analytics = app(ChatFlowAnalyticsService::class);

        try {
            \DB::connection('helpdesk')->statement('SELECT csat_score_value FROM helpdesk_chat_flow_sessions LIMIT 1');
        } catch (\Throwable) {
            $this->markTestSkipped('helpdesk_chat_flow_sessions.csat_score_value not available in test DB.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeSession(ChatFlow $flow, array $attributes = []): ChatFlowSession
    {
        return ChatFlowSession::create(array_merge([
            'chat_flow_id' => $flow->id,
            'conversation_id' => 1,
            'status' => 'completed',
            'trigger_type' => 'conversation_start',
            'context' => [],
            'started_at' => now(),
        ], $attributes));
    }

    private function makeExecution(
        ChatFlowSession $session,
        string $nodeId,
        string $nodeType = 'action',
        string $status = 'success',
        ?int $durationMs = 100,
    ): ChatFlowExecution {
        return ChatFlowExecution::create([
            'session_id' => $session->id,
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'status' => $status,
            'duration_ms' => $durationMs,
            'executed_at' => now(),
        ]);
    }

    // ==================== buildDropOff ====================

    public function test_drop_off_returns_empty_array_when_flow_has_no_sessions(): void
    {
        $flow = ChatFlow::factory()->create();

        $this->assertSame([], $this->analytics->buildDropOff($flow));
    }

    public function test_drop_off_matches_expected_rates_without_plucking_all_session_ids(): void
    {
        $flow = ChatFlow::factory()->create([
            'nodes' => [
                ['id' => 'n1', 'type' => 'start', 'label' => 'Inicio'],
                ['id' => 'n2', 'type' => 'question', 'label' => 'Pregunta'],
                ['id' => 'n3', 'type' => 'end', 'label' => 'Fin'],
            ],
        ]);

        $completed = $this->makeSession($flow, ['status' => 'completed']);
        $abandonedAtQuestion = $this->makeSession($flow, ['status' => 'abandoned', 'current_node_id' => 'n2']);
        $failedAtQuestion = $this->makeSession($flow, ['status' => 'failed', 'current_node_id' => 'n2']);
        $failedAtStart = $this->makeSession($flow, ['status' => 'failed', 'current_node_id' => 'n1']);

        $this->makeExecution($completed, 'n1', 'start');
        $this->makeExecution($completed, 'n2', 'question');
        $this->makeExecution($completed, 'n3', 'end');
        $this->makeExecution($abandonedAtQuestion, 'n1', 'start');
        $this->makeExecution($abandonedAtQuestion, 'n2', 'question');
        $this->makeExecution($failedAtQuestion, 'n1', 'start');
        $this->makeExecution($failedAtQuestion, 'n2', 'question');
        $this->makeExecution($failedAtStart, 'n1', 'start');

        // "All-time" window (no $from) is exactly the previously-unbounded case
        // the subquery fix targets.
        $dropOff = $this->analytics->buildDropOff($flow);

        $this->assertSame([
            ['node_id' => 'n2', 'label' => 'Pregunta', 'type' => 'question', 'reached' => 3, 'dropped' => 2, 'rate' => 66.7],
            ['node_id' => 'n1', 'label' => 'Inicio', 'type' => 'start', 'reached' => 4, 'dropped' => 1, 'rate' => 25.0],
            ['node_id' => 'n3', 'label' => 'Fin', 'type' => 'end', 'reached' => 1, 'dropped' => 0, 'rate' => 0.0],
        ], $dropOff);
    }

    public function test_drop_off_respects_the_from_window(): void
    {
        $flow = ChatFlow::factory()->create([
            'nodes' => [['id' => 'n1', 'type' => 'start', 'label' => 'Inicio']],
        ]);

        $outOfWindow = $this->makeSession($flow, ['status' => 'failed', 'current_node_id' => 'n1', 'started_at' => now()->subDays(40)]);
        $this->makeExecution($outOfWindow, 'n1', 'start');

        $inWindow = $this->makeSession($flow, ['status' => 'failed', 'current_node_id' => 'n1', 'started_at' => now()]);
        $this->makeExecution($inWindow, 'n1', 'start');

        $dropOff = $this->analytics->buildDropOff($flow, now()->subDays(30));

        $this->assertSame([
            ['node_id' => 'n1', 'label' => 'Inicio', 'type' => 'start', 'reached' => 1, 'dropped' => 1, 'rate' => 100.0],
        ], $dropOff);
    }

    // ==================== buildCsatTrend ====================

    public function test_csat_trend_buckets_by_week_using_flow_scale(): void
    {
        $flow = ChatFlow::factory()->create([
            'nodes' => [['id' => 'c', 'type' => 'csat', 'data' => ['scale' => '1-5']]],
        ]);

        $week1 = Carbon::parse('2026-09-07 10:00:00'); // lunes
        $week2 = Carbon::parse('2026-09-14 10:00:00');

        $this->makeSession($flow, ['context' => ['csat_score' => 5], 'started_at' => $week1]);
        $this->makeSession($flow, ['context' => ['csat_score' => 2], 'started_at' => $week1->copy()->addHours(3)]);
        $this->makeSession($flow, ['context' => ['csat_score' => 'genial'], 'started_at' => $week1->copy()->addHours(5)]); // texto libre: se ignora
        $this->makeSession($flow, ['context' => ['csat_score' => 4], 'started_at' => $week2]);

        $trend = $this->analytics->buildCsatTrend($flow, now()->subYear());

        $this->assertSame([
            [
                'week' => $week1->copy()->startOfWeek()->toDateString(),
                'answered' => 2,
                'average' => 3.5,
                'rate' => 50.0,
            ],
            [
                'week' => $week2->copy()->startOfWeek()->toDateString(),
                'answered' => 1,
                'average' => 4.0,
                'rate' => 100.0,
            ],
        ], $trend);
    }

    public function test_csat_trend_is_empty_when_no_numeric_scores(): void
    {
        $flow = ChatFlow::factory()->create();

        $this->assertSame([], $this->analytics->buildCsatTrend($flow));
    }

    // ==================== buildNodeLatency ====================

    public function test_node_latency_aggregates_duration_and_failures_per_node(): void
    {
        $flow = ChatFlow::factory()->create([
            'nodes' => [['id' => 'n1', 'type' => 'http_request', 'label' => 'Webhook']],
        ]);

        $s1 = $this->makeSession($flow);
        $s2 = $this->makeSession($flow);
        $s3 = $this->makeSession($flow);

        $this->makeExecution($s1, 'n1', 'http_request', 'success', 100);
        $this->makeExecution($s2, 'n1', 'http_request', 'success', 300);
        $this->makeExecution($s3, 'n1', 'http_request', 'failed', 500);

        $latency = $this->analytics->buildNodeLatency($flow);

        $this->assertCount(1, $latency);
        $this->assertSame('n1', $latency[0]['node_id']);
        $this->assertSame('Webhook', $latency[0]['label']);
        $this->assertSame('http_request', $latency[0]['node_type']);
        $this->assertSame(3, $latency[0]['executions']);
        $this->assertSame(300.0, $latency[0]['avg_ms']);
        $this->assertSame(500, $latency[0]['max_ms']);
        $this->assertSame(1, $latency[0]['failures']);
        $this->assertSame(33.3, $latency[0]['failure_rate']);
    }

    public function test_node_latency_is_scoped_to_the_flows_sessions_and_sorted_by_avg_desc(): void
    {
        $flow = ChatFlow::factory()->create();
        $otherFlow = ChatFlow::factory()->create();

        $slow = $this->makeSession($flow);
        $fast = $this->makeSession($flow);
        $otherFlowSession = $this->makeSession($otherFlow);

        $this->makeExecution($slow, 'slow_node', 'ai_response', 'success', 900);
        $this->makeExecution($fast, 'fast_node', 'question', 'success', 50);
        $this->makeExecution($otherFlowSession, 'slow_node', 'ai_response', 'success', 5000);

        $latency = $this->analytics->buildNodeLatency($flow);

        $this->assertSame(['slow_node', 'fast_node'], array_column($latency, 'node_id'));
        $this->assertSame(900.0, $latency[0]['avg_ms']);
    }

    // ==================== buildHttpFailureAlerts ====================

    public function test_http_failure_alerts_flags_nodes_above_threshold_and_min_executions(): void
    {
        $nodeLatency = [
            ['node_id' => 'n1', 'label' => 'Webhook A', 'node_type' => 'http_request', 'executions' => 10, 'avg_ms' => 100.0, 'max_ms' => 200, 'failures' => 3, 'failure_rate' => 30.0],
            ['node_id' => 'n2', 'label' => 'Webhook B', 'node_type' => 'http_request', 'executions' => 3, 'avg_ms' => 50.0, 'max_ms' => 60, 'failures' => 3, 'failure_rate' => 100.0],
            ['node_id' => 'n3', 'label' => 'IA', 'node_type' => 'ai_response', 'executions' => 20, 'avg_ms' => 10.0, 'max_ms' => 20, 'failures' => 15, 'failure_rate' => 75.0],
            ['node_id' => 'n4', 'label' => 'Webhook C', 'node_type' => 'http_request', 'executions' => 10, 'avg_ms' => 10.0, 'max_ms' => 20, 'failures' => 1, 'failure_rate' => 10.0],
        ];

        $alerts = $this->analytics->buildHttpFailureAlerts($nodeLatency);

        $this->assertSame(['n1'], array_column($alerts, 'node_id'));
    }

    public function test_http_failure_alert_threshold_is_configurable(): void
    {
        config([
            'helpdeskchatflow.analytics.http_failure_alert_rate' => 0.5,
            'helpdeskchatflow.analytics.http_failure_alert_min' => 1,
        ]);

        $nodeLatency = [
            ['node_id' => 'n1', 'label' => 'Webhook', 'node_type' => 'http_request', 'executions' => 2, 'avg_ms' => 10.0, 'max_ms' => 20, 'failures' => 1, 'failure_rate' => 50.0],
        ];

        $this->assertCount(1, $this->analytics->buildHttpFailureAlerts($nodeLatency));
    }

    // ==================== Rendering ====================

    public function test_analytics_page_renders_the_new_sections(): void
    {
        $this->requireHelpdeskDb();

        $this->seed(ChatFlowPermissionsSeeder::class);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo(['chatflow.view']);

        $flow = ChatFlow::factory()->create([
            'nodes' => [['id' => 'n1', 'type' => 'http_request', 'label' => 'Webhook']],
        ]);

        $session = $this->makeSession($flow);
        $this->makeExecution($session, 'n1', 'http_request', 'success', 100);

        $this->actingAs($user)
            ->get(route('chatflow.analytics', $flow))
            ->assertOk()
            ->assertViewHas('csatTrend')
            ->assertViewHas('nodeLatency')
            ->assertViewHas('httpAlerts')
            ->assertSee('Latencia y fallos por nodo');
    }

    private function requireHelpdeskDb(): void
    {
        try {
            \DB::connection('helpdesk')->statement('SELECT 1 FROM helpdesk_chat_flows LIMIT 1');
        } catch (\Throwable) {
            $this->markTestSkipped('helpdesk_chat_flows table not available in test DB (system_test_pristine).');
        }
    }
}
