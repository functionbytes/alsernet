<?php

namespace Modules\Supplier\Services\Extraction;

use InvalidArgumentException;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Services\Extraction\Contracts\SourceDriverInterface;
use Modules\Supplier\Services\Extraction\Drivers\ApiDriver;
use Modules\Supplier\Services\Extraction\Drivers\FtpDriver;
use Modules\Supplier\Services\Extraction\Drivers\UploadDriver;
use Modules\Supplier\Services\Extraction\Drivers\WebScrapingDriver;

class SourceExtractionDriverFactory
{
    public function make(Source $source): SourceDriverInterface
    {
        return match ($source->source_type) {
            Source::SOURCE_TYPE_WEBSITE => app(WebScrapingDriver::class),
            Source::SOURCE_TYPE_API => app(ApiDriver::class),
            Source::SOURCE_TYPE_FTP, 'sftp' => app(FtpDriver::class),
            Source::SOURCE_TYPE_FILE, 'upload' => app(UploadDriver::class),
            default => throw new InvalidArgumentException("Unsupported source type: {$source->source_type}"),
        };
    }
}
