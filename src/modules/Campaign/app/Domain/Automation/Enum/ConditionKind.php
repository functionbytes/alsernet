<?php

namespace Modules\Campaign\Domain\Automation\Enum;

enum ConditionKind: string
{
    case Open = 'open';
    case Click = 'click';
}
