<?php

namespace Modules\Media\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Media\Models\MediaFile;

/**
 * Allows any Eloquent model to associate MediaFiles via a polymorphic pivot.
 *
 * Usage: add `use HasMediaFiles;` to your model.
 * Requires the `mediables` pivot table (see media module migration).
 */
trait HasMediaFiles
{
    public function mediaFiles(): MorphToMany
    {
        return $this->morphToMany(MediaFile::class, 'mediable', 'mediables')
            ->withPivot('collection', 'order')
            ->withTimestamps()
            ->orderBy('mediables.order');
    }

    public function attachMedia(int $mediaFileId, string $collection = 'default', int $order = 0): void
    {
        $this->mediaFiles()->syncWithoutDetaching([
            $mediaFileId => ['collection' => $collection, 'order' => $order],
        ]);
    }

    public function detachMedia(int $mediaFileId): void
    {
        $this->mediaFiles()->detach($mediaFileId);
    }

    public function mediaByCollection(string $collection): Collection
    {
        return $this->mediaFiles()->wherePivot('collection', $collection)->get();
    }
}
