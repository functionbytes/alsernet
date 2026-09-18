<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketGroupRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'un grupo';
    }
}
