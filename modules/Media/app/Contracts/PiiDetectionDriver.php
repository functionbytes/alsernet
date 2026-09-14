<?php

namespace Modules\Media\Contracts;

interface PiiDetectionDriver
{
    /**
     * Detect personally identifiable information in the given file.
     *
     * @return array{has_pii: bool, types: array<string>}
     */
    public function detectPii(string $filePath): array;
}
