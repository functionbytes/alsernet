<?php

namespace Modules\Ecommerce\Enums;

enum OrderReturnReason: string
{
    case DEFECTIVE = 'defective';
    case WRONG_ITEM = 'wrong_item';
    case NOT_AS_DESCRIBED = 'not_as_described';
    case OTHER = 'other';
}
