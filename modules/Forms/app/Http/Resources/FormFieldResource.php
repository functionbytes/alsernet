<?php

namespace Modules\Forms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'placeholder' => $this->placeholder,
            'default_value' => $this->default_value,
            'help_text' => $this->help_text,
            'is_required' => (bool) $this->is_required,
            'is_visible' => (bool) $this->is_visible,
            'width' => $this->width,
            'sort_order' => (int) $this->sort_order,
            'step_number' => (int) $this->step_number,
            'options' => $this->options,
            'validation_rules' => $this->validation_rules,
        ];
    }
}
