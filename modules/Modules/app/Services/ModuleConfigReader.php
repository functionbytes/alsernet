<?php

namespace Modules\Modules\Services;

use Nwidart\Modules\Module;

class ModuleConfigReader
{
    public static function read(Module $module): array
    {
        $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';

        if (! file_exists($configPath)) {
            return [];
        }

        return json_decode(file_get_contents($configPath), true) ?? [];
    }
}
