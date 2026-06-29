<?php

namespace Modules\Ecommerce\Enums;

enum DeletionRequestStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
