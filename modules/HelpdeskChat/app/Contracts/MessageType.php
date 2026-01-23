<?php

namespace Modules\HelpdeskChat\Contracts;

enum MessageType: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
    case ACTIVITY = 'activity';
    case TEMPLATE = 'template';
}
