<?php

namespace Modules\Mailing\Library\Storage\Contracts;

interface StorageService
{
    public function store(Storable $object);
}
