<?php

namespace Modules\Page\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Page\Models\Page;

class PagePublished
{
    use Dispatchable;

    public function __construct(
        public readonly Page $page
    ) {}
}
