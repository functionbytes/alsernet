<?php

namespace Modules\Campaign\Library;

// use Swift_Message;

/**
 * Note: This class is deprecated as Laravel 12 uses Symfony Mailer instead of SwiftMailer
 * Keeping for backwards compatibility but commented out to prevent IDE helper generation errors
 */
class ExtendedSwiftMessage // extends Swift_Message
{
    public $extAttachments = [];
}
