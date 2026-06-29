<?php

namespace Modules\Attention\Imports;

use App\Imports\BaseImport;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;

class AttentionImport extends BaseImport
{
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:500',
            'description' => 'required|string|max:10000',
            'customer_firstname' => 'nullable|string|max:100',
            'customer_lastname' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email|max:255',
            'customer_cellphone' => 'nullable|string|max:20',
            'customer_dni' => 'nullable|string|max:30',
            'status' => 'nullable|string|in:received,in_process,resolved,closed',
        ];
    }

    protected function processRow(array $row): void
    {
        $status = isset($row['status']) && $row['status']
            ? AttentionStatus::from($row['status'])
            : AttentionStatus::RECEIVED;

        Attention::create([
            'radicado' => Attention::generateRadicado(),
            'subject' => $row['subject'],
            'description' => $row['description'],
            'customer_firstname' => $row['customer_firstname'] ?? null,
            'customer_lastname' => $row['customer_lastname'] ?? null,
            'customer_email' => $row['customer_email'] ?? null,
            'customer_cellphone' => $row['customer_cellphone'] ?? null,
            'customer_dni' => $row['customer_dni'] ?? null,
            'status' => $status,
            'is_anonymous' => empty($row['customer_firstname']) && empty($row['customer_lastname']),
        ]);
    }
}
