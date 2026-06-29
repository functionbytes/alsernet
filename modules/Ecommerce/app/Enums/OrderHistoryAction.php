<?php

namespace Modules\Ecommerce\Enums;

enum OrderHistoryAction: string
{
    case CREATE = 'create';
    case CONFIRM = 'confirm';
    case PAYMENT = 'payment';
    case SHIPPING = 'shipping';
    case COMPLETE = 'complete';
    case CANCEL = 'cancel';
    case REFUND = 'refund';
}
