<?php

namespace Modules\Forms\Tests\Unit\Services;

use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Services\FormArtifactBuilder;
use Modules\Forms\Tests\TestCase;

class FormArtifactBuilderTest extends TestCase
{
    private FormArtifactBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = app(FormArtifactBuilder::class);
    }

    private function makeForm(array $attributes = [], array $fields = []): Form
    {
        $form = Form::create($attributes + [
            'name' => 'Formulario de prueba',
            'slug' => 'artefacto-'.uniqid(),
            'is_active' => true,
        ]);

        $fields = $fields ?: [
            ['key' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'is_required' => true],
        ];

        foreach ($fields as $i => $field) {
            FormField::create($field + [
                'form_id' => $form->id,
                'is_visible' => true,
                'step_number' => 1,
                'sort_order' => $i + 1,
            ]);
        }

        return $form->fresh();
    }

    public function test_html_has_no_laravel_csrf_and_carries_prestashop_hidden_inputs(): void
    {
        $artifact = $this->builder->build($this->makeForm(), ['form_key' => 'contacto']);

        $this->assertStringNotContainsString('name="_token"', $artifact['html']);
        $this->assertStringContainsString('name="_alsernetforms_action" value="contacto"', $artifact['html']);
        $this->assertStringContainsString('modules/alsernetforms/controllers/routes.php', $artifact['html']);
    }

    /**
     * El artefacto se cachea, así que una marca de tiempo horneada en el HTML
     * mediría lo que tardó la publicación, no lo que tardó el cliente. El
     * antibot la rellena en el navegador.
     */
    public function test_start_time_is_left_empty_so_the_artifact_can_be_cached(): void
    {
        $html = $this->builder->build($this->makeForm())['html'];

        $this->assertMatchesRegularExpression('/name="_start_time"\s+value=""/', $html);
    }

    /**
     * El CSS propio del formulario se acota a su envoltorio: sin esto, una
     * regla del formulario repintaba media tienda.
     */
    public function test_custom_css_is_scoped_to_the_form_wrapper(): void
    {
        $form = $this->makeForm(['custom_css' => '.btn { color: red; }']);
        $css = $this->builder->build($form, ['form_key' => 'demo'])['css'];

        $this->assertStringContainsString('#alsf-demo-wrapper .btn', $css);
        $this->assertStringNotContainsString("\n.btn {", $css);
    }

    /** Una media query no es un selector: acotarla la rompería. */
    public function test_media_queries_survive_scoping(): void
    {
        $form = $this->makeForm(['custom_css' => '@media (max-width: 600px) { .btn { color: red; } }']);
        $css = $this->builder->build($form, ['form_key' => 'demo'])['css'];

        $this->assertStringContainsString('@media (max-width: 600px)', $css);
        $this->assertStringContainsString('#alsf-demo-wrapper .btn', $css);
    }

    /**
     * style_config se guardaba y no se leía en ninguna parte: los colores que
     * el usuario elegía en el editor no llegaban al formulario servido.
     */
    public function test_style_config_is_compiled_to_css(): void
    {
        $form = $this->makeForm(['style_config' => ['primary_color' => '#90bb13']]);
        $css = $this->builder->build($form, ['form_key' => 'demo'])['css'];

        $this->assertStringContainsString('#alsf-demo-wrapper .btn-primary', $css);
        $this->assertStringContainsString('#90bb13', $css);
    }

    /** style_config viene de un formulario: no se copia a CSS sin mirarlo. */
    public function test_style_config_rejects_values_that_are_not_colours(): void
    {
        $form = $this->makeForm(['style_config' => ['primary_color' => 'red; } body { display:none']]);
        $css = $this->builder->build($form, ['form_key' => 'demo'])['css'];

        $this->assertStringNotContainsString('display:none', $css);
    }

    /**
     * custom_js nunca se llegó a inyectar en el render de Laravel. Aquí sí, y
     * envuelto: un error suyo no debe tumbar el resto del script.
     */
    public function test_custom_js_is_injected_and_wrapped(): void
    {
        $form = $this->makeForm(['custom_js' => 'console.log("hola");']);
        $js = $this->builder->build($form)['js'];

        $this->assertStringContainsString('console.log("hola");', $js);
        $this->assertStringContainsString('try {', $js);
        $this->assertStringContainsString('catch', $js);
    }

    /**
     * Desde el artefacto v2 el motor viaja aparte: la tienda lo guarda una sola
     * vez y lo sirve compartido. Antes se copiaba entero dentro del `js` de
     * cada formulario, así que una ficha de producto con dos formularios se
     * bajaba y ejecutaba 45 KB repetidos.
     */
    public function test_the_engine_travels_apart_from_the_form_config(): void
    {
        $artifact = $this->builder->build($this->makeForm());

        $this->assertStringContainsString('window.FormsConfig', $artifact['js']);
        $this->assertStringNotContainsString('function initForm', $artifact['js']);

        $this->assertStringContainsString('function initForm', $artifact['engine']);
        $this->assertSame(hash('sha256', $artifact['engine']), $artifact['engine_hash']);
    }

    public function test_submit_url_points_at_prestashop_not_at_laravel(): void
    {
        $js = $this->builder->build($this->makeForm())['js'];

        $this->assertStringContainsString('modules/alsernetforms/controllers/routes.php', $js);
        $this->assertStringNotContainsString('"submitUrl": "http', $js);
    }

    public function test_fields_meta_skips_layout_fields_and_keeps_options(): void
    {
        $form = $this->makeForm([], [
            ['key' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'is_required' => true],
            ['key' => 'sep', 'label' => 'Sección', 'type' => 'section_header'],
            ['key' => 'motivo', 'label' => 'Motivo', 'type' => 'select', 'options' => [
                ['value' => 'a', 'label' => 'Opción A'],
            ]],
        ]);

        $meta = $this->builder->build($form)['fields_meta'];

        $this->assertCount(2, $meta);
        $this->assertSame(['nombre', 'motivo'], array_column($meta, 'key'));
        $this->assertSame([['value' => 'a', 'label' => 'Opción A']], $meta[1]['options']);
    }

    public function test_hash_is_stable_across_rebuilds(): void
    {
        $form = $this->makeForm();

        $this->assertSame(
            $this->builder->build($form)['hash'],
            $this->builder->build($form->fresh())['hash']
        );
    }

    public function test_hash_changes_when_the_definition_changes(): void
    {
        $form = $this->makeForm();
        $before = $this->builder->build($form)['hash'];

        $form->update(['submit_button_text' => 'Enviar ahora mismo']);

        $this->assertNotSame($before, $this->builder->build($form->fresh())['hash']);
    }

    // ─── Multiidioma ──────────────────────────────────────────────────────────

    /**
     * Los .tpl originales traducían con `{l s='...'}`, así que un visitante
     * inglés veía el formulario en inglés. El artefacto se compilaba en un solo
     * idioma y la tienda servía español a los seis: esto lo fija.
     */
    public function test_the_artifact_carries_one_html_per_translated_locale(): void
    {
        $form = $this->makeForm([], [[
            'key' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
            'is_required' => true,
            'translations' => ['en' => ['label' => 'First name'], 'fr' => ['label' => 'Prénom']],
        ]]);

        $artifact = $this->builder->build($form);

        $this->assertSame(['en', 'fr'], array_keys($artifact['translations']));
        $this->assertStringContainsString('First name', $artifact['translations']['en']['html']);
        $this->assertStringContainsString('Prénom', $artifact['translations']['fr']['html']);
        // El idioma base no se duplica dentro de las traducciones.
        $this->assertStringContainsString('Nombre', $artifact['html']);
        $this->assertArrayNotHasKey('es', $artifact['translations']);
    }

    /**
     * Un idioma cuyo HTML sale idéntico al base no merece ocupar sitio ni
     * viajar a la tienda. Se usa 'nl', que no está en forms.success_messages:
     * los seis idiomas de la tienda sí cambian aunque el campo no se traduzca,
     * porque el aviso de envío correcto ya se traduce por su cuenta.
     */
    public function test_a_locale_with_no_real_translation_is_not_stored(): void
    {
        $form = $this->makeForm([], [[
            'key' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
            'translations' => ['nl' => ['placeholder' => null]],
        ]]);

        $this->assertSame([], $this->builder->build($form)['translations']);
    }

    public function test_the_hash_changes_when_only_a_translation_changes(): void
    {
        $form = $this->makeForm([], [[
            'key' => 'nombre',
            'label' => 'Nombre',
            'type' => 'text',
            'translations' => ['en' => ['label' => 'First name']],
        ]]);

        $antes = $this->builder->build($form)['hash'];

        $form->fields()->first()->update(['translations' => ['en' => ['label' => 'Given name']]]);

        $this->assertNotSame($antes, $this->builder->build($form->fresh())['hash']);
    }
}
