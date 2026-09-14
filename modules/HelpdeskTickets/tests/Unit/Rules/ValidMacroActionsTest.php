<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Rules;

use Modules\HelpdeskTickets\Rules\ValidMacroActions;
use PHPUnit\Framework\TestCase;

/**
 * Antes bastaba con que las acciones fuesen JSON valido, asi que se guardaban
 * macros que solo fallaban al aplicarlas: un tipo mal escrito no hacia nada (y
 * aun asi decia "aplicada") y un JSON que no fuese lista de objetos daba un 500.
 */
class ValidMacroActionsTest extends TestCase
{
    /**
     * Ejecuta la regla sin levantar el contenedor y devuelve el primer fallo.
     */
    private function falla(string $json): ?string
    {
        $errores = [];

        (new ValidMacroActions)->validate('actions', $json, function (string $msg) use (&$errores) {
            $errores[] = $msg;
        });

        return $errores[0] ?? null;
    }

    public function test_it_accepts_a_well_formed_action_list(): void
    {
        $this->assertNull($this->falla('[{"type":"add_tag","value":"vip"}]'));
    }

    public function test_it_accepts_several_actions_including_one_without_arguments(): void
    {
        $this->assertNull($this->falla(
            '[{"type":"reply","body":"Hola"},{"type":"assign_user","value":5},{"type":"close"}]'
        ));
    }

    public function test_it_rejects_an_object_that_is_not_wrapped_in_a_list(): void
    {
        $this->assertStringContainsString('dentro de una lista', (string) $this->falla('{"type":"reply","body":"hola"}'));
    }

    public function test_it_rejects_a_list_of_strings(): void
    {
        $this->assertStringContainsString('debe ser un objeto', (string) $this->falla('["reply","close"]'));
    }

    public function test_it_rejects_an_empty_list(): void
    {
        $this->assertStringContainsString('al menos una accion', (string) $this->falla('[]'));
    }

    public function test_it_rejects_an_unknown_action_type(): void
    {
        $error = (string) $this->falla('[{"type":"set_stat","value":2}]');

        $this->assertStringContainsString('tipo desconocido', $error);
        $this->assertStringContainsString('set_status', $error, 'debe listar los tipos validos');
    }

    public function test_it_rejects_a_reply_without_body(): void
    {
        $this->assertStringContainsString('"body"', (string) $this->falla('[{"type":"reply"}]'));
    }

    public function test_it_rejects_an_action_that_needs_a_value_without_one(): void
    {
        $this->assertStringContainsString('"value"', (string) $this->falla('[{"type":"add_tag"}]'));
    }

    public function test_it_points_at_the_offending_action_by_position(): void
    {
        $error = (string) $this->falla('[{"type":"close"},{"type":"reply"}]');

        $this->assertStringContainsString('accion #2', $error);
    }

    public function test_it_rejects_a_type_that_is_not_even_a_string(): void
    {
        $this->assertStringContainsString('tipo desconocido', (string) $this->falla('[{"type":123}]'));
    }
}
