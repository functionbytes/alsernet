<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Customer;

class CustomerMerged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $base,
        public readonly Customer $mergee,
    ) {}
}
