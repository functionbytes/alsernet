<?php

namespace Modules\Media\Repositories\Interfaces;

interface MediaFolderInterface
{
    public function getBreadcrumbs(int|string|null $parentId, array $breadcrumbs = []): array;

    public function deleteFolder(int|string|null $folderId, bool $force = false): void;

    public function getAllChildFolders(int|string|null $parentId, array $child = []): array;

    public function getFullPath(int|string|null $folderId, ?string $path = ''): ?string;

    public function restoreFolder(int|string|null $folderId): void;

    public function emptyTrash(): bool;

    public function createSlug(string $name, int|string|null $parentId): string;

    public function createName(string $name, int|string|null $parentId): string;
}
