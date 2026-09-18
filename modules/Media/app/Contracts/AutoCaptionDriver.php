<?php

namespace Modules\Media\Contracts;

interface AutoCaptionDriver
{
    /**
     * Generate a descriptive caption for the given image.
     */
    public function generateCaption(string $imagePath): ?string;
}
