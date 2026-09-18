<?php

namespace Modules\Core\Logging;

use Monolog\Logger;

/**
 * Laravel log channel tap that pushes SensitiveDataMasker
 * onto every handler registered on the logger instance.
 */
class MaskSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(new SensitiveDataMasker);
        }
    }
}
