<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Support;

use Modules\HelpdeskTickets\Support\EmailReplyQuoteStripper;
use Tests\TestCase;

class EmailReplyQuoteStripperTest extends TestCase
{
    /**
     * Caso real (3-sep-2026, TCK-2026-00093): un cliente respondió desde
     * Gmail a la confirmación "Hemos recibido tu solicitud" -- Gmail envuelve
     * el correo citado completo (tablas, estilos inline, el logo) dentro de
     * <div class="gmail_quote gmail_quote_container">.
     */
    public function test_strip_html_cuts_a_real_gmail_quote(): void
    {
        $html = '<div dir="ltr"><div dir="ltr">perfecto quiero saber si esta bien o no el funcionamiento</div>'
            .'<br><div class="gmail_quote gmail_quote_container">'
            .'<div dir="ltr" class="gmail_attr">El jue, 3 sept 2026 a la(s) 10:54 p.m., &lt;info@functionbytes.com&gt; escribió:<br></div>'
            .'<blockquote class="gmail_quote" style="margin:0px 0px 0px 0.8ex;border-left:1px solid rgb(204,204,204);padding-left:1ex">'
            .'<table width="100%"><tbody><tr><td>Hemos recibido tu solicitud</td></tr></tbody></table>'
            .'</blockquote></div></div>';

        $result = EmailReplyQuoteStripper::stripHtml($html);

        $this->assertStringContainsString('perfecto quiero saber si esta bien o no el funcionamiento', $result);
        $this->assertStringNotContainsString('Hemos recibido tu solicitud', $result);
        $this->assertStringNotContainsString('gmail_quote', $result);
    }

    public function test_strip_html_returns_the_original_when_no_quote_marker_is_found(): void
    {
        $html = '<p>Mensaje normal sin cita.</p>';

        $this->assertSame($html, EmailReplyQuoteStripper::stripHtml($html));
    }

    public function test_strip_html_keeps_the_full_body_if_stripping_would_leave_it_empty(): void
    {
        // Alguien reenvía/cita sin escribir nada nuevo antes del marcador --
        // mejor conservar el original que enseñar un hilo vacío.
        $html = '<div class="gmail_quote">Todo esto es la cita.</div>';

        $this->assertSame($html, EmailReplyQuoteStripper::stripHtml($html));
    }

    public function test_strip_html_returns_null_and_empty_string_unchanged(): void
    {
        $this->assertNull(EmailReplyQuoteStripper::stripHtml(null));
        $this->assertSame('', EmailReplyQuoteStripper::stripHtml(''));
    }

    public function test_strip_text_cuts_the_spanish_gmail_marker(): void
    {
        $text = "Perfecto, gracias.\n\nEl jue, 3 sept 2026 a la(s) 10:54 p.m., <info@functionbytes.com> escribió:\n> Hemos recibido tu solicitud\n> Número de ticket: #TCK-2026-00093";

        $result = EmailReplyQuoteStripper::stripText($text);

        $this->assertSame('Perfecto, gracias.', $result);
    }

    public function test_strip_text_cuts_the_english_gmail_marker(): void
    {
        $text = "Thanks!\n\nOn Thu, Sep 3, 2026 at 10:54 PM, <info@functionbytes.com> wrote:\n> We received your request";

        $this->assertSame('Thanks!', EmailReplyQuoteStripper::stripText($text));
    }

    public function test_strip_text_cuts_outlook_original_message_header(): void
    {
        $text = "Aquí va mi respuesta.\n\n-----Mensaje original-----\nDe: soporte@example.com\nEnviado: jueves\nAsunto: Tu ticket";

        $this->assertSame('Aquí va mi respuesta.', EmailReplyQuoteStripper::stripText($text));
    }

    public function test_strip_text_returns_the_original_when_no_marker_is_found(): void
    {
        $text = "Un mensaje normal de varias líneas.\nSin ninguna cita.";

        $this->assertSame($text, EmailReplyQuoteStripper::stripText($text));
    }
}
