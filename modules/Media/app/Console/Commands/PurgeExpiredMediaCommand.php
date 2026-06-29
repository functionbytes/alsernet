<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\MediaFileService;

class PurgeExpiredMediaCommand extends Command
{
    protected $signature = 'media:purge-expired';

    protected $description = 'Elimina permanentemente archivos cuyo expires_at ya pasó';

    public function handle(MediaFileService $service): int
    {
        $expired = MediaFile::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $file) {
            $service->forceDelete($file);
        }

        $this->info("Purgados {$expired->count()} archivos expirados.");

        return self::SUCCESS;
    }
}
