<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketEmailBlacklistRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'un elemento';
    }
}
