<?php

namespace Modules\HelpdeskTickets\Support;

/**
 * Corta la cita del correo anterior que los clientes de email añaden al
 * responder (el propio Gmail: "El jue, 3 sept 2026 a la(s) 22:54, <...>
 * escribió:" seguido del HTML completo del correo original citado) —
 * detectado en vivo (3-sep-2026, TCK-2026-00093): la respuesta real del
 * cliente ("perfecto, quiero saber si...") llegaba pegada a las ~40 líneas
 * de tabla/estilos inline de la propia plantilla de confirmación que le
 * habíamos mandado, y el panel enseñaba ese bloque entero como si el
 * cliente lo hubiese escrito.
 *
 * Solo se aplica al `body`/`html_body` del TicketItem (lo que se ve en el
 * hilo) -- el TicketMail sigue guardando `raw_email`/`body_html` COMPLETOS,
 * sin tocar, para auditoría ("Correo" / "Ver original" en el panel).
 *
 * Heurística por marcadores conocidos, no un parser genérico de RFC 5322:
 * cubre los clientes que de verdad aparecen en este proyecto (Gmail,
 * Outlook, gestores de listas tipo ">"). Un cliente de correo no
 * contemplado simplemente no se recorta -- preferible a recortar de más y
 * perder parte de un mensaje real.
 */
class EmailReplyQuoteStripper
{
    /**
     * Contenedores HTML de cita, en el orden en que merece la pena probarlos.
     * Se corta el HTML en el primer marcador que aparezca.
     *
     * @var array<int, string>
     */
    private const HTML_QUOTE_MARKERS = [
        '<div class="gmail_quote',
        '<blockquote class="gmail_quote',
        '<div id="mail-editor-reference-message-container"',
        '<div id="appendonsend"',
        '<div id="divRplyFwdMsg"',
        '<blockquote type="cite"',
        '<div class="moz-cite-prefix"',
    ];

    /**
     * Patrones de texto plano que marcan el inicio de la cita. Cada uno se
     * usa como regex (case-insensitive, multilinea) y se corta desde donde
     * empieza el match.
     *
     * @var array<int, string>
     */
    private const TEXT_QUOTE_PATTERNS = [
        // Gmail español/inglés: "El <fecha>, <quien> escribió:" / "On <fecha>, <quien> wrote:"
        // Con /s (el "." también matiza saltos de línea): Gmail envuelve esta
        // cabecera a ~76 columnas en texto plano, así que con un nombre+email
        // largo (ej. "Cristian Esparza (functionbytes@gmail.com)") el propio
        // "escribió:" cae en la línea siguiente -- un patrón anclado a una
        // sola línea (^...$) nunca hacía match y la cita entera se colaba tal
        // cual (caso real, 3-sep-2026, segunda respuesta de TCK-2026-00093).
        // El límite de 150 caracteres evita que un mensaje sin este marcador
        // termine recortándose por casualidad más adelante en el texto.
        // /u (unicode): sin él, la clase [oó] parte "ó" (2 bytes en UTF-8) en
        // dos alternativas de UN byte cada una, y deja de casar como
        // carácter completo -- justo lo que rompió esto la primera vez.
        //
        // \r? antes de $: el body_text real de webklex/php-imap trae saltos
        // de línea \r\n (RFC 5322), no \n sueltos como en los fixtures de los
        // tests -- sin el \r? opcional, el \r que sobra justo antes del \n
        // real impedía que $ (con /m) encajara ahí, y la cita completa se
        // colaba entera (mismo caso real de arriba, confirmado con el body
        // guardado de verdad: bytes 0d 0a 0d 0a = "\r\n\r\n" tras "escribió:").
        '/^El .{0,150}escribi[oó]:[ \t]*\r?$/msiu',
        '/^On .{0,150}wrote:[ \t]*\r?$/msi',
        // Outlook clásico.
        '/^-{2,}\s*Mensaje original\s*-{2,}[ \t]*\r?$/mi',
        '/^-{2,}\s*Original Message\s*-{2,}[ \t]*\r?$/mi',
        // Cabecera de reenvío/respuesta genérica (Outlook/Thunderbird sueltan
        // "De: ... Enviado: ... Para: ... Asunto:" como bloque plano).
        '/^De:\s.+\r?\nEnviado:\s.+\r?\nPara:\s.+\r?\nAsunto:/mi',
        '/^From:\s.+\r?\nSent:\s.+\r?\nTo:\s.+\r?\nSubject:/mi',
    ];

    public static function stripHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $lower = mb_strtolower($html);

        $cutAt = null;

        foreach (self::HTML_QUOTE_MARKERS as $marker) {
            $pos = mb_strpos($lower, mb_strtolower($marker));

            if ($pos !== false && ($cutAt === null || $pos < $cutAt)) {
                $cutAt = $pos;
            }
        }

        if ($cutAt === null) {
            return $html;
        }

        $trimmed = trim(mb_substr($html, 0, $cutAt));

        // Si el recorte deja el cuerpo vacío (raro: alguien responde con un
        // mensaje vacío o solo un adjunto), mejor conservar el original
        // completo que enseñar "(sin contenido)" cuando sí escribió algo,
        // aunque fuera solo dentro del bloque citado.
        return $trimmed !== '' ? $trimmed : $html;
    }

    public static function stripText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $cutAt = null;

        foreach (self::TEXT_QUOTE_PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];

                if ($cutAt === null || $pos < $cutAt) {
                    $cutAt = $pos;
                }
            }
        }

        if ($cutAt === null) {
            return $text;
        }

        // substr(), no mb_substr(): PREG_OFFSET_CAPTURE devuelve el offset en
        // BYTES, no en caracteres. Con un texto que traiga alguna tilde antes
        // del marcador (p. ej. "Aquí..."), mb_substr con ese mismo número
        // cortaba un carácter de más -- el corte cae siempre justo en un
        // salto de línea/guion (ASCII), así que substr() en bytes es seguro
        // aquí y no puede partir un carácter multibyte por la mitad.
        $trimmed = trim(substr($text, 0, $cutAt));

        return $trimmed !== '' ? $trimmed : $text;
    }
}
