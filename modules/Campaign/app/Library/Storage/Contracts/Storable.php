<?php

namespace Modules\Campaign\Library\Storage\Contracts;

interface Storable
{
    public function toZip(): string;

    public function getArchivePath(): string;
}
