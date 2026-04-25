<?php

namespace Modules\Ecommerce\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Ecommerce\Models\Customer;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            Customer::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? null,
                    'password' => bcrypt($row['password'] ?? uniqid()),
                    'status' => $row['status'] ?? 'active',
                ]
            );
        }
    }
}
