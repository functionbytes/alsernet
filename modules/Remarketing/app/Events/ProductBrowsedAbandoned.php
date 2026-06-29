<?php

namespace Modules\Remarketing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductBrowsedAbandoned
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $payload) {}
}
