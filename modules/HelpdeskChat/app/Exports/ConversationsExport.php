<?php

namespace Modules\HelpdeskChat\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConversationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $accountId;

    protected $filters;

    public function __construct($accountId, array $filters = [])
    {
        $this->accountId = $accountId;
        $this->filters = $filters;
    }

    /**
     * Query for conversation to export.
     */
    public function query()
    {
        $query = Conversation::query()
            ->where('account_id', $this->accountId)
            ->with(['contact', 'inbox', 'assignee', 'team']);

        // Apply filters
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['inbox_id'])) {
            $query->where('inbox_id', $this->filters['inbox_id']);
        }

        if (! empty($this->filters['assignee_id'])) {
            $query->where('assignee_id', $this->filters['assignee_id']);
        }

        if (! empty($this->filters['team_id'])) {
            $query->where('team_id', $this->filters['team_id']);
        }

        if (! empty($this->filters['created_after'])) {
            $query->where('created_at', '>=', $this->filters['created_after']);
        }

        if (! empty($this->filters['created_before'])) {
            $query->where('created_at', '<=', $this->filters['created_before']);
        }

        return $query->latest('created_at');
    }

    /**
     * Define spreadsheet headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Email Cliente',
            'Teléfono Cliente',
            'Canal',
            'Estado',
            'Prioridad',
            'Asignado a',
            'Equipo',
            'Etiquetas',
            'Primera Respuesta',
            'Fecha Resolución',
            'Última Actividad',
            'Fecha Creación',
        ];
    }

    /**
     * Map conversation data to spreadsheet row.
     */
    public function map($conversation): array
    {
        return [
            $conversation->id,
            $conversation->contact->name ?? '',
            $conversation->contact->email ?? '',
            $conversation->contact->phone_number ?? '',
            $conversation->inbox->name ?? '',
            ucfirst($conversation->status),
            $conversation->priority ? ucfirst($conversation->priority) : '',
            $conversation->assignee->name ?? '',
            $conversation->team->name ?? '',
            $conversation->cached_label_list ?? '',
            $conversation->first_response_at?->format('Y-m-d H:i:s') ?? '',
            $conversation->resolved_at?->format('Y-m-d H:i:s') ?? '',
            $conversation->last_activity_at?->format('Y-m-d H:i:s') ?? '',
            $conversation->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply styles to spreadsheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
