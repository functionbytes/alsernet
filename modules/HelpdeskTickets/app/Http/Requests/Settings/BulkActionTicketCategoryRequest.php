<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketCategoryRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una categoria';
    }
}
