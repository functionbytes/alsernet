<?php

namespace Modules\Media\Drivers\SmartCrop;

use Modules\Media\Contracts\SmartCropDriver;

class NullDriver implements SmartCropDriver
{
    public function detectFaces(string $imagePath): array
    {
        return [];
    }
}
