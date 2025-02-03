<?php

namespace App\Library\Storage\Contracts;

interface StorageService
{
    public function store(Storable $object);
}
