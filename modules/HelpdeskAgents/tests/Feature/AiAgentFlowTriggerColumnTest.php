<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Tests\TestCase;

/**
 * Regression for the trigger_type→trigger column rename (migration
 * 2026_05_21_000600): the model + controller kept writing/reading the old
 * `trigger_type` key while the column is now `trigger`, so flow create/query
 * broke against the real schema ("Unknown column 'trigger_type'"). Latent only
 * because the AI runtime is disabled by default.
 */
class AiAgentFlowTriggerColumnTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function makeAgent(): AiAgent
    {
        return AiAgent::create([
            'name' => 'Trigger Agent',
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'status' => 'active',
            'enabled_at' => now(),
        ]);
    }

    public function test_flow_persists_via_the_trigger_column(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $flow = AiAgentFlow::create([
            'ai_agent_id' => $this->makeAgent()->id,
            'name' => 'Bienvenida',
            'trigger' => 'conversation_start',
        ]);

        $this->assertDatabaseHas('helpdesk_ai_agent_flows', [
            'id' => $flow->id,
            'trigger' => 'conversation_start',
        ], 'helpdesk');
    }

    public function test_scope_and_label_use_the_trigger_column(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $agent = $this->makeAgent();

        $flow = AiAgentFlow::create([
            'ai_agent_id' => $agent->id,
            'name' => 'Por mensaje',
            'trigger' => 'message',
        ]);

        $found = AiAgentFlow::query()->byTrigger('message')->whereKey($flow->id)->first();

        $this->assertNotNull($found, 'scopeByTrigger must query the trigger column');
        $this->assertSame('Mensaje', $found->trigger_label);
    }
}
