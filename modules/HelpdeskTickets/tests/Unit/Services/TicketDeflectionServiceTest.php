<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

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
}
