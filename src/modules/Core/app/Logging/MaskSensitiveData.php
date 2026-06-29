<?php

namespace Modules\Core\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;

/**
 * Laravel log channel tap that pushes SensitiveDataMasker
 * onto every handler registered on the logger instance.
 */
class MaskSensitiveData
{
    public function __invoke(IlluminateLogger|Logger $logger): void
    {
        $monolog = $logger instanceof IlluminateLogger ? $logger->getLogger() : $logger;
        foreach ($monolog->getHandlers() as $handler) {
            $handler->pushProcessor(new SensitiveDataMasker);
        }
    }
}
