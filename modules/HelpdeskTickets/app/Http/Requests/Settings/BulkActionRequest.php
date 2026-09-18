<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las acciones masivas de los catálogos de Ajustes.
 *
 * Había diez FormRequest idénticos (~44 líneas cada uno) que solo se
 * diferenciaban en el sustantivo de dos mensajes de error: mismo authorize(),
 * mismas reglas, mismos attributes(). Cualquier cambio en la validación de
 * acciones masivas había que replicarlo diez veces.
 *
 * Las subclases solo declaran cómo se llama lo que están seleccionando.
 */
abstract class BulkActionRequest extends FormRequest
{
    /**
     * Nombre de la entidad en singular y con su artículo, tal como aparece en
     * "Debe seleccionar al menos ___" (p. ej. "un estado", "una macro").
     */
    abstract protected function entityLabel(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        $entity = $this->entityLabel();

        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => "Debe seleccionar al menos {$entity}.",
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => "Debe seleccionar al menos {$entity}.",
            'ids.*.integer' => 'Cada identificador debe ser un numero entero.',
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
