<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedChunksCommand extends Command
{
    protected $signature = 'media:clean-orphaned-chunks';

    protected $description = 'Elimina directorios de chunks abandonados más viejos que el threshold configurado';

    public function handle(): int
    {
        $threshold = strtotime(config('media.chunk.clear.timestamp', '-3 HOURS'));
        $disk = config('media.chunk.storage.disk', 'local');
        $chunkRoot = config('media.chunk.storage.chunks', 'chunks');

        if (! Storage::disk($disk)->exists($chunkRoot)) {
            $this->info('Sin directorio de chunks.');

            return self::SUCCESS;
        }

        $dirs = Storage::disk($disk)->directories($chunkRoot);
        $cleaned = 0;

        foreach ($dirs as $dir) {
            $mtime = Storage::disk($disk)->lastModified($dir);

            if ($mtime && $mtime < $threshold) {
                Storage::disk($disk)->deleteDirectory($dir);
                $cleaned++;
            }
        }

        $this->info("Eliminados {$cleaned} directorios de chunks huérfanos.");

        return self::SUCCESS;
    }
}
