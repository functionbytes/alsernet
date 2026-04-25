<?php

namespace Modules\Ecommerce\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
