<?php

namespace Modules\Media\Observers;

use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Setting;
use Modules\Media\Jobs\OptimizeImageJob;
use Modules\Media\Models\MediaFile;

/**
 * Dispara la optimización automática de imagen (webp + srcset) cada vez
 * que se crea un MediaFile. Queueado para no bloquear el upload — el
 * navegador del usuario ve WebP/responsive tan pronto como la cola
 * procese el job (típicamente segundos).
 *
 * Se puede desactivar con `Setting::set('optimize.auto_optimize_uploads',
 * '0')` desde el panel.
 */
class MediaFileOptimizeObserver
{
    public function created(MediaFile $file): void
    {
        if (Setting::get('optimize.auto_optimize_uploads', '1') !== '1') {
            return;
        }
        if ($file->type !== 'image') {
            return;
        }
        if (! in_array($file->mime_type, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            return;
        }

        OptimizeImageJob::dispatch($file->id);
    }

    /**
     * Al eliminar el MediaFile original, borrar los archivos derivados
     * (.webp + -Ww.webp) del disco. Previene huérfanos que inflan el
     * storage y pueden servirse por error.
     */
    public function deleted(MediaFile $file): void
    {
        if ($file->type !== 'image') {
            return;
        }
        if (! in_array($file->mime_type, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
            return;
        }

        $diskName = $file->disk ?: config('filesystems.default');
        $disk = Storage::disk($diskName);
        $relative = ltrim((string) $file->url, '/');
        if ($relative === '') {
            return;
        }

        $siblings = [];
        // .webp hermano simple
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
        if (is_string($webp) && $webp !== $relative) {
            $siblings[] = $webp;
        }
        // variantes responsive
        foreach ([480, 768, 1024, 1920] as $w) {
            $variant = preg_replace('/\.(jpe?g|png)$/i', '-'.$w.'w.webp', $relative);
            if (is_string($variant)) {
                $siblings[] = $variant;
            }
        }

        foreach ($siblings as $sib) {
            if ($disk->exists($sib)) {
                $disk->delete($sib);
            }
        }
    }
}
