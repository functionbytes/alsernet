<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

class BulkActionMacroRequest extends BulkActionRequest
{
    protected function entityLabel(): string
    {
        return 'una macro';
    }
}
