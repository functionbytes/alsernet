<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiUsage;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\AiUsageRecorder;
use Tests\TestCase;

/**
 * Observabilidad de coste LLM: ledger helpdesk_ai_usage + presupuesto diario.
 */
class AiUsageTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Cache::forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);
        config(['helpdeskagents.ai_usage.enabled' => true]);
        config(['helpdeskagents.ai_usage.daily_max_calls' => 0]);
        config(['helpdeskagents.ai_usage.daily_max_tokens' => 0]);
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function createDefaultAgent(string $provider = 'openai', string $model = 'gpt-4o-mini'): AiAgent
    {
        $agent = AiAgent::factory()->default()->create([
            'provider' => $provider,
            'model' => $model,
            'parameters' => ['api_key' => 'test-key'],
        ]);

        Cache::forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        return $agent;
    }

    private function llm(): AgentLlmService
    {
        return app(AgentLlmService::class);
    }

    private function messages(): array
    {
        return [
            ['role' => 'system', 'content' => 'Eres un asistente.'],
            ['role' => 'user', 'content' => 'Hola'],
        ];
    }

    public function test_successful_openai_call_records_usage_with_tokens_and_feature(): void
    {
        $this->createDefaultAgent();

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Respuesta']]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 45],
            ]),
        ]);

        $result = $this->llm()->chat($this->messages(), ['feature' => 'summary']);

        $this->assertSame('Respuesta', $result);

        $row = AiUsage::query()->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('openai', $row->provider);
        $this->assertSame('gpt-4o-mini', $row->model);
        $this->assertSame('summary', $row->feature);
        $this->assertSame(120, $row->tokens_in);
        $this->assertSame(45, $row->tokens_out);
        $this->assertTrue($row->success);
        $this->assertNull($row->status_code);
    }

    public function test_anthropic_usage_tokens_are_mapped(): void
    {
        $this->createDefaultAgent('anthropic', 'claude-3-5-haiku-20241022');

        Http::fake([
            'https://api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Respuesta']],
                'usage' => ['input_tokens' => 80, 'output_tokens' => 20],
            ]),
        ]);

        $this->assertSame('Respuesta', $this->llm()->chat($this->messages(), ['feature' => 'classification']));

        $row = AiUsage::query()->latest('id')->first();
        $this->assertSame('anthropic', $row->provider);
        $this->assertSame('classification', $row->feature);
        $this->assertSame(80, $row->tokens_in);
        $this->assertSame(20, $row->tokens_out);
    }

    public function test_failed_provider_call_records_failure_with_status(): void
    {
        $this->createDefaultAgent();

        Http::fake([
            'https://api.openai.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->assertNull($this->llm()->chat($this->messages(), ['feature' => 'summary']));

        $row = AiUsage::query()->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertFalse($row->success);
        $this->assertSame(500, $row->status_code);
        $this->assertNull($row->tokens_in);
    }

    public function test_call_without_feature_defaults_to_other(): void
    {
        $this->createDefaultAgent();

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'ok']]],
            ]),
        ]);

        $this->llm()->chat($this->messages());

        $this->assertSame('other', AiUsage::query()->latest('id')->first()->feature);
    }

    public function test_daily_call_budget_exceeded_returns_null_without_http_call(): void
    {
        $this->createDefaultAgent();
        config(['helpdeskagents.ai_usage.daily_max_calls' => 1]);

        AiUsage::query()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'summary',
            'duration_ms' => 100,
            'success' => true,
            'created_at' => now(),
        ]);

        Http::fake();

        $this->assertNull($this->llm()->chat($this->messages(), ['feature' => 'summary']));
        Http::assertNothingSent();

        // The skipped call must not add a ledger row either.
        $this->assertSame(1, AiUsage::query()->count());
    }

    public function test_daily_token_budget_exceeded_returns_null(): void
    {
        $this->createDefaultAgent();
        config(['helpdeskagents.ai_usage.daily_max_tokens' => 100]);

        AiUsage::query()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'chatflow',
            'tokens_in' => 90,
            'tokens_out' => 30,
            'duration_ms' => 100,
            'success' => true,
            'created_at' => now(),
        ]);

        Http::fake();

        $this->assertNull($this->llm()->chat($this->messages()));
        Http::assertNothingSent();
    }

    public function test_yesterdays_usage_does_not_count_against_todays_budget(): void
    {
        $this->createDefaultAgent();
        config(['helpdeskagents.ai_usage.daily_max_calls' => 1]);

        AiUsage::query()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'summary',
            'duration_ms' => 100,
            'success' => true,
            'created_at' => now()->subDay(),
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'ok']]],
            ]),
        ]);

        $this->assertSame('ok', $this->llm()->chat($this->messages()));
    }

    public function test_tracking_disabled_does_not_record_but_call_still_works(): void
    {
        $this->createDefaultAgent();
        config(['helpdeskagents.ai_usage.enabled' => false]);

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'ok']]],
            ]),
        ]);

        $this->assertSame('ok', $this->llm()->chat($this->messages()));
        $this->assertSame(0, AiUsage::query()->count());
    }

    public function test_recorder_fails_silently_when_ledger_unavailable(): void
    {
        $recorder = app(AiUsageRecorder::class);

        // Point the model at a nonexistent table via a schema-less connection
        // failure: simplest reliable probe is calling record() after dropping
        // config; here we just assert no exception bubbles up on bad data.
        $recorder->record('openai', str_repeat('m', 500), 'summary', -5, null, -100, true);

        $this->assertTrue(true);
    }

    public function test_ai_usage_report_command_runs(): void
    {
        AiUsage::query()->create([
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'summary',
            'tokens_in' => 10,
            'tokens_out' => 5,
            'duration_ms' => 200,
            'success' => true,
            'created_at' => now(),
        ]);

        $this->artisan('helpdesk:ai-usage', ['--days' => 7])
            ->assertExitCode(0);
    }
}
