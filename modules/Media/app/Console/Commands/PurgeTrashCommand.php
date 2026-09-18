<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Services\MediaFileService;
use Modules\Media\Services\MediaFolderService;

class PurgeTrashCommand extends Command
{
    protected $signature = 'media:purge-trash {--days=30}';

    protected $description = 'Elimina permanentemente archivos y carpetas en papelera con más de N días';

    public function handle(MediaFileService $fileService, MediaFolderService $folderService): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $files = MediaFile::onlyTrashed()->where('deleted_at', '<=', $threshold)->get();
        $folders = MediaFolder::onlyTrashed()->where('deleted_at', '<=', $threshold)->get();

        foreach ($files as $file) {
            $fileService->forceDelete($file);
        }

        foreach ($folders as $folder) {
            $folderService->forceDelete($folder);
        }

        $this->info("Purgados {$files->count()} archivos y {$folders->count()} carpetas.");

        return self::SUCCESS;
    }
}
