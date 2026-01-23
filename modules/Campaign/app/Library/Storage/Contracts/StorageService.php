<?php

namespace Modules\Campaign\Library\Storage\Contracts;

interface StorageService
{
    public function store(Storable $object);
}
