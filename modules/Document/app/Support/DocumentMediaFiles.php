<?php

namespace Modules\Document\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * 29-sep-2026: reglas comunes para los ficheros de expedientes (DNI, licencias,
 * adjuntos). Los ficheros viven en el disco privado 'documents_private' y el
 * nombre en disco lo genera siempre el servidor (uuid + extensión según el
 * contenido): un ".htaccess" con "%PDF-" dentro pasaba `mimes:pdf` y
 * conservaba su nombre en /storage.
 */
final class DocumentMediaFiles
{
    public const DISK = 'documents_private';

    public const COLLECTIONS = ['documents', 'additional_attachments'];

    /** Extensiones (deducidas del CONTENIDO) que admite un expediente. */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'doc', 'docx'];

    /** Imágenes que admite la importación desde el chat / dispositivo. */
    public const IMAGE_EXTENSIONS = ['avif', 'bmp', 'gif', 'heic', 'heif', 'jpg', 'jpeg', 'png', 'tif', 'tiff', 'webp'];

    /**
     * Extensión segura para guardar el fichero, deducida del contenido
     * (finfo), o null si no está en la lista blanca.
     *
     * @param  array<int, string>  $allowed
     */
    public static function safeExtension(UploadedFile $file, array $allowed = self::ALLOWED_EXTENSIONS): ?string
    {
        $ext = strtolower((string) $file->guessExtension());
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        // HEIC/HEIF: finfo suele devolver application/octet-stream; se admite
        // solo si la extensión del cliente lo dice Y la cabecera es ISO-BMFF.
        if (in_array($ext, ['', 'bin'], true)) {
            $clientExt = strtolower((string) $file->getClientOriginalExtension());
            if (in_array($clientExt, ['heic', 'heif'], true) && in_array($clientExt, $allowed, true)) {
                $head = (string) @file_get_contents((string) $file->getRealPath(), false, null, 0, 16);
                if (substr($head, 4, 4) === 'ftyp') {
                    return $clientExt;
                }
            }

            return null;
        }

        $allowedNormalized = array_map(fn ($e) => $e === 'jpeg' ? 'jpg' : $e, $allowed);

        return in_array($ext, $allowedNormalized, true) ? $ext : null;
    }

    /** Nombre en disco: uuid + extensión ya validada. Nunca el del cliente. */
    public static function storedName(string $extension): string
    {
        return Str::uuid()->toString().'.'.strtolower($extension);
    }

    /** Nombre original, solo para mostrar (se guarda como custom property). */
    public static function originalName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '';

        return Str::limit($name !== '' ? $name : 'archivo', 200, '');
    }

    /**
     * Guard de colección (acceptsFile): rechaza cualquier nombre en disco que
     * no sea uuid/aleatorio + extensión de la lista blanca, aunque algún
     * punto de subida se olvide de usar storedName().
     */
    public static function isSafeStoredName(string $fileName): bool
    {
        if ($fileName === '' || str_starts_with($fileName, '.') || preg_match('/[\/\\\\\x00-\x1f]/', $fileName)) {
            return false;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($ext, array_unique(array_merge(self::ALLOWED_EXTENSIONS, self::IMAGE_EXTENSIONS)), true);
    }

    /**
     * URL firmada y temporal a un fichero de expediente, para quien no pasa
     * por el panel de Documentos (agentes del helpdesk). Se genera solo
     * después de haber autorizado al usuario sobre ese expediente.
     */
    public static function signedUrl(Media $media, ?int $minutes = null): string
    {
        $minutes ??= (int) config('documents.signed_media_url_minutes', 120);

        return URL::temporarySignedRoute(
            'documents.media.signed',
            now()->addMinutes(max(1, $minutes)),
            ['media' => $media->id]
        );
    }
}
