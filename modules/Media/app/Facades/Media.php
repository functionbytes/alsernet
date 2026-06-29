<?php

namespace Modules\Media\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Modules\Media\Models\MediaFile;
use Modules\Media\Services\MediaFileService;

/**
 * @method static array upload(UploadedFile $file, ?int $folderId, string $disk, int $userId)
 * @method static MediaFile copy(MediaFile $file, int $userId)
 * @method static MediaFile move(MediaFile $file, ?int $folderId)
 * @method static void delete(MediaFile $file)
 * @method static Collection findSimilar(MediaFile $file, int $maxDistance = 10, int $limit = 20)
 * @method static int getUserUsage(int $userId)
 * @method static bool hasQuotaFor(int $userId, int $additionalBytes)
 * @method static MediaFile rename(MediaFile $file, string $name)
 * @method static void restore(MediaFile $file)
 * @method static bool toggleFavorite(MediaFile $file, int $userId)
 *
 * @see MediaFileService
 */
class Media extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MediaFileService::class;
    }
}
