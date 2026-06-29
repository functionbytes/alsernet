<?php

namespace Modules\Helpdesk\Services\Exports;

use Generator;
use Modules\Helpdesk\Models\Customer;

class CustomersExporter
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
            'Nombre',
            'Email',
            'Teléfono',
            'Empresa',
            'Conversaciones',
            'País',
            'Creado en',
        ];
    }

    public function rows(): Generator
    {
        $query = Customer::query()
            ->when($this->filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($this->filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->orderBy('id');

        foreach ($query->lazy(200) as $customer) {
            yield [
                $customer->id,
                $customer->name ?? '',
                $customer->email ?? '',
                $customer->phone ?? '',
                '',  // company — not a direct column; can be extended later
                $customer->total_conversations ?? 0,
                $customer->country ?? '',
                $customer->created_at?->toDateTimeString() ?? '',
            ];
        }
    }
}
