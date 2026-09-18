<?php

namespace Modules\HelpdeskTickets\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\HelpdeskTickets\Models\Macro;

/**
 * Comprueba que el JSON de acciones de una macro es realmente ejecutable.
 *
 * Con la validacion 'json' a secas bastaba con que el texto fuese JSON valido,
 * asi que se guardaban macros rotas que solo fallaban al usarlas delante del
 * cliente: un `type` mal escrito caia en el `default` de MacroExecutor y la
 * macro decia "aplicada" sin hacer nada, y un JSON que no fuese lista de
 * objetos reventaba con un TypeError (500) al aplicarla.
 */
class ValidMacroActions implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $actions = json_decode((string) $value, true);

        if (! is_array($actions) || $actions === []) {
            $fail('Las acciones deben ser una lista con al menos una accion. Ejemplo: [{"type": "close"}]');

            return;
        }

        if (! array_is_list($actions)) {
            $fail('Las acciones deben ir dentro de una lista, entre corchetes. Ejemplo: [{"type": "close"}]');

            return;
        }

        $specs = Macro::actionSpecs();
        $known = array_keys(Macro::$actionTypes);

        foreach ($actions as $i => $action) {
            $n = $i + 1;

            if (! is_array($action) || array_is_list($action)) {
                $fail("La accion #{$n} debe ser un objeto con al menos la clave \"type\".");

                continue;
            }

            $type = $action['type'] ?? null;

            if (! is_string($type) || ! in_array($type, $known, true)) {
                $fail("La accion #{$n} usa un tipo desconocido: ".json_encode($type).'. Tipos validos: '.implode(', ', $known).'.');

                continue;
            }

            $key = $specs[$type]['key'] ?? null;

            if ($key !== null && ! filled($action[$key] ?? null)) {
                $fail("La accion #{$n} (\"{$type}\") necesita la clave \"{$key}\": ".$specs[$type]['hint'].'.');
            }
        }
    }
}
