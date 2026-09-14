<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Modules\Helpdesk\Http\Requests\Managers\Settings\Concerns\AutoReplyMessageRequest;

/** Reglas/mensajes compartidos en AutoReplyMessageRequest. */
class StoreConversationFarewellRequest extends AutoReplyMessageRequest
{
    /** Ver nota en StoreOffHoursResponseRequest::$errorBag. */
    protected $errorBag = 'farewell';
}
