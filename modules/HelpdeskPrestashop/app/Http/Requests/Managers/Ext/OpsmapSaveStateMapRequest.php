<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskPrestashop\Services\Ext\OpsmapStateMapService;

class OpsmapSaveStateMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.statemap.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            // map[<id de estado de PS>] = acción; los ids van como clave.
            'map' => ['nullable', 'array', 'max:500'],
            'map.*' => ['required', 'string', Rule::in(array_keys(OpsmapStateMapService::ACTIONS))],
            'names' => ['nullable', 'array', 'max:500'],
            'names.*' => ['nullable', 'string', 'max:255'],
            'create_note' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                foreach (array_keys((array) $this->input('map', [])) as $key) {
                    if (! ctype_digit((string) $key) || (int) $key <= 0) {
                        $validator->errors()->add('map', 'El mapeo contiene un estado de PrestaShop no válido.');

                        return;
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'map.*.in' => 'Una de las acciones elegidas no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'map' => 'mapeo de estados',
            'create_note' => 'crear nota',
        ];
    }
}
