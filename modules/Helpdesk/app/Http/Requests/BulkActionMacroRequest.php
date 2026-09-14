<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\Macro;

class BulkActionMacroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.macros.delete') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            // El `exists` no pasa por el modelo, asi que el global scope de
            // origen no lo alcanza: hay que repetir el filtro a mano para que
            // no valide ids de macros de HelpdeskTickets.
            'ids.*' => ['integer', Rule::exists('helpdesk.helpdesk_macros', 'id')->where('module', Macro::MODULE)],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos un macro.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos un macro.',
            'ids.*.integer' => 'Cada identificador debe ser un numero entero.',
            'ids.*.exists' => 'Uno o mas macros seleccionados no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'ids' => 'identificadores',
            'ids.*' => 'identificador',
        ];
    }
}
