<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketViewRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una vista';
    }
}
