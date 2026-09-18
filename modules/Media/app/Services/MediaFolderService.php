<?php

namespace Modules\Media\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Media\Events\MediaFolderCreated;
use Modules\Media\Events\MediaFolderDeleted;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;

class MediaFolderService
{
    public function __construct(private readonly MediaFileService $fileService) {}

    public function create(string $name, ?int $parentId, string $disk, int $userId, ?string $color = null): MediaFolder
    {
        $folder = MediaFolder::create([
            'name' => MediaFolder::createName($name, $parentId),
            'slug' => MediaFolder::createSlug($name, $parentId),
            'parent_id' => $parentId,
            'user_id' => $userId,
            'disk' => $disk,
            'color' => $color,
        ]);

        MediaFolderCreated::dispatch($folder);

        return $folder;
    }

    public function rename(MediaFolder $folder, string $name): MediaFolder
    {
        $folder->update(['name' => $name]);

        return $folder;
    }

    public function move(MediaFolder $folder, ?int $parentId): MediaFolder
    {
        if ($this->wouldCreateCycle($folder, $parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => ['Mover esta carpeta crearía un ciclo en la jerarquía.'],
            ]);
        }

        $folder->update(['parent_id' => $parentId]);

        return $folder;
    }

    public function delete(MediaFolder $folder): void
    {
        MediaFolderDeleted::dispatch($folder);

        $folder->delete();
    }

    public function restore(MediaFolder $folder): void
    {
        $folder->restore();
    }

    /**
     * Permanently delete a folder and all its descendants (subfolders + files).
     */
    public function forceDelete(MediaFolder $folder): void
    {
        DB::transaction(function () use ($folder): void {
            $this->forceDeleteDescendants($folder);
            $folder->withoutEvents(fn () => $folder->forceDelete());
        });
    }

    private function forceDeleteDescendants(MediaFolder $folder): void
    {
        foreach ($folder->children()->withTrashed()->get() as $child) {
            $this->forceDeleteDescendants($child);
            $child->withoutEvents(fn () => $child->forceDelete());
        }

        $folder->files()->withTrashed()->each(
            fn (MediaFile $file) => $this->fileService->forceDelete($file)
        );
    }

    private function wouldCreateCycle(MediaFolder $folder, ?int $targetParentId): bool
    {
        if ($targetParentId === null) {
            return false;
        }

        if ($targetParentId === $folder->id) {
            return true;
        }

        $current = MediaFolder::find($targetParentId);
        $depth = 0;

        while ($current && $depth < 100) {
            if ($current->id === $folder->id) {
                return true;
            }

            $current = $current->parent_id ? MediaFolder::find($current->parent_id) : null;
            $depth++;
        }

        return false;
    }
}
