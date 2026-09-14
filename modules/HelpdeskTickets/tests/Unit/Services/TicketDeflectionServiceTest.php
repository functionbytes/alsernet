<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Services\TicketDeflectionService;
use Tests\TestCase;

/**
 * Deflexión en el portal.
 *
 * Lo importante no es acertar: es NO mostrar artículos que no vienen a cuento.
 * Un cliente al que le ponen tres enlaces irrelevantes antes de dejarle
 * escribir siente que le dan largas, y eso cuesta más que el ticket ahorrado.
 */
class TicketDeflectionServiceTest extends TestCase
{
    /**
     * @param  array<int, array<string, mixed>>  $articles
     * @return array<int, array<string, mixed>>
     */
    private function pick(?string $raw, array $articles): array
    {
        $method = new \ReflectionMethod(TicketDeflectionService::class, 'pick');
        $method->setAccessible(true);

        return $method->invoke(app(TicketDeflectionService::class), $raw, $articles);
    }

    /** @return array<int, array<string, mixed>> */
    private function articles(): array
    {
        return [
            ['id' => 'a', 'title' => 'Cómo devolver un pedido', 'url' => '/a', 'excerpt' => ''],
            ['id' => 'b', 'title' => 'Formas de pago', 'url' => '/b', 'excerpt' => ''],
            ['id' => 'c', 'title' => 'Plazos de envío', 'url' => '/c', 'excerpt' => ''],
        ];
    }

    public function test_it_keeps_the_chosen_articles_in_order(): void
    {
        $picked = $this->pick('[3, 1]', $this->articles());

        $this->assertCount(2, $picked);
        $this->assertSame('c', $picked[0]['id']);
        $this->assertSame('a', $picked[1]['id']);
    }

    public function test_an_empty_selection_shows_nothing(): void
    {
        // Es la respuesta CORRECTA cuando ninguno sirve, no un fallo.
        $this->assertSame([], $this->pick('[]', $this->articles()));
    }

    public function test_an_out_of_range_index_is_ignored(): void
    {
        $picked = $this->pick('[1, 99]', $this->articles());

        $this->assertCount(1, $picked);
        $this->assertSame('a', $picked[0]['id']);
    }

    public function test_an_unparseable_answer_shows_nothing(): void
    {
        // Mejor no deflectar que deflectar mal.
        $this->assertSame([], $this->pick('creo que el primero le sirve', $this->articles()));
        $this->assertSame([], $this->pick(null, $this->articles()));
    }

    public function test_at_most_three_articles_are_shown(): void
    {
        $many = array_map(
            fn (int $i): array => ['id' => (string) $i, 'title' => "T{$i}", 'url' => "/{$i}", 'excerpt' => ''],
            range(0, 9)
        );

        $this->assertCount(3, $this->pick('[1,2,3,4,5]', $many));
    }

    public function test_a_query_too_short_is_not_searched(): void
    {
        $this->assertSame([], app(TicketDeflectionService::class)->suggest('hola'));
    }

    public function test_it_does_nothing_while_disabled(): void
    {
        config(['helpdesktickets.deflection.enabled' => false]);

        $this->assertSame([], app(TicketDeflectionService::class)->suggest('No me llega el pedido que hice la semana pasada'));
    }

    /**
     * Fix de lógica de negocio del 14-sep-2026 (auditoría): filter() no
     * capturaba las excepciones de $this->llm->chat(), rompiendo la
     * garantía documentada en la clase ("Nunca impide abrir el ticket.
     * Sugiere y se aparta.") — un fallo transitorio del LLM (timeout,
     * credencial inválida) reventaba en vez de degradar.
     */
    public function test_a_failing_llm_falls_back_to_the_first_three_articles_instead_of_throwing(): void
    {
        $this->mock(AgentLlmService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('chat')->andThrow(new \RuntimeException('proveedor caído'));
        });

        $method = new \ReflectionMethod(TicketDeflectionService::class, 'filter');
        $method->setAccessible(true);

        $picked = $method->invoke(app(TicketDeflectionService::class), 'consulta de prueba', $this->articles());

        $this->assertSame($this->articles(), $picked, 'Sin LLM disponible, debe degradar a los 3 primeros del buscador, no lanzar.');
    }
}
