<?php

namespace Modules\Media\Repositories\Eloquent;

use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaSetting;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;

class MediaSettingRepository implements MediaSettingInterface
{
    public function __construct(protected MediaSetting $model) {}

    public function getFavorites(int $userId): array
    {
        return MediaFavorite::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (MediaFavorite $f) => [
                'id' => $f->favoritable_id,
                'is_folder' => $f->favoritable_type === MediaFolder::class,
            ])
            ->all();
    }

    public function addFavorites(int $userId, array $items): void
    {
        foreach ($items as $item) {
            MediaFavorite::firstOrCreate([
                'user_id' => $userId,
                'favoritable_id' => $item['id'],
                'favoritable_type' => $item['is_folder'] ? MediaFolder::class : MediaFile::class,
            ]);
        }
    }

    public function removeFavorites(int $userId, array $items): void
    {
        foreach ($items as $item) {
            MediaFavorite::query()
                ->where('user_id', $userId)
                ->where('favoritable_id', $item['id'])
                ->where('favoritable_type', $item['is_folder'] ? MediaFolder::class : MediaFile::class)
                ->delete();
        }
    }
}
