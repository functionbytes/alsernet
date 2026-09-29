<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Seguridad 29-sep-2026: se retiraron las rutas públicas /clear (limpiaba
 * cachés y los contadores de throttle sin login), /files, /thumbs y
 * /p/assets (lectura arbitraria de ficheros latente, sin uso). Solo queda
 * /assets/{dirname}/{basename} porque PathHelper::generatePublicPath() la
 * referencia por nombre; ahora está confinada a storage/app/public.
 */
class FileServeController extends Controller
{
    public function publicAsset(string $dirname, string $basename): BinaryFileResponse
    {
        $decoded = base64_decode(strtr($dirname, '-_', '+/'), true);
        abort_if($decoded === false, 404);

        $base = realpath(storage_path('app/public'));
        abort_unless($base !== false, 404);

        // PathHelper genera el dirname relativo a storage/ ("app/public/...").
        $relative = ltrim(preg_replace('#^app/public(/|$)#', '', $decoded), '/');
        $abs = realpath($base.'/'.($relative !== '' ? $relative.'/' : '').rawurldecode($basename));

        abort_unless(
            $abs !== false && str_starts_with($abs, $base.DIRECTORY_SEPARATOR) && is_file($abs) && is_readable($abs),
            404
        );

        return response()->file($abs, [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="'.addcslashes(basename($abs), '"\\').'"',
        ]);
    }
}
