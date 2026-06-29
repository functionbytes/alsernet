<?php

namespace Modules\Ecommerce\Enums;

enum GlobalOptionType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case SELECT = 'select';
    case DATE = 'date';
    case DATE_TIME = 'date_time';
}
