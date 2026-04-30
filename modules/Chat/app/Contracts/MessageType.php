<?php

namespace Modules\Chat\Contracts;

enum MessageType: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
    case ACTIVITY = 'activity';
    case TEMPLATE = 'template';
}
