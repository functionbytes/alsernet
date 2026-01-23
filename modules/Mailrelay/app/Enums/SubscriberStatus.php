<?php

namespace Modules\Mailrelay\Enums;

enum SubscriberStatus: string
{
    case ACTIVE = 'active';
    case UNSUBSCRIBED = 'unsubscribed';
    case BOUNCED = 'bounced';
    case PENDING = 'pending';
    case BANNED = 'banned';
}
