<?php

namespace Modules\Media\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Repositories\Interfaces\MediaFileInterface;
use Modules\Media\Services\MediaFileService;

class MediaFileRepository implements MediaFileInterface
{
    public function __construct(
        protected MediaFile $model,
        protected MediaFileService $fileService
    ) {}

    public function getFilesByFolderId(
        int|string|null $folderId,
        array $params = [],
        bool $withFolders = true,
        array $folderParams = []
    ): Collection {
        $disk = $params['disk'] ?? null;
        $query = $this->model->newQuery();

        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($disk) {
            $query->where('disk', $disk);
        }

        if (! empty($params['search'])) {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if (! empty($params['filter']) && $params['filter'] !== 'everything') {
            $mimeTypes = config("media.mime_types.{$params['filter']}", []);
            if ($mimeTypes) {
                $query->whereIn('mime_type', $mimeTypes);
            }
        }

        $files = $query->orderBy('name')->get()->map(fn (MediaFile $file) => (object) array_merge(
            $file->toArray(),
            ['is_folder' => false, 'human_size' => $file->human_size]
        ));

        if (! $withFolders) {
            return Collection::make($files);
        }

        $folderQuery = MediaFolder::query();

        if ($folderId) {
            $folderQuery->where('parent_id', $folderId);
        } else {
            $folderQuery->whereNull('parent_id');
        }

        if ($disk) {
            $folderQuery->where('disk', $disk);
        }

        if (! empty($folderParams['search'])) {
            $folderQuery->where('name', 'LIKE', '%'.$folderParams['search'].'%');
        }

        $folders = $folderQuery->orderBy('name')->get()->map(fn (MediaFolder $folder) => (object) array_merge(
            $folder->toArray(),
            ['is_folder' => true]
        ));

        return Collection::make($folders->concat($files));
    }

    public function getTrashed(int|string|null $folderId, array $params = []): Collection
    {
        $fileQuery = $this->model->onlyTrashed();
        $folderQuery = MediaFolder::onlyTrashed();

        $files = $fileQuery->orderBy('name')->get()->map(
            fn (MediaFile $file) => (object) array_merge($file->toArray(), ['is_folder' => false, 'human_size' => $file->human_size])
        );

        $folders = $folderQuery->orderBy('name')->get()->map(
            fn (MediaFolder $folder) => (object) array_merge($folder->toArray(), ['is_folder' => true])
        );

        return Collection::make($folders->concat($files));
    }

    public function emptyTrash(): bool
    {
        $this->model->onlyTrashed()->each(
            fn (MediaFile $file) => $this->fileService->forceDelete($file)
        );

        return true;
    }

    public function createName(string $name, int|string|null $folder): string
    {
        return MediaFile::createName($name, $folder);
    }
}
