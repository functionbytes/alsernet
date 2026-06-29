<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.segments.create');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('conditions')) {
            $decoded = json_decode($this->input('conditions'), true);
            $this->merge([
                'conditions' => is_array($decoded) ? $decoded : [],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:static,dynamic'],
            'conditions' => ['required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'conditions' => 'condiciones',
        ];
    }
}
