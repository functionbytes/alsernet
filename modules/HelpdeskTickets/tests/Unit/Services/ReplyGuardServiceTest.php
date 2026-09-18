<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\ReplyGuardService;
use Tests\TestCase;

/**
 * Guardia de salida: lo que se protege es que NUNCA bloquee.
 *
 * Un aviso equivocado cuesta unos segundos al agente. Una guardia que impide
 * enviar cuesta un cliente sin respuesta, así que todo aquí está montado para
 * fallar en abierto.
 */
class ReplyGuardServiceTest extends TestCase
{
    /** @return array<int, array<string, mixed>> */
    private function parse(?string $raw, string $draft): array
    {
        $method = new \ReflectionMethod(ReplyGuardService::class, 'parse');
        $method->setAccessible(true);

        return $method->invoke(app(ReplyGuardService::class), $raw, $draft);
    }

    public function test_a_valid_warning_survives_with_its_quote(): void
    {
        $draft = 'Le devolveremos el importe de 240 euros antes del viernes.';
        $raw = '[{"kind": "dato_no_verificable", "message": "El importe no aparece en el hilo", "excerpt": "240 euros"}]';

        $warnings = $this->parse($raw, $draft);

        $this->assertCount(1, $warnings);
        $this->assertSame('dato_no_verificable', $warnings[0]['kind']);
        $this->assertSame('240 euros', $warnings[0]['excerpt']);
    }

    public function test_a_quote_that_is_not_in_the_draft_is_dropped(): void
    {
        // Si el modelo parafrasea, el agente busca una frase que nunca
        // escribió. El aviso se conserva; la cita inventada, no.
        $raw = '[{"kind": "promesa", "message": "Promete un reembolso", "excerpt": "le devolvemos todo el dinero ya"}]';

        $warnings = $this->parse($raw, 'Le devolveremos el importe antes del viernes.');

        $this->assertCount(1, $warnings);
        $this->assertSame('', $warnings[0]['excerpt']);
    }

    public function test_whitespace_differences_do_not_break_the_quote_check(): void
    {
        $draft = "Le devolveremos\n  el importe   completo.";
        $raw = '[{"kind": "promesa", "message": "Promete reembolso", "excerpt": "Le devolveremos el importe completo"}]';

        $this->assertNotSame('', $this->parse($raw, $draft)[0]['excerpt']);
    }

    public function test_an_invented_kind_is_discarded(): void
    {
        $raw = '[{"kind": "tono_agresivo", "message": "Suena brusco", "excerpt": ""}]';

        // El tono NO es competencia de esta guardia: avisar de eso la
        // convierte en un corrector de estilo que el agente aprende a ignorar.
        $this->assertSame([], $this->parse($raw, 'Un borrador cualquiera.'));
    }

    public function test_a_warning_without_message_is_discarded(): void
    {
        $this->assertSame([], $this->parse('[{"kind": "promesa", "message": "  "}]', 'Texto.'));
    }

    public function test_a_non_json_answer_produces_no_warnings(): void
    {
        // Fallar en abierto: sin avisos, el envío sigue su curso.
        $this->assertSame([], $this->parse('Todo me parece correcto.', 'Texto.'));
        $this->assertSame([], $this->parse(null, 'Texto.'));
    }

    public function test_the_warning_list_is_capped(): void
    {
        $rows = array_fill(0, 12, ['kind' => 'promesa', 'message' => 'Algo', 'excerpt' => '']);

        // Una lista larga de avisos se ignora entera, así que se recorta.
        $this->assertCount(5, $this->parse(json_encode($rows), 'Texto.'));
    }

    public function test_a_short_draft_is_not_checked_at_all(): void
    {
        $ticket = new Ticket;

        $result = app(ReplyGuardService::class)->check($ticket, 'Gracias.');

        $this->assertFalse($result['checked']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_it_does_nothing_while_disabled(): void
    {
        config(['helpdesktickets.reply_guard.enabled' => false]);

        $result = app(ReplyGuardService::class)->check(
            new Ticket,
            str_repeat('texto suficientemente largo ', 10),
        );

        $this->assertFalse($result['checked']);
    }
}
