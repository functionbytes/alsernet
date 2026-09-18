<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Modules\HelpdeskEmailActivity\Services\EmailLinkCheckService;
use Tests\TestCase;

/**
 * Extracción y clasificación de los enlaces de un correo. Los tests cubren todo
 * salvo la petición HTTP en sí: comprobar un enlace real es, por definición,
 * tráfico saliente a un tercero, y eso no puede depender de la red en CI.
 */
class EmailLinkCheckServiceTest extends TestCase
{
    private function service(): EmailLinkCheckService
    {
        return app(EmailLinkCheckService::class);
    }

    public function test_extracts_links_images_and_remote_stylesheets(): void
    {
        $html = <<<'HTML'
            <a href="https://example.com/ver">Ver</a>
            <img src="https://cdn.example.com/logo.png">
            <link rel="stylesheet" href="https://cdn.example.com/mail.css">
            <div style="background:url('https://cdn.example.com/bg.jpg')"></div>
        HTML;

        $urls = $this->service()->extractUrls($html, null);

        $this->assertContains('https://example.com/ver', $urls);
        $this->assertContains('https://cdn.example.com/logo.png', $urls);
        $this->assertContains('https://cdn.example.com/mail.css', $urls);
        $this->assertContains('https://cdn.example.com/bg.jpg', $urls);
    }

    public function test_extracts_links_from_the_plain_text_part(): void
    {
        $urls = $this->service()->extractUrls(null, "Más info en https://example.com/ayuda.\nGracias.");

        // El punto final es de la frase, no de la URL.
        $this->assertSame(['https://example.com/ayuda'], $urls);
    }

    public function test_deduplicates_the_same_url_across_parts(): void
    {
        $urls = $this->service()->extractUrls(
            '<a href="https://example.com/x">x</a><a href="https://example.com/x">otra vez</a>',
            'https://example.com/x',
        );

        $this->assertSame(['https://example.com/x'], $urls);
    }

    public function test_ignores_anchors_mailto_and_relative_urls(): void
    {
        $html = '<a href="#top">arriba</a><a href="mailto:a@b.c">correo</a><a href="/relativa">rel</a><img src="data:image/gif;base64,R0lGOD">';

        $this->assertSame([], $this->service()->extractUrls($html, null));
    }

    public function test_own_tracking_links_are_listed_but_never_requested(): void
    {
        $uid = 'a2a90479-83c6-4572-9cd1-b8fb27bccfad';
        $html = '<img src="https://panel.example.com/e/'.$uid.'.gif">'
            .'<a href="https://panel.example.com/e/'.$uid.'/c/TOKEN123">ir</a>';

        $result = $this->service()->run($html, null);

        $this->assertSame(2, $result['Skipped']);
        $this->assertSame(0, $result['Errors']);

        foreach ($result['Links'] as $link) {
            // Código 0 = no se pidió. Abrirlos falsearía las aperturas y clics
            // del propio registro que se está inspeccionando.
            $this->assertSame(0, $link['StatusCode']);
        }
    }

    public function test_private_addresses_are_reported_as_blocked_not_requested(): void
    {
        $result = $this->service()->run('<a href="http://127.0.0.1/interno">interno</a>', null);

        $this->assertSame(451, $result['Links'][0]['StatusCode']);
        $this->assertSame(1, $result['Errors']);
    }

    public function test_message_without_links_returns_an_empty_result(): void
    {
        $result = $this->service()->run('<p>Sin enlaces</p>', 'Tampoco aquí');

        $this->assertSame([], $result['Links']);
        $this->assertSame(0, $result['Errors']);
    }
}
