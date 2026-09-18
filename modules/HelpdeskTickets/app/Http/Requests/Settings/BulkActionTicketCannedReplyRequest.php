<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionTicketCannedReplyRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una respuesta';
    }
}
