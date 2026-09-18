<?php

namespace Modules\Media\Observers;

use Spatie\Activitylog\Models\Activity;

class ActivityLogSignatureObserver
{
    public function creating(Activity $activity): void
    {
        if (! str_starts_with($activity->log_name ?? '', 'media.')) {
            return;
        }

        $payload = [
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'properties' => is_string($activity->properties)
                ? $activity->properties
                : json_encode($activity->properties),
            'created_at' => (string) now(),
        ];

        ksort($payload);

        $activity->hmac_signature = hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            config('app.key')
        );
    }

    public function updating(Activity $activity): void
    {
        if (str_starts_with($activity->log_name ?? '', 'media.')) {
            throw new \RuntimeException('Activity log del módulo Media es inmutable');
        }
    }

    public function deleting(Activity $activity): void
    {
        if (str_starts_with($activity->log_name ?? '', 'media.')) {
            throw new \RuntimeException('Activity log del módulo Media no se puede borrar');
        }
    }
}
