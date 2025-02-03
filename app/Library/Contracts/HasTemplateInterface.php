<?php

namespace App\Library\Contracts;

interface HasTemplateInterface
{
    public function isStageExcluded(string $name): bool;
}
