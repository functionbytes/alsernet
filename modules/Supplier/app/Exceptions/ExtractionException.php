<?php

namespace Modules\Supplier\Exceptions;

use Exception;

class ExtractionException extends Exception
{
    public static function batchFailed(string $batchId, string $reason): self
    {
        return new self("Extraction batch [{$batchId}] failed: {$reason}");
    }

    public static function sourceUnavailable(string $sourceId): self
    {
        return new self("Source [{$sourceId}] is unavailable for extraction.");
    }

    public static function mappingError(string $field, string $reason): self
    {
        return new self("Mapping error for field [{$field}]: {$reason}");
    }

    public static function mappingNotFound(int|string $sourceId): self
    {
        return new self("No extraction mapping found for source [{$sourceId}].");
    }
}
