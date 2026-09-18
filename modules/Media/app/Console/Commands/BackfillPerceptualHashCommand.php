<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\MediaFileService;

class BackfillPerceptualHashCommand extends Command
{
    protected $signature = 'media:backfill-phash {--batch=100}';

    protected $description = 'Calcula phash para imágenes existentes sin phash';

    public function handle(MediaFileService $service): int
    {
        $batch = (int) $this->option('batch');

        MediaFile::query()
            ->where('type', 'image')
            ->whereNull('phash')
            ->chunk($batch, function ($files) use ($service): void {
                foreach ($files as $file) {
                    $disk = Storage::disk($file->disk);

                    if (! $disk->exists($file->url)) {
                        continue;
                    }

                    $phash = $service->calculatePhash($disk->path($file->url));

                    if ($phash) {
                        $file->update(['phash' => $phash]);
                        $this->info("phash: {$file->id}");
                    }
                }
            });

        return self::SUCCESS;
    }
}
