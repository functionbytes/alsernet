<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketSlaPolicyRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una politica';
    }
}
