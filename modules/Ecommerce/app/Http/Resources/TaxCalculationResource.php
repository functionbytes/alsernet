<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'taxAmount' => $this->resource['tax_amount'] ?? null,
            'taxRate' => $this->resource['tax_rate'] ?? null,
            'subtotal' => $this->resource['subtotal'] ?? null,
            'totalWithTax' => $this->resource['total_with_tax'] ?? null,
        ];
    }
}
