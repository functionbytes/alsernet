<?php

namespace Modules\Media\Repositories\Interfaces;

interface MediaSettingInterface
{
    public function getFavorites(int $userId): array;

    public function addFavorites(int $userId, array $items): void;

    public function removeFavorites(int $userId, array $items): void;
}
