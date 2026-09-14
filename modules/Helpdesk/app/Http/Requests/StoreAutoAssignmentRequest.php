<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Services\AutoAssignmentService;

class StoreAutoAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.manage');
    }

    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'strategy' => ['required', 'string', 'in:'.implode(',', AutoAssignmentService::STRATEGIES)],
            'retry' => ['required', 'string', 'in:'.implode(',', AutoAssignmentService::RETRIES)],
            'fallback' => ['required', 'string', 'in:'.implode(',', AutoAssignmentService::FALLBACKS)],
        ];
    }

    public function messages(): array
    {
        return [
            'strategy.required' => 'La estrategia es obligatoria.',
            'strategy.in' => 'La estrategia seleccionada no es válida.',
            'retry.in' => 'El valor de reintento no es válido.',
            'fallback.in' => 'El valor de fallback no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'strategy' => 'estrategia',
            'retry' => 'reintento',
            'fallback' => 'fallback',
        ];
    }
}
