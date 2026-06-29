<?php

namespace Modules\Campaign\Library\Contracts;

interface HasTemplateInterface
{
    public function isStageExcluded(string $name): bool;
}
