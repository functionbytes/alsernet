<?php

namespace Modules\HelpdeskTickets\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Support\AutomationCatalog;

/**
 * Regla de escalado creada desde el modal de la pantalla de tickets.
 *
 * A diferencia de StoreAutomationRequest (Ajustes), que acepta las condiciones
 * y acciones como JSON libre —ahí se teclean a mano en un textarea—, aquí
 * llegan ya estructuradas desde el editor "Si… Entonces…" y se validan contra
 * AutomationCatalog: campo, operador y acción tienen que existir de verdad en
 * AutomationEngine. Una regla con una acción que el motor no implementa se
 * guardaba sin protestar y luego no hacía nada.
 */
class StoreTicketOpsAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', Rule::in(array_keys(Automation::$triggerEvents))],
            'is_active' => ['nullable', 'boolean'],
            'actions' => ['required', 'array', 'min:1', 'max:5'],
            'actions.*.type' => ['required', 'string', Rule::in(array_keys(AutomationCatalog::actionsByKey()))],
            'actions.*.value' => ['nullable'],
        ], self::conditionRules());
    }

    /**
     * Parte de condiciones, compartida con la prueba en seco de la regla.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function conditionRules(): array
    {
        return [
            // nullable y no required: "cualquier ticket" (la opción
            // "cualquier prioridad" del mockup) es una regla sin condiciones,
            // que el motor trata como "siempre coincide". No vale 'present':
            // jQuery no serializa los arrays vacíos, así que en ese caso la
            // clave ni siquiera llega.
            'conditions' => ['nullable', 'array', 'max:5'],
            'conditions.*.field' => ['required', 'string', Rule::in(array_keys(AutomationCatalog::fieldsByKey()))],
            'conditions.*.op' => ['required', 'string', Rule::in(array_keys(AutomationCatalog::operators()))],
            'conditions.*.value' => ['nullable'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validarCondiciones($validator);

            if ($this->validaAcciones()) {
                $this->validarAcciones($validator);
            }
        });
    }

    /**
     * La prueba en seco solo manda condiciones; el store manda además acciones.
     */
    protected function validaAcciones(): bool
    {
        return true;
    }

    private function validarCondiciones(Validator $validator): void
    {
        $campos = AutomationCatalog::fieldsByKey();

        foreach ((array) $this->input('conditions', []) as $i => $condition) {
            $field = $condition['field'] ?? null;
            $op = $condition['op'] ?? null;
            $spec = $campos[$field] ?? null;

            if (! $spec || ! $op) {
                continue; // ya lo ha marcado la regla Rule::in
            }

            if (! in_array($op, $spec['ops'], true)) {
                $validator->errors()->add("conditions.{$i}.op", 'Ese operador no se puede usar con "'.$spec['label'].'".');

                continue;
            }

            // is_null / is_not_null no llevan valor: preguntan por la ausencia.
            if (in_array($op, ['is_null', 'is_not_null'], true)) {
                continue;
            }

            $value = $condition['value'] ?? null;

            if ($op === 'in') {
                if (! is_array($value) || $value === []) {
                    $validator->errors()->add("conditions.{$i}.value", 'Elige al menos un valor.');
                }

                continue;
            }

            if (is_array($value) || $value === null || $value === '') {
                $validator->errors()->add("conditions.{$i}.value", 'Falta el valor de la condición.');

                continue;
            }

            $this->validarQueElIdExiste($validator, "conditions.{$i}.value", $spec, $value);
        }
    }

    private function validarAcciones(Validator $validator): void
    {
        $acciones = AutomationCatalog::actionsByKey();

        foreach ((array) $this->input('actions', []) as $i => $action) {
            $spec = $acciones[$action['type'] ?? null] ?? null;

            if (! $spec) {
                continue;
            }

            if ($spec['input'] === 'none') {
                continue;
            }

            $value = $action['value'] ?? null;

            if (is_array($value) || $value === null || $value === '') {
                $validator->errors()->add("actions.{$i}.value", 'Falta el valor de la acción "'.$spec['label'].'".');

                continue;
            }

            $this->validarQueElIdExiste($validator, "actions.{$i}.value", $spec, $value);
        }
    }

    /**
     * Los desplegables mandan ids de catálogos vivos (estado, equipo,
     * categoría). Un id inexistente dejaba la regla escribiendo un
     * status_id/group_id huérfano en el ticket.
     *
     * Los agentes NO se comprueban aquí: assign_user ya hace User::find() y
     * no hace nada si no existe, y la tabla de usuarios vive en otra conexión.
     *
     * @param  array<string, mixed>  $spec
     */
    private function validarQueElIdExiste(Validator $validator, string $key, array $spec, mixed $value): void
    {
        $modelo = match ($spec['options'] ?? null) {
            'statuses' => TicketStatus::class,
            'groups' => TicketGroup::class,
            'categories' => TicketCategory::class,
            default => null,
        };

        if ($modelo === null) {
            // priorities: valor cerrado, se comprueba contra el catálogo.
            if (($spec['options'] ?? null) === 'priorities'
                && ! in_array((string) $value, array_column(AutomationCatalog::priorities(), 'id'), true)) {
                $validator->errors()->add($key, 'Esa prioridad no existe.');
            }

            return;
        }

        if (! $modelo::query()->whereKey((int) $value)->exists()) {
            $validator->errors()->add($key, 'La opción elegida ya no existe.');
        }
    }

    /**
     * Condiciones listas para el motor: [{field, op, value}] con el valor ya
     * en el tipo que espera la comparación.
     *
     * @return array<int, array<string, mixed>>
     */
    public function condicionesNormalizadas(): array
    {
        $campos = AutomationCatalog::fieldsByKey();
        $out = [];

        foreach ((array) $this->input('conditions', []) as $condition) {
            $spec = $campos[$condition['field']] ?? null;

            if (! $spec) {
                continue;
            }

            $op = $condition['op'];
            $value = $condition['value'] ?? null;

            $out[] = [
                'field' => $spec['field'],
                'op' => $op,
                'value' => match (true) {
                    in_array($op, ['is_null', 'is_not_null'], true) => null,
                    $op === 'in' => array_values(array_map(
                        fn ($v) => AutomationCatalog::castValue($spec['cast'], $v),
                        (array) $value
                    )),
                    default => AutomationCatalog::castValue($spec['cast'], $value),
                },
            ];
        }

        return $out;
    }

    /**
     * Acciones listas para el motor: [{type, value}].
     *
     * @return array<int, array<string, mixed>>
     */
    public function accionesNormalizadas(): array
    {
        $acciones = AutomationCatalog::actionsByKey();
        $out = [];

        foreach ((array) $this->input('actions', []) as $action) {
            $spec = $acciones[$action['type']] ?? null;

            if (! $spec) {
                continue;
            }

            $out[] = [
                'type' => $spec['type'],
                'value' => $spec['input'] === 'none'
                    ? null
                    : AutomationCatalog::castValue($spec['cast'], $action['value'] ?? null),
            ];
        }

        return $out;
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'trigger_event' => 'disparador',
            'conditions' => 'condiciones',
            'actions' => 'acciones',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ponle un nombre a la regla.',
            'trigger_event.required' => 'Elige cuándo se evalúa la regla.',
            'trigger_event.in' => 'Ese disparador no existe.',
            'actions.required' => 'Una regla sin acciones no haría nada.',
            'actions.min' => 'Una regla sin acciones no haría nada.',
        ];
    }
}
