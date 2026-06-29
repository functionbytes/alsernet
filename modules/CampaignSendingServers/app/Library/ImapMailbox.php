<?php

namespace Modules\CampaignSendingServers\Library;

use Exception;

/**
 * Wrapper minimalista sobre la extensión imap_* de PHP para abrir un buzón
 * IMAP/POP3, iterar mensajes y procesarlos.
 *
 * No usamos webklex/php-imap o php-imap/php-imap como dependencia para
 * mantener este módulo sin paquetes extra. Si la extensión `imap` no está
 * instalada, lanza excepción descriptiva.
 *
 * El "bounce parser" propiamente dicho NO vive aquí — esta clase sólo
 * baja los mensajes y devuelve estructuras crudas (header + body).
 */
class ImapMailbox
{
    protected $stream;

    /**
     * @param  string  $host  ej: imap.gmail.com
     * @param  int  $port  993 (IMAP+SSL) | 143 (IMAP) | 995 (POP3+SSL) | 110 (POP3)
     * @param  string  $protocol  'imap'|'pop3'
     * @param  string  $encryption  'ssl'|'tls'|'none'
     * @param  string  $folder  INBOX por defecto
     */
    public function __construct(
        protected string $host,
        protected int $port,
        protected string $protocol,
        protected string $encryption,
        protected string $username,
        protected string $password,
        protected string $folder = 'INBOX',
    ) {
        if (! function_exists('imap_open')) {
            throw new Exception('La extensión PHP `imap` no está instalada. Instala php-imap o equivalente.');
        }
    }

    /**
     * Abre la conexión. Lanza excepción si falla.
     */
    public function open(): void
    {
        $flags = '/'.$this->protocol;
        if ($this->encryption === 'ssl') {
            $flags .= '/ssl/novalidate-cert';
        } elseif ($this->encryption === 'tls') {
            $flags .= '/tls/novalidate-cert';
        } else {
            $flags .= '/notls';
        }

        $mailbox = "{{$this->host}:{$this->port}{$flags}}{$this->folder}";

        $this->stream = @imap_open($mailbox, $this->username, $this->password);
        if ($this->stream === false) {
            throw new Exception('IMAP open falló: '.imap_last_error());
        }
    }

    /**
     * Cierra y vacía la cola de errores.
     */
    public function close(): void
    {
        if ($this->stream) {
            @imap_close($this->stream, CL_EXPUNGE);
        }
        // Limpia errores acumulados
        imap_errors();
        imap_alerts();
    }

    /**
     * Itera los mensajes UNSEEN y pasa cada uno al callback como
     * ['header' => string, 'body' => string, 'msgno' => int].
     * Tras procesar, marca el mensaje como leído + lo borra (CL_EXPUNGE en close()).
     */
    public function eachUnseen(callable $callback, int $limit = 100): int
    {
        if (! $this->stream) {
            throw new Exception('IMAP stream no abierto. Llama open() primero.');
        }

        $msgNumbers = imap_search($this->stream, 'UNSEEN') ?: [];
        $msgNumbers = array_slice($msgNumbers, 0, $limit);
        $processed = 0;

        foreach ($msgNumbers as $msgno) {
            $header = imap_fetchheader($this->stream, $msgno) ?: '';
            $body = imap_body($this->stream, $msgno) ?: '';

            $callback([
                'msgno' => $msgno,
                'header' => $header,
                'body' => $body,
            ]);

            // Marcar como leído y eliminado (se aplica al close con CL_EXPUNGE)
            imap_setflag_full($this->stream, (string) $msgno, '\\Seen');
            imap_delete($this->stream, $msgno);
            $processed++;
        }

        return $processed;
    }

    /**
     * Helper: extrae el primer match de un header concreto en el blob de headers.
     * Retorna null si no existe.
     */
    public static function extractHeader(string $headerBlob, string $headerName): ?string
    {
        $lines = preg_split('/\r?\n/', $headerBlob);
        $needle = strtolower($headerName).':';
        foreach ($lines as $line) {
            if (str_starts_with(strtolower($line), $needle)) {
                return trim(substr($line, strlen($needle)));
            }
        }

        return null;
    }
}
