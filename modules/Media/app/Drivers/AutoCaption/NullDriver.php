<?php

namespace Modules\Media\Drivers\AutoCaption;

use Modules\Media\Contracts\AutoCaptionDriver;

class NullDriver implements AutoCaptionDriver
{
    public function generateCaption(string $imagePath): ?string
    {
        return null;
    }
}
