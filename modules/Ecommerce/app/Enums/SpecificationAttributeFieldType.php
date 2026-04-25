<?php

namespace Modules\Ecommerce\Enums;

enum SpecificationAttributeFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case NUMBER = 'number';
}
