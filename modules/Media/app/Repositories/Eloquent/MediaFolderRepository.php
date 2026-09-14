<?php

namespace Modules\Media\Repositories\Eloquent;

use Modules\Media\Models\MediaFolder;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;

class MediaFolderRepository implements MediaFolderInterface
{
    public function __construct(protected MediaFolder $model) {}

    public function getBreadcrumbs(int|string|null $parentId, array $breadcrumbs = []): array
    {
        if (! $parentId) {
            return $breadcrumbs;
        }

        $folder = $this->model->withTrashed()->find($parentId);

        if (! $folder) {
            return $breadcrumbs;
        }

        $parent = $this->getBreadcrumbs($folder->parent_id, $breadcrumbs);

        return array_merge($parent, [
            ['id' => $folder->id, 'name' => $folder->name],
        ]);
    }

    public function deleteFolder(int|string|null $folderId, bool $force = false): void
    {
        $children = $this->model
            ->when($force, fn ($q) => $q->withTrashed())
            ->where('parent_id', $folderId)
            ->get();

        foreach ($children as $child) {
            $this->deleteFolder($child->id, $force);
        }

        $folder = $this->model->withTrashed()->find($folderId);

        if ($folder) {
            $force ? $folder->forceDelete() : $folder->delete();
        }
    }

    public function getAllChildFolders(int|string|null $parentId, array $child = []): array
    {
        if (! $parentId) {
            return $child;
        }

        $folders = $this->model->where('parent_id', $parentId)->get();

        foreach ($folders as $folder) {
            $child[$parentId][] = $folder;
            $child = $this->getAllChildFolders($folder->id, $child);
        }

        return $child;
    }

    public function getFullPath(int|string|null $folderId, ?string $path = ''): ?string
    {
        return MediaFolder::getFullPath($folderId, $path);
    }

    public function restoreFolder(int|string|null $folderId): void
    {
        $children = $this->model->withTrashed()->where('parent_id', $folderId)->get();

        foreach ($children as $child) {
            $this->restoreFolder($child->id);
        }

        $this->model->withTrashed()->where('id', $folderId)->restore();
    }

    public function emptyTrash(): bool
    {
        $this->model->onlyTrashed()->each(fn (MediaFolder $folder) => $folder->forceDelete());

        return true;
    }

    public function createSlug(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createSlug($name, $parentId);
    }

    public function createName(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createName($name, $parentId);
    }
}
