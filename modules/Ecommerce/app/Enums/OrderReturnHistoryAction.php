<?php

namespace Modules\Ecommerce\Enums;

enum OrderReturnHistoryAction: string
{
    case CREATE = 'create';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case COMPLETE = 'complete';
}
