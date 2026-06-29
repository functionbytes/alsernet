<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Ecommerce\Models\Customer;

class CustomerService
{
    public function getCustomers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function createCustomer(array $data): Customer
    {
        return Customer::query()->create($data);
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer;
    }
}
