<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketTemplateMatcher;
use Tests\TestCase;

/**
 * Puntuacion de plantillas candidatas.
 *
 * Es lo que decide que ocho textos ve el modelo de entre todo el catalogo, asi
 * que un criterio malo aqui no da un error: da sugerencias peores y mas caras,
 * en silencio.
 */
class TicketTemplateMatcherTest extends TestCase
{
    private TicketTemplateMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new TicketTemplateMatcher;
    }

    /** @return array<int, string> */
    private function terms(Ticket $ticket): array
    {
        $method = new \ReflectionMethod($this->matcher, 'terms');
        $method->setAccessible(true);

        return $method->invoke($this->matcher, $ticket);
    }

    /** @param array<string, mixed> $item */
    private function score(array $item, Ticket $ticket): int
    {
        $method = new \ReflectionMethod($this->matcher, 'score');
        $method->setAccessible(true);

        return $method->invoke($this->matcher, $item, $this->terms($ticket));
    }

    private function ticket(string $subject, string $description = ''): Ticket
    {
        $ticket = new Ticket;
        $ticket->subject = $subject;
        $ticket->description = $description;

        return $ticket;
    }

    public function test_greetings_and_filler_do_not_count_as_signal(): void
    {
        $terms = $this->terms($this->ticket('Hola buenos dias', 'Gracias de antemano, saludos'));

        $this->assertSame([], $terms, 'Solo cortesia: no hay nada con lo que puntuar');
    }

    public function test_short_words_are_ignored(): void
    {
        $terms = $this->terms($this->ticket('El pin no va'));

        // Palabras de menos de 4 letras generan ruido: "no", "va", "el"
        // aparecen en casi cualquier plantilla.
        $this->assertSame([], $terms);
    }

    public function test_it_extracts_the_meaningful_words(): void
    {
        $terms = $this->terms($this->ticket('Problema con la devolucion del pedido'));

        $this->assertContains('devolucion', $terms);
        $this->assertContains('pedido', $terms);
        $this->assertContains('problema', $terms);
        $this->assertNotContains('con', $terms);
    }

    public function test_a_match_in_the_name_weighs_more_than_one_in_the_body(): void
    {
        $ticket = $this->ticket('Quiero tramitar una devolucion');

        $byName = $this->score(
            ['name' => 'Devolucion aceptada', 'subject' => null, 'body' => 'Le confirmamos la operacion.'],
            $ticket
        );

        $byBody = $this->score(
            ['name' => 'Confirmacion generica', 'subject' => null, 'body' => 'Su devolucion queda registrada.'],
            $ticket
        );

        $this->assertGreaterThan($byBody, $byName);
    }

    public function test_an_unrelated_template_scores_zero(): void
    {
        $score = $this->score(
            ['name' => 'Alta de usuario', 'subject' => null, 'body' => 'Bienvenido a la plataforma.'],
            $this->ticket('Quiero tramitar una devolucion')
        );

        $this->assertSame(0, $score);
    }

    public function test_accents_and_case_do_not_break_the_match(): void
    {
        $score = $this->score(
            ['name' => 'DEVOLUCIÓN', 'subject' => null, 'body' => ''],
            $this->ticket('Solicito una DEVOLUCIÓN urgente')
        );

        $this->assertGreaterThan(0, $score);
    }
}
