<?php

namespace App\Library\Storage\Contracts;

interface Storable
{
    public function toZip(): string;
    public function getArchivePath(): string;
}
