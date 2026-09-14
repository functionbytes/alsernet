<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Models;

use Modules\HelpdeskTickets\Models\TicketMail;
use PHPUnit\Framework\TestCase;

/**
 * Imágenes remotas de los correos entrantes.
 *
 * Purificar el HTML impide que se ejecute código, pero no que el navegador
 * PIDA los recursos que el correo referencia: un <img> de un píxel alojado en
 * el servidor del remitente le confirma la hora exacta de lectura y la IP del
 * agente con solo abrir el ticket. Los clientes de correo llevan años
 * bloqueándolas por defecto.
 *
 * Sin base de datos: blockRemoteImages() es una función pura sobre una cadena.
 */
class TicketMailRemoteImagesTest extends TestCase
{
    public function test_desactiva_el_pixel_de_seguimiento(): void
    {
        $html = TicketMail::blockRemoteImages(
            '<p>Hola</p><img src="https://tracker.example.com/p.gif?id=9" width="1" height="1">'
        );

        // Ojo con la aserción: 'data-src="..."' CONTIENE 'src="..."', así que
        // buscar la subcadena a pelo da un falso fallo. Lo que importa es que
        // no quede ningún src= que el navegador vaya a resolver.
        $this->assertSame(0, preg_match_all('/<img[^>]*(?<![-\w])src=/i', $html));
        $this->assertStringContainsString('data-src="https://tracker.example.com/p.gif?id=9"', $html);
        $this->assertStringContainsString('data-blocked="1"', $html);
        // Los atributos que no son el src se conservan: sin ellos el correo se
        // descuadra al restaurar las imágenes.
        $this->assertStringContainsString('width="1"', $html);
    }

    public function test_respeta_las_comillas_simples(): void
    {
        $html = TicketMail::blockRemoteImages("<img src='http://x.test/a.png' alt='foto'>");

        $this->assertStringContainsString("data-src='http://x.test/a.png'", $html);
        $this->assertStringContainsString("alt='foto'", $html);
    }

    public function test_deja_pasar_las_imagenes_incrustadas(): void
    {
        // Una data: URI viaja dentro del propio mensaje: no genera ninguna
        // petición que delate al agente, así que bloquearla solo rompería el
        // correo.
        $original = '<img src="data:image/png;base64,iVBORw0KGgo=" alt="inline">';

        $this->assertSame($original, TicketMail::blockRemoteImages($original));
    }

    public function test_no_toca_el_html_sin_imagenes(): void
    {
        $original = '<p>Un correo de solo texto.</p>';

        $this->assertSame($original, TicketMail::blockRemoteImages($original));
        $this->assertSame('', TicketMail::blockRemoteImages(''));
    }

    public function test_una_imagen_sin_src_no_rompe_nada(): void
    {
        $original = '<img alt="sin src">';

        $this->assertSame($original, TicketMail::blockRemoteImages($original));
    }

    public function test_bloquea_todas_las_imagenes_del_correo(): void
    {
        $html = TicketMail::blockRemoteImages(
            '<img src="http://a.test/1.png"><p>texto</p><img src="http://b.test/2.png">'
        );

        $this->assertSame(2, substr_count($html, 'data-blocked="1"'));
        $this->assertSame(0, preg_match_all('/<img[^>]*(?<![-\w])src=/i', $html));
    }
}
