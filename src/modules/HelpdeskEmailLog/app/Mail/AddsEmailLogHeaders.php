<?php

namespace Modules\HelpdeskEmailLog\Mail;

use Illuminate\Mail\Mailables\Headers;
use Modules\HelpdeskEmailLog\Contracts\TracksEmailLog;

/**
 * Add this trait to any Mailable to attach tracking context to its email log
 * entry. When the Mailable also implements {@see TracksEmailLog}, the module,
 * entity type and entity id are exposed too.
 *
 * The headers are read by the LogEmailQueued listener and then stripped from
 * the outgoing message, so they never reach the recipient.
 *
 * If the Mailable already defines headers(), call parent or merge manually.
 */
trait AddsEmailLogHeaders
{
    public function headers(): Headers
    {
        $text = ['X-Mailable-Class' => static::class];

        if ($this instanceof TracksEmailLog) {
            $text['X-Email-Module'] = $this->getEmailLogModule();
            $text['X-Entity-Type'] = $this->getEmailLogEntityType();
            $text['X-Entity-Id'] = (string) $this->getEmailLogEntityId();
        }

        return new Headers(text: $text);
    }
}
