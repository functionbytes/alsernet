<?php

namespace Modules\HelpdeskTickets\Tests\Unit;

use Modules\HelpdeskTickets\Models\TicketMail;
use Tests\TestCase;

/**
 * El saneador del HTML de un correo tiene que hacer dos cosas a la vez:
 * dejar el mensaje como lo compuso quien lo envió, y no dejar pasar nada
 * ejecutable.
 *
 * Estaba haciendo solo la segunda. `style` se permitía únicamente en <span>,
 * así que la maquetación de cualquier correo —que en un email va SIEMPRE en
 * línea, porque no hay hojas de estilo que valgan— se caía entera: la cabecera
 * de color quedaba en un <div> desnudo, las celdas perdían relleno y bordes, y
 * los titulares salían al tamaño por defecto del navegador. El agente veía roto
 * lo que el cliente había recibido bien.
 */
class TicketMailPurifyHtmlTest extends TestCase
{
    /** Un correo de verdad: tabla contenedora, banda de color y ficha de datos. */
    private const CORREO = <<<'HTML'
        <table width="100%" cellpadding="0" cellspacing="0" border="0" align="center"
               style="width:100%; background-color:#f5f6f8;">
          <tr>
            <td align="center" valign="top" style="padding:24px;">
              <div style="background: #90bb13; color: #ffffff; padding: 20px 24px;">
                <h2 style="margin:0; font-size:18px;">Hemos recibido tu solicitud</h2>
              </div>
              <table width="100%" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:12px 0; border-bottom:1px solid #eeeeee;"><b>Número</b></td>
                  <td style="padding:12px 0; border-bottom:1px solid #eeeeee;">#TCK-2026-00117</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        HTML;

    public function test_conserva_la_maquetacion_del_correo(): void
    {
        $limpio = TicketMail::purifyHtml(self::CORREO);

        // La banda de color: es un <div style>, el caso que se perdía entero.
        $this->assertStringContainsString('#90bb13', $limpio);
        $this->assertStringContainsString('padding', $limpio);

        // La ficha de datos: sin bordes ni relleno se lee como texto suelto.
        $this->assertStringContainsString('border-bottom', $limpio);
        $this->assertStringContainsString('border-collapse', $limpio);

        // El titular con su tamaño, no con el que ponga el navegador.
        $this->assertStringContainsString('font-size', $limpio);

        // Y la estructura de tablas, que es como se maqueta un email.
        $this->assertStringContainsString('<table', $limpio);
        $this->assertStringContainsString('cellpadding', $limpio);
    }

    /**
     * Gmail reescribe los colores a rgb() al citar un mensaje: si solo se
     * admitiera la notación hexadecimal, la respuesta de un cliente se vería
     * rota aunque el original estuviera bien.
     */
    public function test_admite_los_colores_en_rgb_que_escriben_los_clientes_de_correo(): void
    {
        $limpio = TicketMail::purifyHtml(
            '<div style="background:rgb(144,187,19);color:rgb(255,255,255);padding:20px 24px">Hola</div>'
        );

        $this->assertStringContainsString('rgb(144,187,19)', $limpio);
        $this->assertStringContainsString('padding', $limpio);
    }

    public function test_sigue_bloqueando_lo_ejecutable(): void
    {
        $peligroso = '<div style="color:#000">Hola</div>'
            .'<script>alert(1)</script>'
            .'<img src="x" onerror="alert(2)">'
            .'<a href="javascript:alert(3)">pulsa</a>'
            .'<iframe src="https://ejemplo.test"></iframe>';

        $limpio = TicketMail::purifyHtml($peligroso);

        $this->assertStringNotContainsString('<script', $limpio);
        $this->assertStringNotContainsString('onerror', $limpio);
        $this->assertStringNotContainsString('javascript:', $limpio);
        $this->assertStringNotContainsString('<iframe', $limpio);

        // Lo inofensivo del mismo fragmento se queda.
        $this->assertStringContainsString('Hola', $limpio);
    }

    /**
     * `position` es lo único de la familia de la maquetación que se deja fuera
     * a propósito: con fixed o absolute, un correo podría sacar contenido de su
     * hueco y tapar algo del panel al agente.
     */
    public function test_no_deja_que_un_correo_se_salga_de_su_hueco(): void
    {
        $limpio = TicketMail::purifyHtml(
            '<div style="position:fixed; top:0; left:0; width:100%; height:100%; background:#fff">tapa el panel</div>'
        );

        $this->assertStringNotContainsString('position', $limpio);
        // El resto del estilo sí sobrevive: solo se cae la propiedad peligrosa.
        $this->assertStringContainsString('width', $limpio);
    }
}
