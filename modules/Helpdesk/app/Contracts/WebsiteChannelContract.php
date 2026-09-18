<?php

namespace Modules\Helpdesk\Contracts;

use Modules\Helpdesk\Models\Inbox;

interface WebsiteChannelContract
{
    public function getWebsiteToken(): string;

    public function getInbox(): ?Inbox;

    public function isActive(): bool;
}
