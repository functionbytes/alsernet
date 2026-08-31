<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskAgents\Services\AiUsageRecorder;
use Tests\TestCase;

/**
 * Bucle de tool-calling de AgentLlmService.
 *
 * El agente por defecto se inyecta en la cache que usa
 * InteractsWithDefaultAiAgent, asi que estos tests no tocan la BD: lo que se
 * ejercita es la traduccion de formatos por proveedor y las guardas del bucle
 * (techo de iteraciones, herramienta que falla, proveedor sin tool-calling).
 */
class AgentLlmToolCallingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'helpdeskagents.mcp.max_tool_iterations' => 4,
            'helpdeskagents.mcp.max_result_bytes' => 6000,
            'helpdeskagents.ai_usage.enabled' => false,
            'helpdeskagents.ai_usage.daily_max_calls' => 0,
            'helpdeskagents.ai_usage.daily_max_tokens' => 0,
            'helpdeskagents.providers.anthropic.supports_tools' => true,
            'helpdeskagents.providers.openai.supports_tools' => true,
            'helpdeskagents.providers.gemini.supports_tools' => false,
        ]);
    }

    protected function tearDown(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        parent::tearDown();
    }

    private function service(string $provider = 'anthropic'): AgentLlmService
    {
        $agent = new AiAgent([
            'name' => 'Test',
            'provider' => $provider,
            'model' => 'test-model',
        ]);
        $agent->setRawAttributes(['api_key_encrypted' => 'sk-test'] + $agent->getAttributes(), true);

        cache()->put(AgentLlmService::DEFAULT_AGENT_CACHE_KEY, $agent, 300);

        return new AgentLlmService(new AiUsageRecorder);
    }

    /**
     * Cuerpo de la ULTIMA peticion enviada al proveedor.
     *
     * Http::recorded() puede arrastrar peticiones de otras clases de la misma
     * corrida, asi que indexar por posicion absoluta ([1]) pasa en solitario y
     * falla en suite. Lo que estos tests miran siempre es la peticion que cierra
     * el bucle.
     *
     * @return array<string, mixed>
     */
    private function lastRequestBody(): array
    {
        // recorded() es una Collection, no un array: ->last(), no end().
        return Http::recorded()->last()[0]->data();
    }

    /** @return array<int, array<string, mixed>> */
    private function tools(): array
    {
        return [[
            'name' => 'get-order',
            'description' => 'Devuelve un pedido',
            'input_schema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]],
        ]];
    }

    public function test_anthropic_executes_a_tool_and_feeds_the_result_back(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'content' => [
                        ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-order', 'input' => ['id' => 42]],
                    ],
                    'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
                ])
                ->push([
                    'content' => [['type' => 'text', 'text' => 'El pedido 42 se entrego el martes.']],
                    'usage' => ['input_tokens' => 20, 'output_tokens' => 8],
                ]),
        ]);

        $seen = [];

        $result = $this->service()->chatWithTools(
            [['role' => 'user', 'content' => '¿Que paso con mi pedido?']],
            $this->tools(),
            function (string $name, array $args) use (&$seen): string {
                $seen[] = [$name, $args];

                return '{"estado":"entregado"}';
            },
        );

        $this->assertSame('El pedido 42 se entrego el martes.', $result['text']);
        $this->assertSame([['get-order', ['id' => 42]]], $seen);
        $this->assertSame(2, $result['iterations']);
        $this->assertTrue($result['tools_used']);
        $this->assertSame([['name' => 'get-order', 'arguments' => ['id' => 42], 'ok' => true, 'error' => null]], $result['tool_calls']);

        // La segunda peticion debe reenviar el turno del asistente y el
        // tool_result en UN solo turno de usuario: repartirlos entre varios
        // turnos es lo que ensena al modelo a dejar de pedir tools en paralelo.
        $second = $this->lastRequestBody();
        $this->assertCount(3, $second['messages']);
        $this->assertSame('assistant', $second['messages'][1]['role']);
        $this->assertSame('user', $second['messages'][2]['role']);
        $this->assertSame('tool_result', $second['messages'][2]['content'][0]['type']);
        $this->assertSame('tu_1', $second['messages'][2]['content'][0]['tool_use_id']);
    }

    public function test_openai_arguments_are_json_decoded_before_reaching_the_executor(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'choices' => [['message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            // OpenAI manda los argumentos como STRING JSON.
                            'function' => ['name' => 'get-order', 'arguments' => '{"id": 7}'],
                        ]],
                    ]]],
                ])
                ->push(['choices' => [['message' => ['content' => 'Listo.']]]]),
        ]);

        $seen = [];

        $result = $this->service('openai')->chatWithTools(
            [['role' => 'user', 'content' => 'hola']],
            $this->tools(),
            function (string $name, array $args) use (&$seen): string {
                $seen[] = $args;

                return 'ok';
            },
        );

        $this->assertSame('Listo.', $result['text']);
        $this->assertSame([['id' => 7]], $seen);

        // El resultado va como mensaje 'tool' propio, con su tool_call_id.
        $second = $this->lastRequestBody();
        $this->assertSame('tool', $second['messages'][2]['role']);
        $this->assertSame('call_1', $second['messages'][2]['tool_call_id']);
    }

    public function test_a_failing_tool_is_reported_to_the_model_instead_of_aborting(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push(['content' => [['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-order', 'input' => []]]])
                ->push(['content' => [['type' => 'text', 'text' => 'No he podido consultarlo.']]]),
        ]);

        $result = $this->service()->chatWithTools(
            [['role' => 'user', 'content' => 'hola']],
            $this->tools(),
            fn () => throw new \RuntimeException('ERP caido'),
        );

        $this->assertSame('No he podido consultarlo.', $result['text']);
        $this->assertFalse($result['tool_calls'][0]['ok']);
        $this->assertSame('ERP caido', $result['tool_calls'][0]['error']);

        $second = $this->lastRequestBody();
        $this->assertTrue($second['messages'][2]['content'][0]['is_error']);
    }

    public function test_the_loop_stops_at_the_iteration_ceiling(): void
    {
        config(['helpdeskagents.mcp.max_tool_iterations' => 2]);

        // Un modelo que pide herramienta indefinidamente.
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Voy a mirarlo.'],
                    ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-order', 'input' => []],
                ],
            ]),
        ]);

        $calls = 0;

        $result = $this->service()->chatWithTools(
            [['role' => 'user', 'content' => 'hola']],
            $this->tools(),
            function () use (&$calls): string {
                $calls++;

                return 'ok';
            },
        );

        $this->assertSame(2, $result['iterations']);
        // En la ultima iteracion permitida no se ejecuta la herramienta: su
        // resultado no tendria turno en el que ser respondido — gasto tirado.
        $this->assertSame(1, $calls);
        $this->assertSame('Voy a mirarlo.', $result['text']);
    }

    public function test_a_provider_without_tool_calling_degrades_to_a_plain_completion(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Respuesta sin herramientas.']]]]],
            ]),
        ]);

        $result = $this->service('gemini')->chatWithTools(
            [['role' => 'user', 'content' => 'hola']],
            $this->tools(),
            fn () => throw new \LogicException('no deberia ejecutarse'),
        );

        $this->assertSame('Respuesta sin herramientas.', $result['text']);
        $this->assertFalse($result['tools_used']);
    }

    public function test_tool_results_are_capped_in_bytes(): void
    {
        config(['helpdeskagents.mcp.max_result_bytes' => 500]);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push(['content' => [['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-order', 'input' => []]]])
                ->push(['content' => [['type' => 'text', 'text' => 'ok']]]),
        ]);

        $this->service()->chatWithTools(
            [['role' => 'user', 'content' => 'hola']],
            $this->tools(),
            fn () => str_repeat('x', 50000),
        );

        $sent = $this->lastRequestBody()['messages'][2]['content'][0]['content'];

        $this->assertLessThan(600, strlen($sent));
        $this->assertStringContainsString('recortado', $sent);
    }
}
