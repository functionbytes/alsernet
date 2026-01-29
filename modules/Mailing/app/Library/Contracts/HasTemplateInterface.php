<?php

namespace Modules\Mailing\Library\Contracts;

interface HasTemplateInterface
{
    public function isStageExcluded(string $name): bool;
}
