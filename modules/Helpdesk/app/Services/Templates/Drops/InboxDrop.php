<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use Modules\Helpdesk\Models\Inbox;

class InboxDrop extends BaseDrop
{
    public function __construct(
        private readonly Inbox $inbox
    ) {}

    public function id(): int
    {
        return $this->inbox->id;
    }

    public function name(): string
    {
        return $this->inbox->name ?? '';
    }

    public function channelType(): string
    {
        return $this->inbox->channel_type ?? '';
    }
}
