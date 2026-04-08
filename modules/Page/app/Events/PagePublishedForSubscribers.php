<?php

namespace Modules\Page\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Page\Models\Page;

class PagePublishedForSubscribers
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Page $page
    ) {}
}
