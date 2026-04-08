<?php

namespace Modules\Page\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageApproval;

class PageApprovalRejected
{
    use Dispatchable;

    public function __construct(
        public readonly Page $page,
        public readonly PageApproval $approval
    ) {}
}
