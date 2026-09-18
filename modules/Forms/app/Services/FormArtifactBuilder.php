<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Facades\View;
use Modules\Forms\Models\Form;

/**
 * Compila un formulario a un artefacto autocontenido (HTML + CSS + JS) que otro
 * sistema puede almacenar y servir por su cuenta, sin llamar a Laravel en runtime.
 *
 * Hoy el consumidor es el módulo `alsernetforms` de PrestaShop: recibe el
 * artefacto al pulsar "Publicar", lo guarda en su propia tabla y lo pinta desde
 * ahí. Si este panel se cae, los formularios del sitio siguen funcionando.
 *
 * Lo que el artefacto NO lleva, a propósito: jQuery, jQuery Validate, Select2,
 * toastr ni Bootstrap. El front de la tienda ya los carga en todas las páginas
 * (verificado en el navegador: jQuery 3.5.1, Validate con mensajes en español,
 * Select2, toastr, $.fn.modal y las utilidades CSS de Bootstrap). Duplicarlos
 * rompería el tema.
 */
class FormArtifactBuilder
{
    /**
     * Formato del artefacto. Se versiona porque el consumidor lo persiste: si
     * algún día cambia la forma, PrestaShop necesita poder rechazar lo que no
     * entiende en vez de renderizar basura.
     *
     * v2: el motor (forms.js) deja de ir dentro del `js` de cada formulario y
     * viaja aparte en `engine`. Antes se copiaba entero en los 19 artefactos
     * (~38 KB cada uno) y en una ficha de producto, que lleva dos formularios,
     * el navegador se lo descargaba y ejecutaba dos veces.
     */
    public const ARTIFACT_VERSION = 2;

    /**
     * @param  array{form_key?: string, submit_url?: string, recaptcha_site_key?: string}  $options
     * @return array<string, mixed>
     */
    public function build(Form $form, array $options = []): array
    {
        $form->loadMissing(['fields' => fn ($q) => $q->visible()->ordered()]);

        $formKey = $options['form_key'] ?? $form->slug;
        $formId = 'alsf-'.$formKey;

        $html = $this->buildHtml($form, $formId, $formKey, $options);
        $css = $this->buildCss($form, $formId);
        $js = $this->buildJs($form, $formId, $formKey, $options);
        $engine = $this->engineSource();
        $fieldsMeta = $this->buildFieldsMeta($form);
        $translations = $this->buildTranslations($form, $formId, $formKey, $options, $html);

        return [
            'artifact_version' => self::ARTIFACT_VERSION,
            'engine' => $engine,
            'engine_hash' => hash('sha256', $engine),
            'form_key' => $formKey,
            'form_id' => $form->id,
            'label' => $form->name,
            'slug' => $form->slug,
            'html' => $html,
            'css' => $css,
            'js' => $js,
            'fields_meta' => $fieldsMeta,
            'translations' => $translations,
            'hash' => $this->hash($html, $css, $js, $fieldsMeta, $translations, $engine),
        ];
    }

    /**
     * El mismo HTML compilado una vez por idioma.
     *
     * Los .tpl originales traducían con `{l s='...'}`, así que un visitante
     * inglés veía el formulario en inglés. El artefacto se compilaba en un solo
     * idioma y servía español a los seis: esta es la parte que lo devuelve.
     *
     * Solo el HTML varía. El CSS es igual para todos, y el JS también: en vez de
     * hornear el idioma en la configuración, forms.js lo lee de
     * `prestashop.language.iso_code` -- así no hay que guardar seis copias del
     * motor entero por formulario.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, array{html: string}>
     */
    private function buildTranslations(Form $form, string $formId, string $formKey, array $options, string $baseHtml): array
    {
        $base = $options['locale'] ?? app()->getLocale();
        $anterior = app()->getLocale();
        $out = [];

        foreach ($this->localesOf($form) as $locale) {
            if ($locale === $base) {
                continue;
            }

            try {
                app()->setLocale($locale);
                $html = $this->buildHtml($form, $formId, $formKey, $options + ['locale' => $locale]);
            } finally {
                app()->setLocale($anterior);
            }

            // Un idioma sin ninguna traducción real produce el HTML base: no
            // merece ocupar sitio ni viajar a la tienda.
            if ($html !== $baseHtml) {
                $out[$locale] = ['html' => $html];
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * Idiomas para los que algún campo tiene traducción.
     *
     * @return array<int, string>
     */
    private function localesOf(Form $form): array
    {
        $locales = [];

        foreach ($form->fields as $field) {
            if (is_array($field->translations)) {
                $locales = array_merge($locales, array_keys($field->translations));
            }
        }

        return array_values(array_unique(array_filter($locales, 'is_string')));
    }

    /**
     * Hash de la definición compilada. Es lo que permite decir "hay cambios sin
     * publicar" y no reenviar lo idéntico. Se calcula sobre el artefacto ya
     * compilado (no sobre el modelo) para que cualquier cambio que altere el
     * resultado visible cuente, y ninguno que no lo altere dispare una
     * republicación.
     *
     * @param  array<int, array<string, mixed>>  $fieldsMeta
     */
    public function hash(string $html, string $css, string $js, array $fieldsMeta, array $translations = [], string $engine = ''): string
    {
        return hash('sha256', implode("\0", [
            self::ARTIFACT_VERSION,
            $html,
            $css,
            $js,
            json_encode($fieldsMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            // El motor cuenta: si cambia, el formulario servido cambia.
            $engine,
        ]));
    }

    /**
     * El motor del constructor, compartido por todos los formularios. La tienda
     * lo guarda una sola vez y lo registra antes del script de cada formulario.
     */
    private function engineSource(): string
    {
        $base = module_path('Forms', 'public/js/forms.js');

        return is_readable($base) ? (string) file_get_contents($base) : '';
    }

    /**
     * Hash del artefacto actual de un formulario, para comparar contra el
     * publicado sin quedarse con el resto del artefacto.
     */
    public function currentHash(Form $form, array $options = []): string
    {
        return $this->build($form, $options)['hash'];
    }

    private function buildHtml(Form $form, string $formId, string $formKey, array $options): string
    {
        $steps = $form->fields->pluck('step_number')->unique()->sort()->values();

        if ($steps->isEmpty()) {
            $steps = collect([1]);
        }

        $isMultiStep = (bool) $form->is_multi_step && $steps->count() > 1;

        return trim(View::make('forms::public.partials.form-body', [
            'artifactMode' => true,
            'artifactFormKey' => $formKey,
            'artifactRecaptchaSiteKey' => $options['recaptcha_site_key'] ?? '',
            'form' => $form,
            'formId' => $formId,
            'isMultiStep' => $isMultiStep,
            'steps' => $steps,
            'totalSteps' => $steps->count(),
            'floatingLabel' => (bool) ($form->floating_label ?? false),
            'buttonText' => $form->submit_button_text ?: 'Enviar',
            'buttonColor' => $form->style_config['button_color'] ?? null,
            'showTitle' => true,
            'theme' => $form->theme ?: 'default',
            'captchaEnabled' => false,
        ])->render());
    }

    /**
     * CSS del artefacto: base del módulo + lo derivado de style_config + el
     * custom_css del usuario, este último SCOPEADO al wrapper.
     *
     * El scoping importa: en el panel el custom_css se inyecta tal cual y solo
     * afecta a una página nuestra, pero aquí aterriza en la tienda entera. Una
     * regla como `body { background: red }` teñiría el sitio del cliente.
     */
    private function buildCss(Form $form, string $formId): string
    {
        $parts = [];

        $base = module_path('Forms', 'public/css/forms.css');

        if (is_readable($base)) {
            $parts[] = "/* forms.css (base del modulo) */\n".file_get_contents($base);
        }

        if ($generated = $this->cssFromStyleConfig($form, $formId)) {
            $parts[] = "/* generado desde style_config */\n".$generated;
        }

        if (! empty($form->custom_css)) {
            $parts[] = "/* custom_css del formulario (scopeado) */\n"
                .$this->scopeCss($form->custom_css, '#'.$formId.'-wrapper');
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * Traduce style_config a CSS. Hasta ahora esta columna se guardaba pero no
     * la leía nadie: el artefacto es su primer consumidor real.
     */
    private function cssFromStyleConfig(Form $form, string $formId): string
    {
        $config = $form->style_config;

        if (! is_array($config) || $config === []) {
            return '';
        }

        $wrapper = '#'.$formId.'-wrapper';
        $rules = [];

        $map = [
            'primary_color' => [$wrapper.' .btn-primary', ['background-color', 'border-color']],
            'text_color' => [$wrapper, ['color']],
            'background_color' => [$wrapper, ['background-color']],
            'border_color' => [$wrapper.' .form-control, '.$wrapper.' .form-select', ['border-color']],
            'label_color' => [$wrapper.' .form-label', ['color']],
        ];

        foreach ($map as $key => [$selector, $properties]) {
            $value = $config[$key] ?? null;

            if (! is_string($value) || ! preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
                continue;
            }

            $declarations = implode(';', array_map(fn ($p) => "{$p}:{$value}", $properties));
            $rules[] = "{$selector}{{$declarations}}";
        }

        foreach (['border_radius' => 'border-radius', 'font_size' => 'font-size'] as $key => $property) {
            $value = $config[$key] ?? null;

            if (! is_scalar($value) || ! preg_match('/^\d+(\.\d+)?(px|rem|em|%)?$/', (string) $value)) {
                continue;
            }

            $value = is_numeric($value) ? $value.'px' : $value;
            $rules[] = "{$wrapper} .form-control,{$wrapper} .form-select,{$wrapper} .btn{{$property}:{$value}}";
        }

        if (! empty($config['font_family']) && is_string($config['font_family'])) {
            $family = preg_replace('/[^a-zA-Z0-9\s,\'"-]/', '', $config['font_family']);
            $rules[] = "{$wrapper}{font-family:{$family}}";
        }

        return implode("\n", $rules);
    }

    /**
     * Antepone el scope a cada selector de una hoja simple. No es un parser CSS
     * completo: respeta @media/@supports recursivamente y deja pasar el resto de
     * at-rules (@font-face, @keyframes) sin tocar, que es lo que se ve en la
     * práctica en el custom_css de un formulario.
     */
    private function scopeCss(string $css, string $scope): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
        $out = '';
        $length = strlen($css);
        $buffer = '';
        $i = 0;

        while ($i < $length) {
            $char = $css[$i];

            if ($char === '{') {
                $selector = trim($buffer);
                $buffer = '';
                $block = $this->readBlock($css, $i); // $i queda tras la llave de cierre

                if ($selector !== '' && $selector[0] === '@') {
                    $out .= str_starts_with($selector, '@media') || str_starts_with($selector, '@supports')
                        ? $selector.'{'.$this->scopeCss($block, $scope).'}'
                        : $selector.'{'.$block.'}';
                } else {
                    $out .= $this->scopeSelector($selector, $scope).'{'.trim($block).'}';
                }

                continue;
            }

            $buffer .= $char;
            $i++;
        }

        return trim($out);
    }

    /**
     * Lee el bloque {...} que empieza en $i contando llaves anidadas y deja $i
     * apuntando justo detrás del cierre.
     */
    private function readBlock(string $css, int &$i): string
    {
        $depth = 0;
        $start = $i + 1;
        $length = strlen($css);

        for (; $i < $length; $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    $block = substr($css, $start, $i - $start);
                    $i++;

                    return $block;
                }
            }
        }

        $i = $length;

        return substr($css, $start);
    }

    private function scopeSelector(string $selector, string $scope): string
    {
        $parts = array_filter(array_map('trim', explode(',', $selector)), fn ($p) => $p !== '');

        $scoped = array_map(function (string $part) use ($scope) {
            // Un custom_css que apunta a body/html quiere decir "todo el
            // formulario": se traduce al wrapper en vez de dejarlo escapar.
            if (in_array($part, ['body', 'html', ':root'], true)) {
                return $scope;
            }

            return $scope.' '.$part;
        }, $parts);

        return implode(',', $scoped);
    }

    /**
     * JS del artefacto: la configuración del formulario + su custom_js.
     *
     * El motor (forms.js) ya no va aquí: viaja en `engine` y la tienda lo sirve
     * como un único fichero compartido. No hay problema de orden -- el motor
     * arranca en $(document).ready, que corre después de que todos los scripts
     * del pie se hayan ejecutado, así que da igual cuál cargue primero.
     */
    private function buildJs(Form $form, string $formId, string $formKey, array $options): string
    {
        $parts = [];

        $parts[] = "/* configuracion del formulario */\n".$this->configScript($form, $formId, $formKey, $options);

        if (! empty($form->custom_js)) {
            // custom_js nunca se llegó a inyectar en el render de Laravel; aquí
            // sí, envuelto para que un error suyo no tumbe el resto del script.
            $parts[] = "/* custom_js del formulario */\ntry {\n".$form->custom_js."\n} catch (e) { if (window.console) console.error('custom_js:', e); }";
        }

        return trim(implode("\n\n", $parts));
    }

    private function configScript(Form $form, string $formId, string $formKey, array $options): string
    {
        $steps = $form->fields->pluck('step_number')->unique()->sort()->values();
        $totalSteps = max(1, $steps->count());

        $conditions = $form->fields
            ->map(fn ($f) => ['key' => $f->key, 'conditions' => $f->conditions, 'logicJumps' => $f->logic_jumps])
            ->filter(fn ($f) => ! empty($f['conditions']) || ! empty($f['logicJumps']))
            ->values();

        $calculationFields = $form->fields
            ->where('type', 'calculation')
            ->map(fn ($f) => ['key' => $f->key, 'formula' => $f->formula])
            ->values();

        $config = [
            'formId' => $form->id,
            'slug' => $form->slug,
            'formKey' => $formKey,
            // Le dice a forms.js que hable el contrato de la tienda: 'action'/'iso'
            // en el cuerpo y el resultado real en `status` con HTTP 200 siempre.
            'platform' => 'prestashop',
            // Endpoint del propio PrestaShop: el envío no pasa por Laravel.
            'submitUrl' => $options['submit_url'] ?? 'modules/alsernetforms/controllers/routes.php',
            'isMultiStep' => (bool) $form->is_multi_step && $totalSteps > 1,
            'totalSteps' => $totalSteps,
            // El texto que se ve sale del bloque HTML, que sí se compila por
            // idioma; esto queda para quien lea la configuración publicada.
            'successMessage' => $form->localizedSuccessMessage($options['locale'] ?? app()->getLocale()),
            'redirectUrl' => $form->redirect_url,
            'successAnimation' => $form->success_animation ?: 'fade',
            'display' => 'inline',
            // Sin sesión Laravel no hay CSRF; PrestaShop valida con reCAPTCHA.
            'csrfToken' => '',
            'locale' => $options['locale'] ?? 'es',
            'conditions' => $conditions,
            'calculationFields' => $calculationFields,
            // El autoguardado de borradores necesita endpoints de Laravel.
            'abandonTrackingEnabled' => false,
        ];

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return "window.FormsConfig = window.FormsConfig || {};\n"
            .'window.FormsConfig['.json_encode($formId)."] = {$json};";
    }

    /**
     * Metadatos por campo. Cumplen dos funciones en PrestaShop: whitelist de lo
     * que se acepta en el envío, y etiquetas legibles para el correo y el ticket
     * (hoy duplicadas a mano en AlsernetFormFieldLabels).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFieldsMeta(Form $form): array
    {
        $layoutTypes = ['section_header', 'html_block', 'divider', 'spacer'];

        return $form->fields
            ->reject(fn ($field) => in_array($field->type, $layoutTypes, true))
            ->map(fn ($field) => array_filter([
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'required' => (bool) $field->is_required,
                'step' => (int) ($field->step_number ?: 1),
                // La tienda lo guarda igualmente en su propio registro de
                // envíos; lo que evita es mandarlo al ticket de Helpdesk.
                'exclude_from_helpdesk' => $field->exclude_from_helpdesk ? true : null,
                // Para 'file', cuantos archivos admite: la tienda lo necesita
                // para leer $_FILES como array en vez de como fichero suelto.
                'max_files' => $field->type === 'file' ? max(1, (int) ($field->max_value ?: 1)) : null,
                'options' => $this->fieldOptions($field),
            ], fn ($value) => $value !== null && $value !== []))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>|null
     */
    private function fieldOptions($field): ?array
    {
        if (! in_array($field->type, ['select', 'checkbox', 'radio', 'image_choice'], true)) {
            return null;
        }

        $options = $field->options;

        if (! is_array($options) || $options === []) {
            return null;
        }

        return array_values(array_map(function ($option) {
            if (is_array($option)) {
                return [
                    'value' => (string) ($option['value'] ?? $option['label'] ?? ''),
                    'label' => (string) ($option['label'] ?? $option['value'] ?? ''),
                ];
            }

            return ['value' => (string) $option, 'label' => (string) $option];
        }, $options));
    }
}
