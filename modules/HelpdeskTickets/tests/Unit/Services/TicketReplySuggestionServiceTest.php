<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketReplySuggestionService;
use Tests\TestCase;

/**
 * Guardas del sugeridor de respuestas sobre lo que devuelve el modelo.
 *
 * Todo lo que se prueba aqui es logica pura sobre la salida del LLM (parseo,
 * atribucion de plantilla, idioma), sin BD ni proveedor: son precisamente las
 * comprobaciones que impiden que una alucinacion del modelo llegue a la UI
 * como si fuera un dato del sistema.
 */
class TicketReplySuggestionServiceTest extends TestCase
{
    /** @param array<int, array<string, mixed>> $candidates */
    private function parse(array $result, array $candidates = [], string $language = 'es'): ?array
    {
        $method = new \ReflectionMethod(TicketReplySuggestionService::class, 'parse');
        $method->setAccessible(true);

        return $method->invoke(app(TicketReplySuggestionService::class), $result, $candidates, $language);
    }

    private function language(Ticket $ticket): string
    {
        $method = new \ReflectionMethod(TicketReplySuggestionService::class, 'resolveLanguage');
        $method->setAccessible(true);

        return $method->invoke(app(TicketReplySuggestionService::class), $ticket);
    }

    /** @return array<int, array<string, mixed>> */
    private function candidates(): array
    {
        return [
            ['id' => 3, 'kind' => 'plantilla', 'name' => 'Devolucion fuera de plazo', 'subject' => null, 'body' => '...'],
            ['id' => 3, 'kind' => 'macro', 'name' => 'Cerrar sin respuesta', 'subject' => null, 'body' => '...'],
        ];
    }

    public function test_it_extracts_the_draft_from_a_json_answer(): void
    {
        $parsed = $this->parse([
            'text' => '{"template_id": 3, "template_kind": "plantilla", "draft": "Hola, tu pedido salio ayer.", "confidence": 0.82}',
            'tool_calls' => [
                ['name' => 'get-customer-context', 'arguments' => [], 'ok' => true, 'error' => null],
                ['name' => 'search-orders', 'arguments' => [], 'ok' => false, 'error' => 'timeout'],
            ],
        ], $this->candidates());

        $this->assertSame('Hola, tu pedido salio ayer.', $parsed['draft']);
        $this->assertSame(0.82, $parsed['confidence']);
        $this->assertSame('Devolucion fuera de plazo', $parsed['template']['name']);
        // Solo se listan como fuente las herramientas que respondieron: una
        // que fallo no respalda ningun dato del borrador.
        $this->assertSame(['get-customer-context'], $parsed['sources']);
    }

    public function test_the_kind_disambiguates_ids_shared_between_types(): void
    {
        $parsed = $this->parse([
            'text' => '{"template_id": 3, "template_kind": "macro", "draft": "Texto."}',
        ], $this->candidates());

        $this->assertSame('macro', $parsed['template']['kind']);
        $this->assertSame('Cerrar sin respuesta', $parsed['template']['name']);
    }

    public function test_an_invented_template_id_is_dropped_instead_of_shown(): void
    {
        $parsed = $this->parse([
            'text' => '{"template_id": 999, "template_kind": "plantilla", "draft": "Texto."}',
        ], $this->candidates());

        $this->assertSame('Texto.', $parsed['draft']);
        $this->assertNull($parsed['template'], 'Un id fuera de las candidatas no debe atribuirse');
    }

    public function test_an_empty_draft_yields_no_suggestion(): void
    {
        $this->assertNull($this->parse(['text' => '{"draft": "   "}']));
    }

    public function test_a_non_json_answer_yields_no_suggestion(): void
    {
        $this->assertNull($this->parse(['text' => 'Claro, aqui tienes tu respuesta.']));
    }

    public function test_confidence_is_clamped_and_defaults_to_zero(): void
    {
        $this->assertSame(1.0, $this->parse(['text' => '{"draft": "x", "confidence": 7}'])['confidence']);
        $this->assertSame(0.0, $this->parse(['text' => '{"draft": "x"}'])['confidence']);
    }

    public function test_language_prefers_the_detected_one_over_the_customer_profile(): void
    {
        $ticket = new Ticket;
        $ticket->detected_language = 'fr';
        $ticket->setRelation('customer', new Customer(['language' => 'en']));

        $this->assertSame('fr', $this->language($ticket));
    }

    public function test_language_falls_back_to_the_customer_then_to_spanish(): void
    {
        $withCustomer = new Ticket;
        $withCustomer->setRelation('customer', new Customer(['language' => 'pt']));
        $this->assertSame('pt', $this->language($withCustomer));

        $bare = new Ticket;
        $bare->setRelation('customer', null);
        $this->assertSame('es', $this->language($bare));
    }

    public function test_an_unsupported_language_code_falls_back_to_spanish(): void
    {
        $ticket = new Ticket;
        // Idioma detectado real pero sin plantillas ni soporte en el helpdesk:
        // mejor responder en espanol que en un idioma que nadie revisa.
        $ticket->detected_language = 'zh';
        $ticket->setRelation('customer', null);

        $this->assertSame('es', $this->language($ticket));
    }
}
