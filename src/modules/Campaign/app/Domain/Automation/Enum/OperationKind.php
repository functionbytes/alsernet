<?php

namespace Modules\Campaign\Domain\Automation\Enum;

enum OperationKind: string
{
    case Tag = 'tag';
    case RemoveTag = 'remove_tag';
    case Copy = 'copy';
    case Move = 'move';
    case Update = 'update';
}
