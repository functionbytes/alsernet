<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketStatusRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'un estado';
    }
}
