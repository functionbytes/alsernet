<?php

namespace Modules\HelpdeskEmailLog\Contracts;

/**
 * Implement this interface in any Mailable to attach entity context
 * (module, entity_type, entity_id) to the email log entry automatically.
 */
interface TracksEmailLog
{
    public function getEmailLogModule(): string;

    public function getEmailLogEntityType(): string;

    public function getEmailLogEntityId(): int|string;
}
