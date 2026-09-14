<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionAutomationRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una automatizacion';
    }
}
