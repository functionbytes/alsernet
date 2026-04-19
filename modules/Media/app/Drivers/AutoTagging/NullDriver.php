<?php

namespace Modules\Media\Drivers\AutoTagging;

use Modules\Media\Contracts\AutoTaggingDriver;

class NullDriver implements AutoTaggingDriver
{
    public function detectTags(string $imagePath): array
    {
        return [];
    }
}
