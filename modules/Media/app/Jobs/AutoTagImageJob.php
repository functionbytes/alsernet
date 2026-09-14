<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaTag;
use Modules\Media\Services\AutoTaggingFactory;

class AutoTagImageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(private readonly int $mediaFileId)
    {
        $this->onQueue('media-heavy');
    }

    public function handle(): void
    {
        $file = MediaFile::query()->find($this->mediaFileId);

        if (! $file || $file->type !== 'image') {
            return;
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->url)) {
            return;
        }

        $tags = AutoTaggingFactory::make()->detectTags($disk->path($file->url));

        if (empty($tags)) {
            return;
        }

        $metadata = $file->metadata ?? [];
        $metadata['ai_tags'] = $tags;
        $file->update(['metadata' => $metadata]);

        foreach ($tags as $tagName) {
            $tag = MediaTag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName, 'color' => '#6c757d']
            );
            $file->tags()->syncWithoutDetaching([$tag->id]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('AutoTagImageJob failed', [
            'media_file_id' => $this->mediaFileId,
            'error' => $exception->getMessage(),
        ]);
    }
}
