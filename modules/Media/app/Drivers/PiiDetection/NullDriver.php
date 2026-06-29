<?php

namespace Modules\Media\Drivers\PiiDetection;

use Modules\Media\Contracts\PiiDetectionDriver;

class NullDriver implements PiiDetectionDriver
{
    public function detectPii(string $filePath): array
    {
        return ['has_pii' => false, 'types' => []];
    }
}
