<?php

namespace Modules\Media\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface MediaFileInterface
{
    public function getFilesByFolderId(
        int|string|null $folderId,
        array $params = [],
        bool $withFolders = true,
        array $folderParams = []
    ): Collection;

    public function getTrashed(int|string|null $folderId, array $params = []): Collection;

    public function emptyTrash(): bool;

    public function createName(string $name, int|string|null $folder): string;
}
