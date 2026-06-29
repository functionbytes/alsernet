<?php

namespace Modules\Ecommerce\Enums;

enum ProductStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case PENDING = 'pending';
}
