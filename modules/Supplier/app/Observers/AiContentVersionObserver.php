<?php

namespace Modules\Supplier\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Ai\AiContentVersion;

class AiContentVersionObserver
{
    private const VERSIONED_FIELDS = [
        'long_description', 'generated_name', 'short_description',
        'seo_title', 'seo_description',
    ];

    public function updating(AiContent $content): void
    {
        $changed = false;
        foreach (self::VERSIONED_FIELDS as $field) {
            if ($content->isDirty($field)) {
                $changed = true;
                break;
            }
        }

        if (! $changed) {
            return;
        }

        // Verificar que el registro padre existe antes de crear la versión.
        // Puede no existir si el AiContent fue eliminado entre el create() y el update()
        // durante una sync, o si se está actualizando un modelo Eloquent sin persistencia real.
        if (! AiContent::where('id', $content->id)->exists()) {
            Log::warning('AiContentVersionObserver: content not found in DB, skipping version', [
                'content_id' => $content->id,
            ]);

            return;
        }

        try {
            $lastVersion = AiContentVersion::where('content_id', $content->id)
                ->max('version_number');
            $nextVersion = ((int) $lastVersion) + 1;

            AiContentVersion::create([
                'content_id'       => $content->id,
                'user_id'          => auth()->id(),
                'version_number'   => $nextVersion,
                'long_description' => $content->getOriginal('long_description'),
                'generated_name'   => $content->getOriginal('generated_name'),
                'short_description'=> $content->getOriginal('short_description'),
                'seo_title'        => $content->getOriginal('seo_title'),
                'seo_description'  => $content->getOriginal('seo_description'),
                'change_reason'    => request()->input('change_reason'),
            ]);
        } catch (\Exception $e) {
            // No propagar el error: un fallo al versionar no debe interrumpir
            // la generación de contenido ni la sincronización del batch.
            Log::error('AiContentVersionObserver: failed to create version', [
                'content_id' => $content->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
