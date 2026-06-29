<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Ecommerce\Models\Invoice;

class InvoiceService
{
    public function getInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->with('reference')
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);
    }
}
