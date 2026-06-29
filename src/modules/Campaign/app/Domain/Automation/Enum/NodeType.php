<?php

namespace Modules\Campaign\Domain\Automation\Enum;

enum NodeType: string
{
    case Trigger = 'trigger';
    case Send = 'send';
    case Wait = 'wait';
    case WaitUntil = 'wait_until';
    case Condition = 'condition';
    case Operation = 'operation';
    case Webhook = 'webhook';

    public static function insertable(): array
    {
        return [
            self::Send, self::Wait, self::WaitUntil,
            self::Condition, self::Operation, self::Webhook,
        ];
    }

    public function isCondition(): bool
    {
        return $this === self::Condition;
    }
}
