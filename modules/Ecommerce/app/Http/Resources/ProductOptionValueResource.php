<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'optionId' => $this->option_id,
            'value' => $this->option_value,
            'affectPrice' => $this->affect_price,
            'affectType' => $this->affect_type,
            'order' => $this->order,
        ];
    }
}
