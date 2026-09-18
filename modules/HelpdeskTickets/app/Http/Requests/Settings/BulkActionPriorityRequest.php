<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionPriorityRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una prioridad';
    }
}
