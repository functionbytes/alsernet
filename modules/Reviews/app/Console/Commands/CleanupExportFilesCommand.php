<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupExportFilesCommand extends Command
{
    protected $signature = 'reviews:cleanup-exports';

    protected $description = 'Eliminar archivos de exportacion con mas de 7 dias de antiguedad';

    public function handle(): int
    {
        $this->info('Buscando archivos de exportacion antiguos...');

        $disk = Storage::disk('local');
        $cutoff = now()->subDays(7);
        $deleted = 0;

        $files = $disk->files('exports');

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);

            if ($lastModified < $cutoff->timestamp) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Archivos eliminados: {$deleted}");

        Log::info('reviews:cleanup-exports completed', [
            'deleted' => $deleted,
            'cutoff' => $cutoff->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }
}
