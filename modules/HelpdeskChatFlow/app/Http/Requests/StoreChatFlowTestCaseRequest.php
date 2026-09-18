<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatFlowTestCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chatflow.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.input' => ['required', 'string'],
            'steps.*.expect_contains' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del escenario es obligatorio.',
            'steps.required' => 'Agrega al menos un paso.',
            'steps.min' => 'Agrega al menos un paso.',
            'steps.*.input.required' => 'Cada paso necesita un mensaje del cliente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'steps' => 'pasos',
        ];
    }
}
