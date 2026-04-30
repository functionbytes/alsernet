<?php

namespace Modules\Chat\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Chat\Models\Conversations\Conversation;

class ConversationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $conversations;

    public function __construct($conversations = null)
    {
        $this->conversations = $conversations;
    }

    public function collection()
    {
        if ($this->conversations) {
            return $this->conversations;
        }

        return Conversation::with(['customer', 'assignee', 'team'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Subject',
            'Customers',
            'Status',
            'Priority',
            'Assignee',
            'Team',
            'Created At',
            'Updated At',
        ];
    }

    public function map($conversation): array
    {
        return [
            $conversation->id,
            $conversation->subject ?? 'Sin asunto',
            optional($conversation->customer)->name ?? 'N/A',
            $conversation->status,
            $conversation->priority,
            optional($conversation->assignee)->name ?? 'Unassigned',
            optional($conversation->team)->name ?? 'N/A',
            $conversation->created_at->format('Y-m-d H:i:s'),
            $conversation->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
