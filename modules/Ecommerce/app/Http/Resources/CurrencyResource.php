<?php

namespace Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'symbol' => $this->symbol,
            'is_prefix_symbol' => $this->is_prefix_symbol,
            'decimals' => $this->decimals,
            'is_default' => $this->is_default,
            'exchange_rate' => $this->exchange_rate,
            'created_at' => $this->created_at,
        ];
    }
}
