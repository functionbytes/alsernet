<?php

namespace Modules\HelpdeskTickets\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

            $allowedExtensions = $this->allowedAttachmentExtensions();
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

            $basePath = config('helpdesk.attachments.path', 'helpdesk/attachments');
            $path = $basePath.'/'.date('Y/m/d').'/'.$filename;

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
