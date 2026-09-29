<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Document\Support\DocumentMediaFiles;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * 29-sep-2026: sirve los ficheros de expedientes desde el disco privado.
 *
 * - panel():  GET /panel/documents/media/{media}/{path}  (auth + can:view-documents-panel).
 *   Es la URL que devuelve $media->getUrl() para el disco documents_private.
 * - signed(): GET /documents/media/{media}?expires=…&signature=…  (URL firmada
 *   temporal, para el helpdesk; ver DocumentMediaFiles::signedUrl()).
 */
class DocumentMediaController extends Controller
{
    /** Tipos que se pueden mostrar en línea (miniaturas / visor PDF). */
    private const INLINE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif',
        'image/bmp',
    ];

    public function panel(string $media, string $path = ''): Response
    {
        $model = $this->resolveMedia($media);

        // La ruta solo sirve el fichero original del propio media: nada de
        // recorrer otras rutas del disco.
        if ($path !== '' && $path !== $model->file_name) {
            abort(404);
        }

        return $this->serve($model);
    }

    public function signed(string $media): Response
    {
        return $this->serve($this->resolveMedia($media));
    }

    private function resolveMedia(string $id): Media
    {
        abort_unless(ctype_digit($id), 404);

        $media = Media::query()
            ->whereKey((int) $id)
            ->where('model_type', Document::class)
            ->whereIn('collection_name', DocumentMediaFiles::COLLECTIONS)
            ->first();

        abort_unless($media !== null, 404);

        return $media;
    }

    private function serve(Media $media): Response
    {
        $disk = Storage::disk($media->disk);
        $relative = $media->getPathRelativeToRoot();

        abort_unless($disk->exists($relative), 404);

        $mime = (string) ($media->mime_type ?: 'application/octet-stream');
        $inline = in_array($mime, self::INLINE_MIME_TYPES, true);
        $downloadName = DocumentMediaFiles::originalName(
            (string) $media->getCustomProperty('original_name', $media->file_name)
        );

        return $disk->response($relative, $downloadName, [
            'Content-Type' => $inline ? $mime : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Referrer-Policy' => 'no-referrer',
        ], $inline ? 'inline' : 'attachment');
    }
}
