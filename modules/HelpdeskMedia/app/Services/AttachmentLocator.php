<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Services\ConversationAttachmentStorage;
use Modules\HelpdeskMedia\Support\StoredFile;

/**
 * Traduce URLs/rutas de adjuntos al disco real donde viven, y al revés.
 *
 * Conversaciones (según el punto de entrada, ver ConversationAttachmentStorage::resolve):
 *  - /storage/helpdesk/...            panel (disco public) y media de redes sociales
 *  - /storage/helpdesk-conversations/ legado del widget
 *  - /helpdesk/attachments/file/...   disco privado, URL firmada (?disk=)
 *  - /hd/attachments/{conv}/{file}    widget de livechat, disco local
 * Las URLs de otro host se ignoran.
 */
class AttachmentLocator
{
    public function __construct(private readonly ConversationAttachmentStorage $storage) {}

    public function locateUrl(mixed $url): ?StoredFile
    {
        if (! is_string($url) || ! $this->isLocalUrl($url)) {
            return null;
        }

        $resolved = $this->storage->resolve($url);

        if ($resolved === null) {
            return null;
        }

        $file = new StoredFile($resolved[0], $resolved[1]);

        return $file->exists() ? $file : null;
    }

    /**
     * Adjuntos de ticket: la fila guarda solo la ruta; el disco es el de
     * adjuntos de Helpdesk (igual que TicketAttachmentDownloadController).
     */
    public function locateTicketPath(?string $path): ?StoredFile
    {
        if (blank($path) || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        $disks = array_unique([(string) config('helpdesk.attachments.disk', 'local'), 'local', 'public']);

        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return new StoredFile($disk, $path);
            }
        }

        return null;
    }

    /**
     * URL pública equivalente tras cambiar el nombre (misma carpeta) de un fichero.
     */
    public function rebuildUrl(string $url, string $disk, string $oldPath, string $newPath): string
    {
        if ($oldPath === $newPath) {
            return $url;
        }

        $urlPath = rawurldecode((string) parse_url($url, PHP_URL_PATH));

        // La firma cubre la ruta: hay que volver a firmar.
        if (str_starts_with($urlPath, '/helpdesk/attachments/file/')) {
            return $this->storage->url($newPath, $disk);
        }

        $withoutQuery = explode('?', $url, 2)[0];
        $oldName = rawurlencode(basename($oldPath));

        if (str_ends_with($withoutQuery, $oldName)) {
            return substr($withoutQuery, 0, -strlen($oldName)).rawurlencode(basename($newPath));
        }

        return $this->storage->url($newPath, $disk);
    }

    public function isLocalUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        if (! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && in_array($host, $this->localHosts(), true);
    }

    /**
     * @return array<int, string>
     */
    private function localHosts(): array
    {
        $hosts = [];

        foreach ([config('app.url'), config('helpdesk.public_url')] as $configured) {
            $host = parse_url((string) $configured, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $hosts[] = strtolower($host);
            }
        }

        return array_values(array_unique($hosts));
    }
}
