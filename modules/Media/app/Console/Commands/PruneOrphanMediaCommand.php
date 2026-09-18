<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;

class PruneOrphanMediaCommand extends Command
{
    protected $signature = 'media:prune-orphans {--dry-run : Show count without deleting}';

    protected $description = 'Remove MediaFile records whose files no longer exist on disk';

    public function handle(): int
    {
        $orphans = $this->findOrphans();
        $count = $orphans->count();

        if ($count === 0) {
            $this->info('No orphan media records found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Found {$count} orphan record(s). Run without --dry-run to delete them.");

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($orphans as $mediaFile) {
            $mediaFile->forceDelete();
            $deleted++;
        }

        $this->info("Found {$count} orphan record(s), deleted {$deleted}.");

        return self::SUCCESS;
    }

    private function findOrphans(): Collection
    {
        return MediaFile::query()
            ->withTrashed()
            ->get()
            ->filter(function (MediaFile $file): bool {
                $disk = $file->disk ?? 'media';

                return ! Storage::disk($disk)->exists($file->url);
            });
    }
}
