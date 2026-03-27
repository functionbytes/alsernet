<?php

namespace Modules\Forms\Enums;

enum FormEmailStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
