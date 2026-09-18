<?php

namespace Modules\Media\Contracts;

interface SmartCropDriver
{
    /**
     * Detect faces and return bounding boxes.
     *
     * @return array<array{x: int, y: int, width: int, height: int}>
     */
    public function detectFaces(string $imagePath): array;
}
