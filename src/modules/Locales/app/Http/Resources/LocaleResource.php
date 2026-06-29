<?php

namespace Modules\Locales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'nativeName' => $this->native_name,
            'flag' => $this->flag,
            'rtl' => $this->rtl,
            'isDefault' => $this->is_default,
            'isActive' => $this->is_active,
            'order' => $this->order,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
