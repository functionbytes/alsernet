<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use Modules\Helpdesk\Models\ConversationItem;

class MessageDrop extends BaseDrop
{
    public function __construct(
        private readonly ConversationItem $item
    ) {}

    public function id(): int
    {
        return $this->item->id;
    }

    public function body(): string
    {
        return $this->item->body ?? '';
    }

    public function createdAt(): ?string
    {
        return $this->formatDate($this->item->created_at);
    }

    public function authorType(): string
    {
        return $this->item->author_type ?? '';
    }
}
