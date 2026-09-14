<?php

namespace Modules\Mailer\Enums;

enum EndpointLogStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
