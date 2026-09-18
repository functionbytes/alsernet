<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskChatFlow\Services\ChatFlowTestSimulator;
use Tests\TestCase;

class ChatFlowTestSimulatorTest extends TestCase
{
    private ChatFlowTestSimulator $sim;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        $this->sim = new ChatFlowTestSimulator;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function node(string $id, string $type, ?string $parent, array $data = [], string $label = ''): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parent, 'label' => $label ?: $id, 'data' => $data];
    }

    /**
     * @param  array<int, array<string,mixed>>  $messages
     */
    private function textOf(array $messages): string
    {
        return implode("\n", array_map(fn ($m) => $m['text'] ?? '', $messages));
    }

    public function test_message_then_collect_input_pauses_for_reply(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('m', 'message', 'start', ['text' => 'Hola']),
            $this->node('ask', 'collect_input', 'm', ['question' => '¿Tu nombre?', 'variable_name' => 'nombre']),
            $this->node('end', 'end', 'ask', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);

        $this->assertSame('active', $start['status']);
        $this->assertStringContainsString('Hola', $this->textOf($start['messages']));
        $this->assertStringContainsString('¿Tu nombre?', $this->textOf($start['messages']));

        $reply = $this->sim->reply($start['session_key'], 'Ada');
        $this->assertSame('completed', $reply['status']);
    }

    public function test_quick_replies_resolves_numbered_reply(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('qr', 'quick_replies', 'start', [
                'text' => 'Elige', 'options' => ['Ventas', 'Soporte'], 'variable_name' => 'area',
            ]),
            $this->node('vent', 'message', 'qr', ['text' => 'Área ventas'], 'Ventas'),
            $this->node('sop', 'message', 'qr', ['text' => 'Área soporte'], 'Soporte'),
        ];

        $start = $this->sim->start($nodes);
        $this->assertStringContainsString('1. Ventas', $this->textOf($start['messages']));
        $this->assertStringContainsString('2. Soporte', $this->textOf($start['messages']));

        // Replying "2" must route to the "Soporte" child.
        $reply = $this->sim->reply($start['session_key'], '2');
        $this->assertStringContainsString('Área soporte', $this->textOf($reply['messages']));
    }

    public function test_csat_node_asks_and_captures_score(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('csat', 'csat', 'start', [
                'question' => '¿Qué tal?', 'scale' => '1-5', 'thanks_message' => '¡Gracias!',
            ]),
            $this->node('end', 'end', 'csat', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);
        $this->assertStringContainsString('¿Qué tal?', $this->textOf($start['messages']));
        $this->assertStringContainsString('Responde con el número', $this->textOf($start['messages']));

        $reply = $this->sim->reply($start['session_key'], '5');
        $this->assertStringContainsString('¡Gracias!', $this->textOf($reply['messages']));
        $this->assertSame('completed', $reply['status']);
    }

    public function test_business_hours_node_continues_and_reports_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-17 10:00:00', 'UTC')); // Wednesday

        $nodes = [
            $this->node('start', 'start', null),
            $this->node('bh', 'business_hours', 'start', [
                'timezone' => 'UTC', 'days' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '18:00',
            ]),
            $this->node('end', 'end', 'bh', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);

        $this->assertStringContainsString('dentro de horario', $this->textOf($start['messages']));
    }

    public function test_rich_message_with_options_waits_for_selection(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('card', 'rich_message', 'start', [
                'title' => 'Oferta', 'subtitle' => 'Solo hoy', 'image_url' => 'https://x/y.jpg',
                'options' => ['Ver más', 'No gracias'],
            ]),
            $this->node('more', 'message', 'card', ['text' => 'Aquí tienes'], 'Ver más'),
            $this->node('bye', 'message', 'card', ['text' => 'Ok'], 'No gracias'),
        ];

        $start = $this->sim->start($nodes);
        $text = $this->textOf($start['messages']);
        $this->assertStringContainsString('Oferta', $text);
        $this->assertStringContainsString('1. Ver más', $text);
        $this->assertSame('active', $start['status']); // waits

        $reply = $this->sim->reply($start['session_key'], '1');
        $this->assertStringContainsString('Aquí tienes', $this->textOf($reply['messages']));
    }

    public function test_rich_message_without_options_continues(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('card', 'rich_message', 'start', ['title' => 'Bienvenido', 'image_url' => 'https://x/y.jpg']),
            $this->node('end', 'end', 'card', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);
        $this->assertStringContainsString('Bienvenido', $this->textOf($start['messages']));
        $this->assertSame('completed', $start['status']);
    }

    public function test_ai_response_node_uses_fallback_without_api_key(): void
    {
        config()->set('services.openai.key', '');

        $nodes = [
            $this->node('start', 'start', null),
            $this->node('ai', 'ai_response', 'start', [
                'use_knowledge_base' => false,
                'fallback_message' => 'Te paso con un agente.',
                'question_variable' => 'last_input',
            ]),
            $this->node('end', 'end', 'ai', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);
        $this->assertStringContainsString('Te paso con un agente.', $this->textOf($start['messages']));
    }

    public function test_http_request_node_saves_response_to_context(): void
    {
        Http::fake(['8.8.8.8/*' => Http::response(['estado' => 'enviado'], 200)]);

        $nodes = [
            $this->node('start', 'start', null),
            $this->node('http', 'http_request', 'start', [
                'method' => 'GET', 'url' => 'http://8.8.8.8/order', 'response_path' => 'estado',
                'save_to' => 'estado_pedido', 'show_message' => true, 'message_template' => 'Estado: {{estado_pedido}}',
            ]),
            $this->node('end', 'end', 'http', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);
        $this->assertStringContainsString('Estado: enviado', $this->textOf($start['messages']));
    }

    public function test_escapes_to_human_when_customer_asks_for_agent(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('qr', 'quick_replies', 'start', ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]),
            $this->node('a', 'message', 'qr', ['text' => 'A'], 'Ventas'),
        ];

        $start = $this->sim->start($nodes);
        $reply = $this->sim->reply($start['session_key'], 'quiero hablar con un humano');

        $this->assertSame('transferred', $reply['status']);
        $this->assertStringContainsString('agente', $this->textOf($reply['messages']));
    }

    public function test_reprompts_on_invalid_option(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('qr', 'quick_replies', 'start', ['text' => 'Elige', 'options' => ['Ventas', 'Soporte']]),
            $this->node('a', 'message', 'qr', ['text' => 'Área A'], 'Ventas'),
        ];

        $start = $this->sim->start($nodes);
        $reply = $this->sim->reply($start['session_key'], 'no sé qué poner');

        $this->assertSame('active', $reply['status']); // stays on the node
        $this->assertStringContainsString('No reconocí esa opción', $this->textOf($reply['messages']));
        $this->assertStringNotContainsString('Área A', $this->textOf($reply['messages']));
    }

    public function test_validates_email_input_and_reprompts_then_accepts(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('ask', 'collect_input', 'start', ['question' => 'Tu email', 'variable_name' => 'email', 'validation' => 'email']),
            $this->node('ok', 'message', 'ask', ['text' => '¡Email guardado!']),
        ];

        $start = $this->sim->start($nodes);

        // Invalid email → re-ask, stays on node
        $bad = $this->sim->reply($start['session_key'], 'esto-no-es-email');
        $this->assertSame('active', $bad['status']);
        $this->assertStringContainsString('email válido', $this->textOf($bad['messages']));

        // Valid email → advances
        $good = $this->sim->reply($start['session_key'], 'cliente@example.com');
        $this->assertStringContainsString('¡Email guardado!', $this->textOf($good['messages']));
    }

    public function test_order_lookup_node_shows_demo_order_in_test_mode(): void
    {
        $nodes = [
            $this->node('start', 'start', null),
            $this->node('ask', 'collect_input', 'start', ['variable_name' => 'numero_pedido']),
            $this->node('lk', 'order_lookup', 'ask', ['order_variable' => 'numero_pedido']),
            $this->node('end', 'end', 'lk', ['action' => 'close']),
        ];

        $start = $this->sim->start($nodes);
        $reply = $this->sim->reply($start['session_key'], '12345');

        $text = $this->textOf($reply['messages']);
        $this->assertStringContainsString('Pedido #12345', $text);
        $this->assertStringContainsString('Pedido de ejemplo', $text);
    }
}
