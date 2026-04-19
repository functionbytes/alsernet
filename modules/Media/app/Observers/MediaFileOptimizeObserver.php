<?php

namespace Modules\Media\Observers;

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
}
