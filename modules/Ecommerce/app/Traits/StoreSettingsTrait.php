<?php

namespace Modules\Ecommerce\Traits;

trait StoreSettingsTrait
{
    public function saveSetting(string $key, mixed $value): void
    {
        setting([$key => $value]);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return setting($key, $default);
    }
}
