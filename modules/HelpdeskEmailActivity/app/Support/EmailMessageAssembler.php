<?php

namespace Modules\HelpdeskEmailActivity\Support;

use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Reconstruye la vista técnica de un envío a partir de lo que se guardó de él:
 * el mensaje .eml completo y sus cabeceras separadas en pares.
 *
 * Vive fuera del controlador porque lo consumen tres sitios con el mismo
 * contrato: la descarga .eml, la pestaña "Original" del inspector y la de
 * "Cabeceras". Duplicarlo llevaría a que la descarga y lo que se ve en
 * pantalla acabaran divergiendo.
 */
class EmailMessageAssembler
{
    /**
     * Mensaje completo (cabeceras + línea en blanco + cuerpo).
     *
     * Cuando el envío es anterior a la captura de `raw_headers` —o su contenido
     * se purgó— las cabeceras se reconstruyen a partir de las columnas del
     * registro, y el mensaje lo declara en una cabecera propia: NO es una copia
     * verbatim de lo que recibió el destinatario y quien lo lea debe saberlo.
     */
    public static function eml(EmailLog $emailLog): string
    {
        $headers = $emailLog->raw_headers;

        if ($headers === null) {
            $lines = [
                'X-HelpdeskEmailActivity-Note: Cabeceras reconstruidas — no es una captura verbatim del envío original.',
                'From: '.($emailLog->from_name ? "{$emailLog->from_name} <{$emailLog->from_address}>" : $emailLog->from_address),
                'To: '.implode(', ', $emailLog->to_addresses ?? []),
            ];

            if ($emailLog->message_id) {
                $lines[] = "Message-ID: <{$emailLog->message_id}>";
            }

            $lines[] = 'Subject: '.$emailLog->subject;
            $lines[] = 'Date: '.($emailLog->created_at?->toRfc2822String() ?? '');
            $headers = implode("\r\n", $lines);
        }

        $body = $emailLog->body_html ?: ($emailLog->body_text ?? '');

        if (! empty($emailLog->attachments)) {
            // Los adjuntos solo guardan metadatos (nombre/tamaño/tipo), nunca
            // el binario — un .eml reconstruido no puede incluirlos de verdad.
            $body .= "\r\n\r\n[Nota: este .eml no incluye los adjuntos originales — solo se conservaron sus metadatos.]";
        }

        return $headers."\r\n\r\n".$body;
    }

    public static function hasContent(EmailLog $emailLog): bool
    {
        return $emailLog->raw_headers !== null || (bool) $emailLog->body_html || (bool) $emailLog->body_text;
    }

    /**
     * Cabeceras en pares nombre => valor, listas para pintar en tabla.
     *
     * Respeta las continuaciones RFC 5322 (una cabecera larga partida en varias
     * líneas que empiezan por espacio o tabulador, como Received o
     * DKIM-Signature) y conserva las repetidas —Received aparece una vez por
     * salto— uniéndolas en una sola entrada multivalor.
     *
     * @return list<array{name: string, value: string}>
     */
    public static function headers(EmailLog $emailLog): array
    {
        $raw = $emailLog->raw_headers;

        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $headers = [];
        $current = null;

        foreach (preg_split('~\r\n|\r|\n~', $raw) ?: [] as $line) {
            if ($line === '') {
                continue;
            }

            if ($current !== null && preg_match('~^[ \t]~', $line) === 1) {
                $headers[$current][count($headers[$current]) - 1] .= ' '.trim($line);

                continue;
            }

            // Delimitador # y no ~: el rango de caracteres válidos en el
            // nombre de una cabecera (RFC 5322) termina justamente en ~.
            if (preg_match('#^([!-9;-~]+):[ \t]*(.*)$#', $line, $matches) === 1) {
                $current = $matches[1];
                $headers[$current] ??= [];
                $headers[$current][] = trim($matches[2]);
            }
        }

        $pairs = [];

        foreach ($headers as $name => $values) {
            $pairs[] = ['name' => $name, 'value' => implode("\n", $values)];
        }

        usort($pairs, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $pairs;
    }
}
