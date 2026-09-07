<?php

namespace Modules\Forms\Imports;

use App\Imports\BaseImport;
use Modules\Forms\Models\FormSubmission;

class FormSubmissionImport extends BaseImport
{
    public function rules(): array
    {
        return [
            'form_id' => 'required|integer|exists:forms,id',
            'status' => 'nullable|string|max:50',
            'ip_address' => 'nullable|ip',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ];
    }

    protected function processRow(array $row): void
    {
        FormSubmission::create([
            'form_id' => (int) $row['form_id'],
            'status' => $row['status'] ?? 'new',
            'ip_address' => $row['ip_address'] ?? null,
            'country' => $row['country'] ?? null,
            'city' => $row['city'] ?? null,
            'utm_source' => $row['utm_source'] ?? null,
            'utm_medium' => $row['utm_medium'] ?? null,
            'utm_campaign' => $row['utm_campaign'] ?? null,
            'is_read' => false,
            'is_spam' => false,
            'is_starred' => false,
        ]);
    }
}
