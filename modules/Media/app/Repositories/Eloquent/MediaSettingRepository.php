<?php

namespace Modules\Media\Repositories\Eloquent;

use Modules\Media\Models\MediaSetting;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;

class MediaSettingRepository implements MediaSettingInterface
{
    public function __construct(protected MediaSetting $model) {}

    public function getFavorites(int $userId): array
    {
        $setting = $this->model
            ->where('key', 'favorites')
            ->where('user_id', $userId)
            ->first();

        return $setting?->value ?? [];
    }

    public function addFavorites(int $userId, array $items): void
    {
        $setting = $this->model->firstOrCreate(
            ['key' => 'favorites', 'user_id' => $userId],
            ['value' => []]
        );

        $setting->value = array_merge($setting->value ?? [], $items);
        $setting->save();
    }

    public function removeFavorites(int $userId, array $items): void
    {
        $setting = $this->model
            ->where('key', 'favorites')
            ->where('user_id', $userId)
            ->first();

        if (! $setting || empty($setting->value)) {
            return;
        }

        $value = array_filter($setting->value, function ($existing) use ($items) {
            foreach ($items as $toRemove) {
                if ($existing['id'] == $toRemove['id'] && $existing['is_folder'] == $toRemove['is_folder']) {
                    return false;
                }
            }

            return true;
        });

        $setting->value = array_values($value);
        $setting->save();
    }
}
