<?php

namespace Modules\Notification\Enums;

/**
 * Resultado del envío de una notificación push a un token FCM.
 */
enum PushResult
{
    case Success;
    case Failed;
    case InvalidToken;
}
