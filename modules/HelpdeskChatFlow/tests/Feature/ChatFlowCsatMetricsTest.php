<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowAnalyticsService;
use Modules\HelpdeskChatFlow\Tests\TestCase;

/**
 * buildCsatMetrics lee la puntuación de la columna virtual csat_score_value
 * (derivada de context->csat_score) en vez de extraerla del JSON.
 */
class ChatFlowCsatMetricsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            \DB::connection('helpdesk')->statement('SELECT csat_score_value FROM helpdesk_chat_flow_sessions LIMIT 1');
        } catch (\Throwable) {
            $this->markTestSkipped('helpdesk_chat_flow_sessions.csat_score_value not available in test DB.');
        }
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function makeSession(ChatFlow $flow, array $context, ?Carbon $startedAt = null): ChatFlowSession
    {
        return ChatFlowSession::create([
            'chat_flow_id' => $flow->id,
            'conversation_id' => 1,
            'status' => 'completed',
            'trigger_type' => 'conversation_start',
            'context' => $context,
            'started_at' => $startedAt ?? now(),
        ]);
    }

    public function test_computes_metrics_from_numeric_scores_only(): void
    {
        $flow = ChatFlow::factory()->create([
            'nodes' => [['id' => 'c', 'type' => 'csat', 'data' => ['scale' => '1-5']]],
        ]);

        $this->makeSession($flow, ['csat_score' => 5]);
        $this->makeSession($flow, ['csat_score' => '4']);
        $this->makeSession($flow, ['csat_score' => 2]);
        $this->makeSession($flow, ['csat_score' => 'muy bien']); // texto libre: se ignora
        $this->makeSession($flow, ['nombre' => 'Ada']);          // sin encuesta
        $this->makeSession($flow, ['csat_score' => 1], now()->subDays(40)); // fuera de ventana

        $metrics = app(ChatFlowAnalyticsService::class)->buildCsatMetrics($flow, now()->subDays(30));

        $this->assertSame(3, $metrics['answered']);
        $this->assertSame(3.7, $metrics['average']);
        $this->assertSame(2, $metrics['satisfied']);
        $this->assertSame(66.7, $metrics['rate']);
        $this->assertSame(5, $metrics['max']);
    }

    public function test_virtual_column_is_hidden_from_serialization(): void
    {
        $flow = ChatFlow::factory()->create();
        $session = $this->makeSession($flow, ['csat_score' => 5])->fresh();

        $this->assertSame('5', (string) $session->csat_score_value);
        $this->assertArrayNotHasKey('csat_score_value', $session->toArray());
    }
}
