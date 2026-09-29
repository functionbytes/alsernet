<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Almacenamiento de adjuntos de conversación según config('helpdesk.attachments.disk').
 *
 * Seguridad 29-sep-2026 (A9): con el disco 'public' los adjuntos se sirven tal
 * cual desde /storage/… (Apache). Con un disco privado ('local') no hay URL
 * pública: se genera una URL FIRMADA y permanente a la ruta
 * helpdesk.attachments.file, que sirve el archivo con cabeceras seguras
 * (nosniff, CSP sandbox, descarga forzada para tipos no inertes). Es firmada y
 * no autenticada porque también la consumen el widget del cliente y Meta
 * (WhatsApp/FB/IG descargan el adjunto que envía el agente).
 *
 * Los adjuntos antiguos (URLs /storage/helpdesk/… guardadas en BD) se siguen
 * resolviendo contra el disco 'public'.
 */
class ConversationAttachmentStorage
{
    /** Tipos que se pueden mostrar inline sin riesgo de ejecutar código. */
    private const INLINE_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
    ];

    public function diskName(): string
    {
        return (string) config('helpdesk.attachments.disk', 'public');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    /** ¿El disco configurado publica sus archivos por URL directa? */
    public function isPublicDisk(?string $disk = null): bool
    {
        $disk ??= $this->diskName();

        return filled(config("filesystems.disks.{$disk}.url"));
    }

    /**
     * Guarda un archivo subido con nombre aleatorio (hashName) y devuelve [path, url].
     *
     * @return array{0: string, 1: string}
     */
    public function storeUploaded(UploadedFile $file, string $folder, ?string $filename = null): array
    {
        $filename ??= $file->hashName();
        $path = trim($folder, '/').'/'.$filename;
        $this->disk()->putFileAs(trim($folder, '/'), $file, $filename);

        return [$path, $this->url($path)];
    }

    /** URL absoluta con la que se muestra/descarga un adjunto del disco configurado. */
    public function url(string $path, ?string $disk = null): string
    {
        $disk ??= $this->diskName();
        $path = ltrim($path, '/');

        if ($this->isPublicDisk($disk)) {
            return Storage::disk($disk)->url($path);
        }

        // Firma relativa: válida con APP_URL y con helpdesk.public_url (Meta).
        $relative = URL::signedRoute('helpdesk.attachments.file', ['path' => $path, 'disk' => $disk], null, false);

        return rtrim((string) (config('app.url') ?: url('/')), '/').$relative;
    }

    /**
     * Resuelve una URL de adjunto guardada en BD a [disk, path], o null si no es nuestra.
     * Acepta el formato antiguo (/storage/helpdesk/…), el de la ruta firmada y
     * el de los adjuntos del widget de livechat (/hd/attachments/…).
     *
     * @return array{0: string, 1: string}|null
     */
    public function resolve(mixed $url): ?array
    {
        if (is_array($url)) {
            $url = $url['url'] ?? null;
        }

        if (! is_string($url) || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }
        $path = rawurldecode($path);

        // Formato antiguo en disco public (incluye adjuntos del widget y de la
        // galería de chat, que el agente también puede descargar/reenviar).
        if (preg_match('#^/storage/((?:helpdesk|helpdesk-conversations|chat-imports)/.+)$#', $path, $m)) {
            return $this->safePath($m[1], true) ? ['public', $m[1]] : null;
        }

        if (preg_match('#^/helpdesk/attachments/file/(helpdesk/.+)$#', $path, $m)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $disk = $this->allowedDisk($query['disk'] ?? null);

            return ($disk && $this->safePath($m[1])) ? [$disk, $m[1]] : null;
        }

        // Adjuntos del widget de livechat (29-sep-2026): disco privado 'local',
        // servidos por /hd/attachments/{conversación}/{fichero}
        // (HelpdeskLivechat WidgetAttachmentController). Mismo patrón de nombre
        // que la ruta.
        if (preg_match('#^/hd/attachments/(\d+)/([A-Za-z0-9]{40}(?:\.[a-z0-9]{1,5})?)$#', $path, $m)) {
            $rel = "helpdesk-conversations/{$m[1]}/{$m[2]}";

            return $this->safePath($rel, true) ? ['local', $rel] : null;
        }

        return null;
    }

    /** Disco permitido para servir adjuntos por la ruta firmada. */
    public function allowedDisk(mixed $disk): ?string
    {
        $disk = is_string($disk) && $disk !== '' ? $disk : $this->diskName();

        return in_array($disk, array_unique(['local', 'public', $this->diskName()]), true) ? $disk : null;
    }

    public function safePath(string $path, bool $legacyPublic = false): bool
    {
        $prefixOk = str_starts_with($path, 'helpdesk/')
            || ($legacyPublic && (str_starts_with($path, 'helpdesk-conversations/') || str_starts_with($path, 'chat-imports/')));

        return $prefixOk
            && ! str_contains($path, '..')
            && ! str_contains($path, "\0");
    }

    /**
     * Respuesta con cabeceras seguras. Solo imágenes rasterizadas, PDF, audio y
     * vídeo se muestran inline; el resto se fuerza como descarga binaria.
     */
    public function response(string $disk, string $path, ?string $downloadName = null, bool $forceDownload = false): StreamedResponse
    {
        $storage = Storage::disk($disk);
        $mime = strtolower((string) ($storage->mimeType($path) ?: 'application/octet-stream'));

        $inline = ! $forceDownload && (
            in_array($mime, self::INLINE_MIMES, true)
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/')
        );

        $headers = [
            'Content-Type' => $inline ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ];

        // El visor PDF de Chrome no abre documentos con CSP sandbox.
        if ($mime !== 'application/pdf') {
            $headers['Content-Security-Policy'] = "default-src 'none'; sandbox";
        }

        return $storage->response($path, $downloadName ?? basename($path), $headers, $inline ? 'inline' : 'attachment');
    }
}
