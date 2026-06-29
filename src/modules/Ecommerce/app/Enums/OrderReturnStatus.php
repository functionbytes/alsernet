<?php

namespace Modules\Ecommerce\Enums;

enum OrderReturnStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';
}
