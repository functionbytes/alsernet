<?php

namespace Modules\HelpdeskChat\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContactsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
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
     * Query for contacts to export.
     */
    public function query()
    {
        $query = Contact::query()
            ->where('account_id', $this->accountId)
            ->with(['contactInboxes.inbox']);

        // Apply filters if provided
        if (! empty($this->filters['labels'])) {
            $query->whereIn('id', function ($q) {
                $q->select('contact_id')
                    ->from('contact_labels')
                    ->whereIn('label_id', $this->filters['labels']);
            });
        }

        if (! empty($this->filters['created_after'])) {
            $query->where('created_at', '>=', $this->filters['created_after']);
        }

        if (! empty($this->filters['created_before'])) {
            $query->where('created_at', '<=', $this->filters['created_before']);
        }

        return $query;
    }

    /**
     * Define spreadsheet headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Teléfono',
            'Ciudad',
            'País',
            'Etiquetas',
            'Canales',
            'Última Actividad',
            'Fecha Creación',
        ];
    }

    /**
     * Map contact data to spreadsheet row.
     */
    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->name,
            $contact->email,
            $contact->phone_number,
            $contact->city,
            $contact->country,
            $contact->cached_label_list ?? '',
            $contact->contactInboxes->pluck('inbox.name')->implode(', '),
            $contact->last_activity_at?->format('Y-m-d H:i:s') ?? '',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply styles to spreadsheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}
