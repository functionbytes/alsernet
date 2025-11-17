<?php

namespace App\Listeners;

use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;
use Illuminate\Support\Facades\Log;

class SetMediaPermissions
{
    public function handle(MediaHasBeenAdded $event)
    {
        $media = $event->media;
        $path = $media->getPath();

        if (file_exists($path)) {
            @chmod($path, 0777); // Archivo
            @chmod(dirname($path), 0777); // Carpeta
            Log::info("Permisos 777 aplicados a: $path");
        }
    }
}
