<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\AlsernetForm;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormPrestashopPublication;
use Modules\Forms\Services\FormService;

/**
 * Reconstruye en el constructor los formularios historicos de la tienda.
 *
 * Consume el JSON que produce
 * `modules/alsernetforms/cron/export-form-definitions.php` en PrestaShop, que
 * cruza AlsernetFormFieldLabels (los campos que el controlador lee de verdad,
 * con su etiqueta en espanol) con el .tpl (tipo y obligatoriedad).
 *
 * Importar NO publica: deja el formulario vinculado a su form_key y en estado
 * "con cambios sin publicar", para revisarlo en el editor y decidir cuando
 * sustituye al de codigo. Mientras no se publique, la tienda sigue sirviendo
 * el .tpl de siempre.
 */
class ImportStoreFormsCommand extends Command
{
    protected $signature = 'forms:import-store
                            {file : JSON generado por export-form-definitions.php}
                            {--key=* : Importar solo estas form_key}
                            {--dry-run : Enseña lo que haría sin tocar la base de datos}';

    protected $description = 'Reconstruye en el constructor los formularios de la tienda (alsernetforms)';

    public function handle(FormService $formService): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("No se puede leer {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error('El fichero no es un JSON válido.');

            return self::FAILURE;
        }

        $only = (array) $this->option('key');
        $dryRun = (bool) $this->option('dry-run');

        $imported = 0;
        $skipped = [];

        foreach ($data as $formKey => $definition) {
            if ($only && ! in_array($formKey, $only, true)) {
                continue;
            }

            $fields = $definition['fields'] ?? [];

            if (! $fields) {
                // huntinginsurance no pasa por submitReport (integración aparte
                // con la aseguradora): no hay nada declarativo que importar.
                $skipped[$formKey] = 'sin campos mapeados';

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '  <info>%s</info> — %s (%d campos)',
                    $formKey,
                    $definition['label'] ?? $formKey,
                    count($fields)
                ));

                $imported++;

                continue;
            }

            DB::transaction(function () use ($formKey, $definition, $fields, $formService, &$imported) {
                $form = $this->upsertForm($formKey, $definition, $formService);
                $this->syncFields($form, $fields);
                $this->linkToStore($form, $formKey);

                $imported++;
            });

            $this->line(sprintf('  importado <info>%s</info> (%d campos)', $formKey, count($fields)));
        }

        foreach ($skipped as $formKey => $reason) {
            $this->warn("  omitido {$formKey}: {$reason}");
        }

        $this->newLine();
        $this->info($dryRun
            ? "{$imported} formularios se importarían."
            : "{$imported} formularios importados. Revísalos en el editor y publícalos cuando estén listos.");

        return self::SUCCESS;
    }

    private function upsertForm(string $formKey, array $definition, FormService $formService): Form
    {
        $existing = FormPrestashopPublication::where('form_key', $formKey)->first();
        $form = $existing ? Form::find($existing->form_id) : null;

        $attributes = [
            'name' => $definition['label'] ?? $formKey,
            'description' => 'Importado del formulario «'.$formKey.'» de la tienda.',
            'is_active' => (bool) ($definition['active'] ?? true),
            'honeypot_enabled' => true,
            // La tienda ya protege el envío con reCAPTCHA v2 en routes.php.
            'captcha_enabled' => true,
            'is_multi_step' => false,
            'submit_button_text' => 'Enviar',
            'success_message' => config('forms.default_success_message'),
        ];

        if ($form) {
            $form->update($attributes);

            return $form;
        }

        $attributes['slug'] = $formService->generateUniqueSlug($attributes['name']);

        return Form::create($attributes);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(Form $form, array $fields): void
    {
        // La importación es idempotente: reejecutarla deja el formulario igual,
        // no duplica campos.
        FormField::where('form_id', $form->id)->delete();

        foreach (array_values($fields) as $index => $field) {
            $type = $this->mapType((string) ($field['type'] ?? 'text'), (bool) ($field['multiple'] ?? false));
            $options = $field['options'] ?? [];
            $translations = $this->localeMap($field, is_array($options) ? $options : []);

            FormField::create([
                'form_id' => $form->id,
                'key' => $field['key'],
                'label' => $field['label'] ?? $field['key'],
                // Las etiquetas vienen ya resueltas por idioma desde la tienda: sin
                // esto el formulario migrado saldría con la clave de traducción
                // ("Firstname shipping") en vez del texto que ve el cliente.
                'translations' => $translations !== [] ? $translations : null,
                'type' => $type,
                // El texto del consentimiento va con su HTML: lleva el enlace a
                // la política de privacidad, y perderlo sería perder el aviso
                // legal que el formulario tenía.
                // El .tpl de huntinginsurance no usa <label>: el texto que el
                // cliente lee está en el placeholder, y sin traerlo el
                // formulario migrado salía con los campos en blanco.
                'placeholder' => ($field['placeholder'] ?? '') !== '' ? $field['placeholder'] : null,
                'consent_text' => $field['consent_text'] ?? null,
                'html_content' => $field['html_content'] ?? null,
                // Sin la clave 'translations' de cada opción: ya vive en el mapa
                // de arriba, y aquí solo duplicaría el dato.
                'options' => is_array($options) && $options !== []
                    ? array_map(fn (array $o) => Arr::except($o, ['translations']), $options)
                    : null,
                'is_required' => (bool) ($field['required'] ?? false),
                'is_visible' => true,
                'step_number' => 1,
                'sort_order' => $index + 1,
                'width' => $this->widthFor($type),
                // Los adjuntos de los formularios originales son múltiples
                // (CV, documentación, fotos): max_value es el tope de archivos.
                'max_value' => ($type === 'file' && ! empty($field['multiple'])) ? 10 : null,
                // Longitud mínima que el JS del formulario original exigía
                // (p. ej. teléfono con minlength 5).
                'min_value' => ! empty($field['minlength']) ? (int) $field['minlength'] : null,
            ]);
        }
    }

    /**
     * Traducciones en la forma que esperan FormField::localizedLabel() y
     * compañía: `['en' => ['label' => ..., 'consent_text' => ..., 'options' => [valor => etiqueta]]]`.
     *
     * La tienda las exporta planas (una cadena por idioma), y guardarlas así
     * dejaba los accessors sin encontrar nada: el formulario se servía en
     * español en los seis idiomas, aunque los catálogos del .tpl sí tenían las
     * traducciones buenas.
     *
     * @param  array<string, mixed>  $field
     * @param  array<int, array<string, mixed>>  $options
     * @return array<string, array<string, mixed>>
     */
    private function localeMap(array $field, array $options): array
    {
        $labels = is_array($field['translations'] ?? null) ? $field['translations'] : [];
        $consents = is_array($field['consent_translations'] ?? null) ? $field['consent_translations'] : [];

        // Los idiomas presentes en cualquiera de las tres fuentes.
        $locales = array_keys($labels + $consents);

        foreach ($options as $option) {
            if (is_array($option['translations'] ?? null)) {
                $locales = array_merge($locales, array_keys($option['translations']));
            }
        }

        $map = [];

        foreach (array_unique($locales) as $locale) {
            $entry = [];

            if (($labels[$locale] ?? '') !== '') {
                $entry['label'] = $labels[$locale];
            }

            if (($consents[$locale] ?? '') !== '') {
                $entry['consent_text'] = $consents[$locale];
            }

            $opciones = [];

            foreach ($options as $option) {
                $traducida = $option['translations'][$locale] ?? '';

                if ($traducida !== '' && isset($option['value'])) {
                    $opciones[(string) $option['value']] = $traducida;
                }
            }

            if ($opciones !== []) {
                $entry['options'] = $opciones;
            }

            if ($entry !== []) {
                $map[$locale] = $entry;
            }
        }

        return $map;
    }

    /**
     * Los tipos del .tpl casi coinciden con los del constructor; las
     * excepciones se traducen aquí.
     */
    private function mapType(string $type, bool $multiple): string
    {
        // El exportador ya distingue consent de checkbox múltiple leyendo el
        // .tpl; aquí solo queda validar contra los tipos que el módulo conoce.

        $known = array_keys((array) config('forms.field_types', []));

        return in_array($type, $known, true) ? $type : 'text';
    }

    private function widthFor(string $type): string
    {
        // Los campos largos ocupan la fila; los cortos van de dos en dos.
        return in_array($type, [
            'textarea', 'consent', 'checkbox', 'radio', 'select', 'file',
            'section_header', 'html_block', 'divider',
        ], true) ? 'full' : 'half';
    }

    /**
     * Deja el formulario apuntando a su form_key, sin publicar.
     */
    private function linkToStore(Form $form, string $formKey): void
    {
        FormPrestashopPublication::updateOrCreate(
            ['form_id' => $form->id],
            ['form_key' => $formKey, 'status' => 'pending'],
        );

        AlsernetForm::where('form_key', $formKey)->update(['form_id' => $form->id]);
    }
}
