<?php

namespace Modules\Helpdesk\Services\Templates\Drops;

use Modules\Helpdesk\Models\Conversation;

class ConversationDrop extends BaseDrop
{
    public function __construct(
        private readonly Conversation $conversation
    ) {}

    public function id(): int
    {
        return $this->conversation->id;
    }

    public function subject(): string
    {
        return $this->conversation->subject ?? '';
    }

    public function status(): string
    {
        return $this->conversation->status?->name ?? '';
    }

    public function priority(): string
    {
        return $this->conversation->priority ?? '';
    }

    public function channel(): string
    {
        return $this->conversation->channel ?? '';
    }

    public function createdAt(): ?string
    {
        return $this->formatDate($this->conversation->created_at);
    }

    public function assignee(): ?AgentDrop
    {
        $assignee = $this->conversation->assignee;

        return $assignee ? new AgentDrop($assignee) : null;
    }

    public function inbox(): ?InboxDrop
    {
        $inbox = $this->conversation->inbox;

        return $inbox ? new InboxDrop($inbox) : null;
    }
}
