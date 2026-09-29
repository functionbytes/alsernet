<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Helpdesk\Services\HelpdeskSettings;
use Modules\HelpdeskTickets\Services\TicketAttachmentSecurityService;
use Webklex\PHPIMAP\Attachment as ImapAttachment;
use Webklex\PHPIMAP\Message as ImapMessage;

/**
 * Guardado de adjuntos entrantes en disco, con lista blanca de extensiones,
 * límite de tamaño y escaneo antimalware. Extraído de FetchTicketEmailsJob
 * (30-sep-2026, job de 1103 líneas) — FetchTicketEmailsJob::parseAttachments()/
 * etc. son ahora delegados finos a esta clase; ver su docblock para el
 * porqué del reparto.
 */
class InboundEmailAttachmentStorer
{
    /**
     * Parse attachments from email message.
     *
     * El disco se lee una sola vez aquí y se pasa a saveAttachment(): antes
     * este método construía la URL pública asumiendo el disco 'public'
     * (asset('storage/...')) mientras saveAttachment() escribía siempre en
     * 'local' — un disco privado sin symlink público, así que la URL
     * resultante daba 404 siempre. Ahora ambos usan el mismo disco
     * (config('helpdesk.attachments.disk')) y el adjunto se sirve por la
     * ruta autorizada existente (ver attachment_urls en processIncomingEmail()).
     */
    public function parseAttachments(ImapMessage $message, array &$skippedAttachments = []): array
    {
        $attachments = [];
        $disk = config('helpdesk.attachments.disk', 'local');

        foreach ($message->getAttachments() as $attachment) {
            try {
                $filename = $attachment->name ?? 'attachment';
                $filePath = $this->saveAttachment($attachment, $disk, $skippedAttachments);

                if (! $filePath) {
                    continue;
                }

                $attachments[] = [
                    'filename' => $filename,
                    'disk' => $disk,
                    'path' => $filePath,
                    ...$this->attachmentMetadata($disk, $filePath),
                ];
            } catch (\Exception $e) {
                Log::warning('Error processing attachment: '.$e->getMessage());
            }
        }

        return $attachments;
    }

    /**
     * @return array{size: ?int, mime: ?string}
     */
    public function attachmentMetadata(string $disk, string $path): array
    {
        try {
            return [
                'size' => Storage::disk($disk)->size($path),
                'mime' => Storage::disk($disk)->mimeType($path) ?: null,
            ];
        } catch (\Throwable) {
            // File may not be readable right after writing; metadata stays null.
            return ['size' => null, 'mime' => null];
        }
    }

    /**
     * Save attachment to storage.
     */
    public function saveAttachment(ImapAttachment $attachment, string $disk, array &$skippedAttachments = []): ?string
    {
        try {
            $filename = $attachment->name ?? time().'_'.random_int(1000, 9999);

            $allowedExtensions = array_values(array_diff(
                $this->allowedAttachmentExtensions(),
                self::FORBIDDEN_ATTACHMENT_EXTENSIONS,
            ));
            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

            if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
                Log::warning('FetchTicketEmailsJob: skipped attachment with disallowed extension', [
                    'filename' => $filename,
                ]);

                // Antes esto se perdía en silencio salvo por el log del
                // servidor — el agente no tenía forma de saber, desde el
                // panel, que el correo traía un adjunto que no llegó (bug
                // real encontrado 4-sep-2026, un .mp3 real descartado sin
                // rastro). processIncomingEmail() deja una nota de sistema
                // en el hilo con esta lista.
                $skippedAttachments[] = $filename;

                return null;
            }

            $content = $attachment->getContent();
            $maxBytes = app(HelpdeskSettings::class)->attachmentMaxKilobytes() * 1024;
            if (strlen($content) > $maxBytes) {
                $skippedAttachments[] = $filename;
                Log::warning('FetchTicketEmailsJob: skipped oversized attachment', [
                    'filename' => $filename,
                    'size' => strlen($content),
                    'max_bytes' => $maxBytes,
                ]);

                return null;
            }

            // 29-sep-2026 (A7): tipo real del contenido (finfo) contra la lista
            // permitida; antes solo se miraba la extensión que pone el remitente.
            $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
            if (! $this->attachmentMimeAllowed($mime, $extension)) {
                $skippedAttachments[] = $filename;
                Log::warning('FetchTicketEmailsJob: skipped attachment with disallowed content type', [
                    'filename' => $filename,
                    'mime' => $mime,
                ]);

                return null;
            }

            // Nombre aleatorio en disco (antes el nombre original en una ruta
            // solo con la fecha: adivinable y dos adjuntos iguales se pisaban).
            // El nombre original va a BD (metadata.attachment_names del item).
            $basePath = config('helpdesk.attachments.path', 'helpdesk/attachments');
            $path = $basePath.'/'.date('Y/m/d').'/'.Str::uuid().'.'.$extension;

            Storage::disk($disk)->put($path, $content);

            try {
                app(TicketAttachmentSecurityService::class)->assertSafeStored($disk, $path, $filename);
            } catch (\Throwable $exception) {
                Storage::disk($disk)->delete($path);
                $skippedAttachments[] = $filename;
                Log::warning('FetchTicketEmailsJob: skipped attachment blocked by malware scan', [
                    'filename' => $filename,
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }

            return $path;
        } catch (\Exception $e) {
            Log::error('Error saving attachment: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extensiones que nunca se aceptan aunque se añadan en Ajustes (guía de
     * desarrollo seguro, apartado 2.4).
     */
    private const FORBIDDEN_ATTACHMENT_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'inc',
        'htaccess', 'ini', 'cgi', 'pl', 'py', 'sh', 'shtml', 'html', 'htm', 'xhtml', 'xml',
        'svg', 'svgz', 'js', 'mjs',
    ];

    /** Tipos que nunca se guardan (se ejecutan o se pintan en el navegador). */
    private const FORBIDDEN_ATTACHMENT_MIMES = [
        'text/html', 'application/xhtml+xml', 'image/svg+xml', 'text/xml', 'application/xml',
        'application/javascript', 'text/javascript', 'text/x-php', 'application/x-php',
        'application/x-httpd-php', 'text/x-shellscript',
    ];

    /**
     * MIME real (finfo) permitido: el de la lista de Ajustes/config, más los
     * que libmagic da a formatos Office legítimos (contenedor OLE/ZIP).
     */
    private function attachmentMimeAllowed(string $mime, string $extension): bool
    {
        if ($mime === '' || in_array($mime, self::FORBIDDEN_ATTACHMENT_MIMES, true)) {
            return false;
        }

        if (in_array($mime, app(HelpdeskSettings::class)->attachmentMimeTypes(), true)) {
            return true;
        }

        $aliases = match ($extension) {
            'doc', 'xls', 'ppt' => ['application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage', 'application/vnd.ms-excel', 'application/msword'],
            'docx', 'xlsx', 'pptx' => ['application/zip', 'application/octet-stream', 'application/encrypted'],
            'csv', 'txt' => ['text/plain', 'text/csv', 'application/csv'],
            'jpg', 'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'mp3' => ['audio/mpeg', 'audio/mp3'],
            'wav' => ['audio/wav', 'audio/x-wav', 'audio/wave'],
            'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
            'ogg' => ['audio/ogg', 'application/ogg', 'video/ogg'],
            'webm' => ['video/webm', 'audio/webm'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            default => [],
        };

        return in_array($mime, $aliases, true);
    }

    /**
     * Lista blanca de extensiones de adjunto entrante, ahora configurable
     * desde Ajustes → Subida de archivos (Setting uploading.allowed_extensions).
     * Esa pantalla ya existía y guardaba el valor, pero ningún consumidor lo
     * leía — un admin podía "guardar" un cambio ahí sin que tuviera ningún
     * efecto real (bug real encontrado 4-sep-2026). Sin nada guardado, cae al
     * mismo default fijo de siempre.
     *
     * @return list<string>
     */
    public function allowedAttachmentExtensions(): array
    {
        return app(HelpdeskSettings::class)->attachmentExtensions();
    }
}
