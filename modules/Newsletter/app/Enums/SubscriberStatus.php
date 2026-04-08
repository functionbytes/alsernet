<?php

namespace Modules\Newsletter\Enums;

enum SubscriberStatus: int
{
    case Subscribed = 1;
    case Unsubscribed = 0;

    public function label(): string
    {
        return match ($this) {
            self::Subscribed => 'Suscrito',
            self::Unsubscribed => 'Desuscrito',
        };
    }
}
