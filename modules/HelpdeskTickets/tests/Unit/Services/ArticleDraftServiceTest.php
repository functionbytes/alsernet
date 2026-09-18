<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\HelpdeskTickets\Services\ArticleDraftService;
use Tests\TestCase;

/**
 * Borradores de artículo a partir de tickets resueltos.
 *
 * Lo que se protege es que nada llegue publicado y que el modelo pueda decir
 * «estos casos no comparten nada»: un artículo de ayuda inventado a partir de
 * tickets que solo comparten vocabulario es peor que no tener artículo.
 */
class ArticleDraftServiceTest extends TestCase
{
    /** @return array<string, string>|null */
    private function parse(?string $raw): ?array
    {
        $method = new \ReflectionMethod(ArticleDraftService::class, 'parse');
        $method->setAccessible(true);

        return $method->invoke(app(ArticleDraftService::class), $raw);
    }

    public function test_it_parses_an_article(): void
    {
        $raw = '{"title": "Cómo cambiar la dirección de envío", "excerpt": "En dos pasos.",
                 "body": "<p>Entra en Mis pedidos…</p>"}';

        $article = $this->parse($raw);

        $this->assertSame('Cómo cambiar la dirección de envío', $article['title']);
        $this->assertStringContainsString('<p>', $article['body']);
    }

    public function test_an_empty_title_is_the_agreed_signal_for_no_article(): void
    {
        // El prompt pide title vacío cuando los casos no comparten un problema
        // común: es una respuesta correcta, no un error de formato.
        $this->assertNull($this->parse('{"title": ""}'));
        $this->assertNull($this->parse('{"title": "   ", "body": "<p>algo</p>"}'));
    }

    public function test_an_article_without_body_is_discarded(): void
    {
        $this->assertNull($this->parse('{"title": "Un título suelto"}'));
    }

    public function test_a_non_json_answer_is_discarded(): void
    {
        $this->assertNull($this->parse('Aquí tienes el artículo que me pediste'));
        $this->assertNull($this->parse(null));
    }

    public function test_the_title_is_capped(): void
    {
        $long = str_repeat('palabra ', 100);

        $this->assertSame(250, mb_strlen($this->parse('{"title": "'.$long.'", "body": "<p>x</p>"}')['title']));
    }

    public function test_it_is_disabled_by_default(): void
    {
        $this->assertFalse(config('helpdesktickets.article_drafts.enabled'));
        $this->assertFalse(app(ArticleDraftService::class)->isAvailable());
    }
}
