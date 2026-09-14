<?php

namespace Modules\Forms\Tests\Unit\Requests;

use Modules\Forms\Http\Requests\UpdateFormRequest;
use Modules\Forms\Tests\TestCase;

/**
 * El editor envía todos los selects de configuración en cada guardado. Los que
 * el usuario no ha tocado llegan vacíos, y ConvertEmptyStringsToNull los
 * convierte en null antes de que nadie los mire.
 *
 * `success_animation` y compañía son columnas NOT NULL con valor por defecto:
 * un null ahí no significa "usa el default", significa error 500 de MySQL. Este
 * es el bug que impedía guardar CSS o JS en los 18 formularios importados.
 */
class UpdateFormRequestTest extends TestCase
{
    private function prepared(array $input): array
    {
        $request = UpdateFormRequest::create('/panel/settings/forms/1', 'PATCH', $input);

        // prepareForValidation() es protegido; se invoca como lo haría el framework.
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        return $request->all();
    }

    public function test_null_select_values_fall_back_to_the_column_default(): void
    {
        $data = $this->prepared([
            'name' => 'Formulario',
            'success_animation' => null,
            'button_position' => null,
            'theme' => null,
        ]);

        $this->assertSame('fade', $data['success_animation']);
        $this->assertSame('left', $data['button_position']);
        $this->assertSame('default', $data['theme']);
    }

    public function test_empty_strings_fall_back_too(): void
    {
        $data = $this->prepared(['name' => 'Formulario', 'success_animation' => '']);

        $this->assertSame('fade', $data['success_animation']);
    }

    public function test_a_real_choice_is_respected(): void
    {
        $data = $this->prepared(['name' => 'Formulario', 'success_animation' => 'confetti']);

        $this->assertSame('confetti', $data['success_animation']);
    }

    /**
     * Un campo que el editor no envía no debe aparecer de la nada: escribiría el
     * default sobre lo que el formulario ya tuviera guardado.
     */
    public function test_absent_fields_are_not_invented(): void
    {
        $data = $this->prepared(['name' => 'Formulario']);

        $this->assertArrayNotHasKey('success_animation', $data);
        $this->assertArrayNotHasKey('theme', $data);
    }

    public function test_animation_is_validated_against_the_column_enum(): void
    {
        $rules = (new UpdateFormRequest)->rules();

        // 'fireworks' llegó a estar en el desplegable y no existe en el enum.
        $this->assertContains('in:none,fade,checkmark,confetti', $rules['success_animation']);
    }
}
