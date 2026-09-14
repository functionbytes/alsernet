<?php

namespace Modules\Media\Contracts;

interface AutoTaggingDriver
{
    /**
     * Detect tags for the given image.
     *
     * @return array<string>
     */
    public function detectTags(string $imagePath): array;
}
