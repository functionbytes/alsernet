<?php

namespace Modules\Forms\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;

class FormSubmissionsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle
{
    /** @var array<int, FormField> */
    private array $fields;

    public function __construct(
        protected Form $form,
        protected array $filters = []
    ) {
        $this->fields = $form->fields()->visible()->ordered()->get()->all();
    }

    public function query(): Builder
    {
        $query = FormSubmission::query()
            ->where('form_id', $this->form->id)
            ->with('values');

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['is_read']) && $this->filters['is_read'] !== '') {
            $query->where('is_read', (bool) $this->filters['is_read']);
        }

        if (isset($this->filters['is_spam']) && $this->filters['is_spam'] !== '') {
            $query->where('is_spam', (bool) $this->filters['is_spam']);
        }

        if (isset($this->filters['is_starred']) && $this->filters['is_starred'] !== '') {
            $query->where('is_starred', (bool) $this->filters['is_starred']);
        }

        if (! empty($this->filters['assigned_to'])) {
            $query->where('assigned_to', $this->filters['assigned_to']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereExists(function ($sub) use ($search) {
                $sub->selectRaw('1')
                    ->from('form_submission_values')
                    ->whereColumn('form_submission_values.submission_id', 'form_submissions.id')
                    ->where('value', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }

    public function chunkSize(): int
    {
        return 200;
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        $base = ['ID', 'Fecha de envío', 'Estado', 'Leído', 'IP', 'País', 'UTM Source'];

        $fieldLabels = array_map(
            fn ($field) => $field->label ?: $field->name,
            array_filter($this->fields, fn ($field) => ! $field->isLayoutField())
        );

        return array_merge($base, array_values($fieldLabels));
    }

    /**
     * @param  FormSubmission  $submission
     * @return array<mixed>
     */
    public function map($submission): array
    {
        $base = [
            $submission->id,
            $submission->created_at?->format('d/m/Y H:i'),
            $submission->status,
            $submission->is_read ? 'Sí' : 'No',
            $submission->ip_address,
            $submission->country,
            $submission->utm_source,
        ];

        $values = [];
        foreach ($this->fields as $field) {
            if ($field->isLayoutField()) {
                continue;
            }
            $values[] = $submission->getValueFor($field->key) ?? '';
        }

        return array_merge($base, $values);
    }

    public function title(): string
    {
        return Str::limit($this->form->name, 31);
    }
}
