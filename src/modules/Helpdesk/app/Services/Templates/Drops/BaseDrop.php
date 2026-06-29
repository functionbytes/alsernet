<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use Keepsuit\Liquid\Drop;

abstract class BaseDrop extends Drop
{
    protected function formatDate(?\DateTimeInterface $date): ?string
    {
        return $date?->toIso8601String();
    }
}
