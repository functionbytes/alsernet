<?php

namespace Modules\Mailing\Library;

use Swift_Message;

class ExtendedSwiftMessage extends Swift_Message
{
    public $extAttachments = [];
}
