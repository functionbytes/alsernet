<?php

namespace Modules\Mailer\Tests\Unit;

use Modules\Mailer\Services\MailerTemplateRendererService;
use Tests\TestCase;

/**
 * El valor de una variable no puede traer etiquetas; la plantilla sí.
 *
 * La distinción es toda la cuestión: el HTML de la plantilla lo escribe un
 * administrador desde el panel, pero los valores que se le meten vienen a
 * menudo de fuera —el asunto de un ticket lo pone quien rellena el formulario
 * público de la web o quien envía el correo entrante—. Sin escaparlos, ese
 * asunto podía cerrar la tabla de la plantilla y añadir sus propios enlaces:
 * un correo de phishing con nuestro remitente y nuestra marca, enviado por
 * nosotros. No hace falta JavaScript para que salga caro.
 */
class MailerTemplateEscapingTest extends TestCase
{
    public function test_un_valor_no_puede_meter_etiquetas_en_el_correo(): void
    {
        $plantilla = '<table><tr><td>{TICKET_SUBJECT}</td></tr></table>';

        // El ataque real: cerrar la tabla y colgar un enlace propio detrás.
        $asunto = 'Ayuda</td></tr></table><a href="https://sitio-falso.test">Verifica tu cuenta</a><table><tr><td>';

        $salida = MailerTemplateRendererService::replaceVariables($plantilla, [
            'TICKET_SUBJECT' => $asunto,
        ]);

        $this->assertStringNotContainsString('<a href', $salida);
        // La tabla que cierra sigue siendo solo la de la plantilla.
        $this->assertSame(1, substr_count($salida, '</table>'));
        // El texto sigue leyéndose, solo que como texto.
        $this->assertStringContainsString('Ayuda', $salida);
    }

    public function test_las_comillas_no_se_escapan_del_atributo(): void
    {
        $plantilla = '<a href="https://tienda.test?ref={CUSTOMER_NAME}">Ir</a>';

        $salida = MailerTemplateRendererService::replaceVariables($plantilla, [
            'CUSTOMER_NAME' => 'Ana" onmouseover="robar()',
        ]);

        $this->assertStringNotContainsString('onmouseover="robar()"', $salida);
    }

    public function test_los_bloques_html_del_servidor_se_respetan(): void
    {
        // Estas listas las arma el propio servidor y son HTML a propósito:
        // escaparlas dejaría al cliente leyendo <li> en su correo.
        $plantilla = '<div>{MERGED_LIST}</div>';

        $salida = MailerTemplateRendererService::replaceVariables($plantilla, [
            'MERGED_LIST' => '<ul><li>#TCK-1</li></ul>',
        ]);

        $this->assertStringContainsString('<ul><li>#TCK-1</li></ul>', $salida);
    }

    public function test_la_convencion_html_evita_tocar_la_lista_al_anadir_bloques(): void
    {
        $salida = MailerTemplateRendererService::replaceVariables('<div>{RESUMEN_HTML}</div>', [
            'RESUMEN_HTML' => '<strong>3 pedidos</strong>',
        ]);

        $this->assertStringContainsString('<strong>3 pedidos</strong>', $salida);
    }

    public function test_lo_que_ya_venia_escapado_no_se_escapa_dos_veces(): void
    {
        // Un puñado de sitios ya escapaba por su cuenta con e(). Si aquí se
        // volviera a escapar, el cliente leería "&amp;lt;" en vez del texto.
        $salida = MailerTemplateRendererService::replaceVariables('<td>{SUBJECT}</td>', [
            'SUBJECT' => 'Consulta sobre &lt;pedido&gt;',
        ]);

        $this->assertStringContainsString('Consulta sobre &lt;pedido&gt;', $salida);
        $this->assertStringNotContainsString('&amp;lt;', $salida);
    }

    public function test_una_plantilla_con_twig_no_es_una_puerta_trasera(): void
    {
        // Basta un {% if %} para que el render pase por Twig, que corre con
        // autoescape apagado. Si el escapado viviera solo en el reemplazo
        // simple, cualquier plantilla con lógica quedaba desprotegida.
        $plantilla = '{% if TICKET_SUBJECT %}<td>{TICKET_SUBJECT}</td>{% endif %}';

        $salida = MailerTemplateRendererService::replaceVariables($plantilla, [
            'TICKET_SUBJECT' => '<a href="https://sitio-falso.test">Verifica tu cuenta</a>',
        ]);

        $this->assertStringNotContainsString('<a href', $salida);
        $this->assertStringContainsString('Verifica tu cuenta', $salida);
    }

    public function test_las_listas_que_recorre_twig_se_escapan_elemento_a_elemento(): void
    {
        $plantilla = '{% for doc in DOCUMENTS %}<li>{{ doc }}</li>{% endfor %}';

        $salida = MailerTemplateRendererService::replaceVariables($plantilla, [
            'DOCUMENTS' => ['DNI', '<script>robar()</script>'],
        ]);

        $this->assertStringNotContainsString('<script>', $salida);
        $this->assertStringContainsString('<li>DNI</li>', $salida);
    }

    public function test_un_cuerpo_ya_escapado_con_saltos_de_linea_conserva_sus_br(): void
    {
        // El llamante hace nl2br(e($texto)): el valor ES HTML, porque los
        // saltos de línea son <br>. Escaparlo otra vez dejaría al cliente
        // leyendo "&lt;br /&gt;" entre cada línea.
        $salida = MailerTemplateRendererService::replaceVariables('<div>{MESSAGE_BODY}</div>', [
            'MESSAGE_BODY' => nl2br(e("Hola\nadiós")),
        ]);

        $this->assertStringContainsString('<br', $salida);
    }

    public function test_la_misma_clave_en_minusculas_cuenta_igual(): void
    {
        // Document manda la variable en las dos formas.
        $salida = MailerTemplateRendererService::replaceVariables('<div>{custom_content}</div>', [
            'custom_content' => '<p>Contenido del administrador</p>',
        ]);

        $this->assertStringContainsString('<p>Contenido del administrador</p>', $salida);
    }
}
