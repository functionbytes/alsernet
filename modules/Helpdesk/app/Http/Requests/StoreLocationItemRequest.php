<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'La latitud es obligatoria.',
            'lat.between' => 'La latitud debe estar entre -90 y 90.',
            'lng.required' => 'La longitud es obligatoria.',
            'lng.between' => 'La longitud debe estar entre -180 y 180.',
        ];
    }

    public function attributes(): array
    {
        return [
            'lat' => 'latitud',
            'lng' => 'longitud',
            'address' => 'dirección',
        ];
    }
}
