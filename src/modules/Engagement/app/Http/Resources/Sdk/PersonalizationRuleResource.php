<?php

namespace Modules\Engagement\Http\Resources\Sdk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalizationRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'selector' => $this->selector,
            'conditions' => $this->conditions,
            'mutation' => $this->mutation,
        ];
    }
}
