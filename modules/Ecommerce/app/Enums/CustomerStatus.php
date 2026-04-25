<?php

namespace Modules\Ecommerce\Enums;

enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
