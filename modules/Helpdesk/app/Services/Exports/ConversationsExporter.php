<?php

namespace Modules\Helpdesk\Services\Exports;

use Generator;
use Modules\Helpdesk\Models\Conversation;

class ConversationsExporter
{
    public function __construct(
        private readonly array $filters = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'ID',
            'Asunto',
            'Cliente',
            'Email',
            'Canal',
            'Inbox',
            'Estado',
            'Prioridad',
            'Asignado a',
            'Mensajes',
            'Creada',
            'Resuelta',
        ];
    }

    public function rows(): Generator
    {
        $query = Conversation::query()
            ->with(['customer', 'inbox', 'status', 'assignee'])
            ->withCount('messages')
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->whereHas('status', fn ($s) => $s->where('key', $v)))
            ->when($this->filters['channel_type'] ?? null, fn ($q, $v) => $q->where('channel', $v))
            ->when($this->filters['inbox_id'] ?? null, fn ($q, $v) => $q->where('inbox_id', $v))
            ->orderBy('id');

        foreach ($query->lazy(200) as $conversation) {
            yield [
                $conversation->id,
                $conversation->subject ?? '',
                $conversation->customer?->name ?? '',
                $conversation->customer?->email ?? '',
                $conversation->channel ?? '',
                $conversation->inbox?->name ?? '',
                $conversation->status?->name ?? '',
                $conversation->priority ?? '',
                $conversation->assignee?->name ?? '',
                $conversation->messages_count ?? 0,
                $conversation->created_at?->toDateTimeString() ?? '',
                $conversation->closed_at?->toDateTimeString() ?? '',
            ];
        }
    }
}
