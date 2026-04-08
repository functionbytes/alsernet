<?php

namespace Modules\Template\Observers;

use Illuminate\Support\Facades\File;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;

class TemplateObserver
{
    /**
     * Handle the Template "created" event.
     */
    public function created(Template $template): void
    {
        // Crear la primera versión automáticamente
        TemplateVersion::create([
            'template_id' => $template->id,
            'version' => 1,
            'content' => $template->content,
            'changed_fields' => json_encode([]),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the Template "updated" event.
     */
    public function updated(Template $template): void
    {
        // Crear snapshot de cambios
        if ($template->isDirty('content')) {
            $nextVersion = ($template->versions()->max('version') ?? 0) + 1;

            $changedFields = [];
            foreach ($template->getDirty() as $field => $newValue) {
                if ($field !== 'updated_at') {
                    $changedFields[$field] = [
                        'old' => $template->getOriginal($field),
                        'new' => $newValue,
                    ];
                }
            }

            TemplateVersion::create([
                'template_id' => $template->id,
                'version' => $nextVersion,
                'content' => $template->content,
                'changed_fields' => json_encode($changedFields),
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the Template "deleted" event.
     */
    public function deleted(Template $template): void
    {
        //
    }

    /**
     * Handle the Template "restored" event.
     */
    public function restored(Template $template): void
    {
        //
    }

    /**
     * Handle the Template "force deleted" event.
     */
    public function forceDeleted(Template $template): void
    {
        $path = base_path($template->template_path ?? "platform/themes/{$template->slug}");

        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
    }
}
