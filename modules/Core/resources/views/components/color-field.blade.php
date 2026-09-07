{{--
    Selector de color del panel.

    Sustituye al par input[type=color] + hex de solo lectura que habia repartido
    por los formularios: la muestra ocupa todo el ancho, el hex es editable y es
    el que se envia, hay atajos con la paleta de marca y, cuando el color sirve
    para etiquetar algo, una vista previa del badge.

    Admite varias instancias en la misma pagina: no usa ids fijos, cada raiz
    .ts-color se inicializa por separado.

        @include('core::components.color-field', [
            'name'        => 'color',
            'value'       => old('color', $status->color),
            'preview'     => $status->name,   // opcional: muestra el badge
            'previewFrom' => '#name',         // opcional: input que lo alimenta
        ])

    @param string      $name        Name del input que se envia (obligatorio)
    @param string      $value       Color actual en #RRGGBB
    @param string|null $id          Id del input de texto; por defecto se deriva del name
    @param string|null $preview     Texto del badge de vista previa; omitir para no mostrarlo
    @param string|null $previewFrom Selector CSS del input que actualiza ese texto
    @param bool        $swatches    Mostrar la paleta de atajos (por defecto si)
    @param bool        $compact     Version en linea para celdas de tabla y filas estrechas
--}}
@php
    $ccFallback = '#90bb13';
    $ccValue = trim((string) ($value ?? '')) ?: $ccFallback;
    $ccId = $id ?? 'cc-'.\Illuminate\Support\Str::slug(str_replace(['[', ']'], ['-', ''], $name));
    // $errors solo existe tras ShareErrorsFromSession: el componente tiene que
    // poder renderizarse tambien fuera de una request web (partials, mails).
    $ccErrorKey = str_replace(['[', ']'], ['.', ''], $name);
    $ccInvalid = isset($errors) && $errors->has($ccErrorKey);
    $ccPreview = $preview ?? null;
    $ccPreviewFrom = $previewFrom ?? null;
    $ccSwatches = ($swatches ?? true) && ! ($compact ?? false);
    $ccCompact = $compact ?? false;

    // Paleta de marca: verdes y grises. El selector nativo sigue permitiendo
    // cualquier color; estos son solo los atajos de un clic.
    $ccPalette = [
        '#4f6b0a' => 'Verde oscuro',
        '#90bb13' => 'Verde de marca',
        '#b6d34a' => 'Verde claro',
        '#d7e8a3' => 'Verde palido',
        '#333333' => 'Gris carbon',
        '#555555' => 'Gris medio',
        '#8a8a8a' => 'Gris',
        '#c4c4c4' => 'Gris claro',
    ];
@endphp

<div class="ts-color {{ $ccCompact ? 'ts-color--compact' : '' }}"
     @if($ccPreviewFrom) data-preview-from="{{ $ccPreviewFrom }}" @endif>

    {{-- El selector es la muestra del color: pulsarla en cualquier punto abre
         la paleta del sistema, y sigue siendo el control enfocable. --}}
    <input type="color" id="{{ $ccId }}-picker" class="ts-color__bar"
           value="{{ $ccValue }}" aria-label="Selector de color"
           @if($ccCompact) title="{{ $ccValue }}" @endif>

    <div class="ts-color__row">
        <input type="text" name="{{ $name }}" id="{{ $ccId }}"
               class="form-control ts-color__hex {{ $ccInvalid ? 'is-invalid' : '' }}"
               value="{{ $ccValue }}" maxlength="7" spellcheck="false" autocomplete="off"
               placeholder="{{ $ccFallback }}" aria-label="Codigo hexadecimal del color">

        @if($ccSwatches)
            <div class="ts-color__swatches" role="group" aria-label="Colores sugeridos">
                @foreach($ccPalette as $ccHex => $ccLabel)
                    <button type="button" class="ts-color__swatch" data-color="{{ $ccHex }}"
                            title="{{ $ccLabel }} ({{ $ccHex }})" aria-label="{{ $ccLabel }}"></button>
                @endforeach
            </div>
        @endif

        @if($ccPreview !== null)
            <div class="ts-color__preview">
                <span class="text-muted small">Vista previa</span>
                <span class="badge ts-color__badge">{{ $ccPreview ?: 'Etiqueta' }}</span>
            </div>
        @endif
    </div>

</div>

@once
    @push('styles')
        <style>
            .ts-color__bar {
                display: block;
                width: 100%;
                height: 3rem;
                padding: 0;
                border: 0;
                border-radius: .5rem;
                background: none;
                cursor: pointer;
                -webkit-appearance: none;
                appearance: none;
            }

            .ts-color__bar::-webkit-color-swatch-wrapper { padding: 0; }

            .ts-color__bar::-webkit-color-swatch {
                border: 1px solid rgba(0, 0, 0, .12);
                border-radius: .5rem;
            }

            .ts-color__bar::-moz-color-swatch {
                border: 1px solid rgba(0, 0, 0, .12);
                border-radius: .5rem;
            }

            .ts-color__bar:focus-visible {
                outline: 2px solid var(--bs-primary, #90bb13);
                outline-offset: 2px;
            }

            .ts-color__row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: .75rem;
                margin-top: .75rem;
            }

            .ts-color__hex {
                width: 8rem;
                flex: 0 0 auto;
                font-family: var(--bs-font-monospace, monospace);
                text-transform: uppercase;
                letter-spacing: .02em;
            }

            .ts-color__swatches {
                display: flex;
                flex-wrap: wrap;
                gap: .375rem;
            }

            .ts-color__swatch {
                width: 1.75rem;
                height: 1.75rem;
                padding: 0;
                border: 1px solid rgba(0, 0, 0, .12);
                border-radius: .375rem;
                background-color: var(--ts-swatch);
                cursor: pointer;
                transition: transform .12s ease, box-shadow .12s ease;
            }

            .ts-color__swatch:hover { transform: translateY(-1px); }

            .ts-color__swatch.is-active {
                box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--ts-swatch);
            }

            .ts-color__preview {
                display: flex;
                align-items: center;
                gap: .5rem;
                margin-left: auto;
            }

            .ts-color__badge {
                background-color: var(--ts-preview, #90bb13);
                color: var(--ts-preview-fg, #fff);
                font-weight: 600;
                padding: .35em .7em;
            }

            /* Version en linea: la muestra se encoge a un chip junto al hex,
               para celdas de tabla y filas donde no cabe la barra. */
            .ts-color--compact {
                display: flex;
                align-items: center;
                gap: .375rem;
            }

            .ts-color--compact .ts-color__bar {
                width: 2rem;
                height: 2rem;
                flex: 0 0 auto;
                border-radius: .375rem;
            }

            .ts-color--compact .ts-color__bar::-webkit-color-swatch { border-radius: .375rem; }
            .ts-color--compact .ts-color__bar::-moz-color-swatch { border-radius: .375rem; }

            .ts-color--compact .ts-color__row {
                flex: 1 1 auto;
                margin-top: 0;
            }

            /* En una celda de tabla el hueco es minimo: hay que asegurar que
               los siete caracteres del hex caben sin recortarse. */
            .ts-color--compact .ts-color__hex {
                width: 100%;
                min-width: 5.5rem;
                padding-left: .5rem;
                padding-right: .5rem;
                font-size: .8125rem;
            }

            @foreach($ccPalette as $ccHex => $ccLabel)
            .ts-color__swatch[data-color="{{ $ccHex }}"] { --ts-swatch: {{ $ccHex }}; }
            @endforeach
        </style>
    @endpush

    @push('scripts')
        <script>
            window.initColorFields = (function () {
                // Acepta "#abc", "abc", "#AABBCC" o "aabbcc"; null si no es un color.
                function normalize(raw) {
                    var v = String(raw || '').trim().replace(/^#/, '');

                    if (/^[0-9a-f]{3}$/i.test(v)) {
                        v = v[0] + v[0] + v[1] + v[1] + v[2] + v[2];
                    }

                    return /^[0-9a-f]{6}$/i.test(v) ? '#' + v.toLowerCase() : null;
                }

                // Texto legible sobre el color elegido: sobre los tonos claros
                // el blanco no se lee.
                function readableOn(color) {
                    var channel = function (c) {
                        c = parseInt(c, 16) / 255;
                        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                    };

                    var luminance = 0.2126 * channel(color.slice(1, 3))
                        + 0.7152 * channel(color.slice(3, 5))
                        + 0.0722 * channel(color.slice(5, 7));

                    return luminance > 0.45 ? '#1c1c1c' : '#ffffff';
                }

                function init(root) {
                    if (root.dataset.colorFieldReady) return;
                    root.dataset.colorFieldReady = '1';

                    var picker = root.querySelector('.ts-color__bar');
                    var hex = root.querySelector('.ts-color__hex');
                    var badge = root.querySelector('.ts-color__badge');
                    var swatches = Array.prototype.slice.call(root.querySelectorAll('.ts-color__swatch'));

                    if (!picker || !hex) return;

                    function apply(raw, syncHex, notify) {
                        var color = normalize(raw);

                        hex.classList.toggle('is-invalid', color === null);

                        if (!color) return;

                        picker.value = color;
                        picker.title = color;

                        // En minusculas: el hex se ve en mayusculas por CSS, pero
                        // es este valor el que se guarda.
                        if (syncHex) hex.value = color;

                        root.style.setProperty('--ts-preview', color);
                        root.style.setProperty('--ts-preview-fg', readableOn(color));

                        swatches.forEach(function (swatch) {
                            swatch.classList.toggle('is-active', swatch.dataset.color.toLowerCase() === color);
                        });

                        // El hex es el input real del formulario: cuando el color
                        // llega desde la muestra o un atajo, hay que anunciarlo
                        // para que lo oigan las vistas previas de la pagina.
                        if (notify) {
                            hex.dispatchEvent(new Event('input', { bubbles: true }));
                            hex.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }

                    picker.addEventListener('input', function () { apply(picker.value, true, true); });
                    hex.addEventListener('input', function () { apply(hex.value, false, false); });
                    hex.addEventListener('blur', function () { apply(hex.value, true, false); });

                    swatches.forEach(function (swatch) {
                        swatch.addEventListener('click', function () { apply(swatch.dataset.color, true, true); });
                    });

                    // Tras form.reset() el hex vuelve a su valor inicial, pero
                    // la muestra y la vista previa se quedarian con el anterior.
                    if (hex.form) {
                        hex.form.addEventListener('reset', function () {
                            setTimeout(function () { apply(hex.value, true, false); }, 0);
                        });
                    }

                    var source = root.dataset.previewFrom
                        ? document.querySelector(root.dataset.previewFrom)
                        : null;

                    if (source && badge) {
                        source.addEventListener('input', function () {
                            badge.textContent = source.value.trim() || 'Etiqueta';
                        });
                    }

                    apply(hex.value, true, false);
                }

                function initAll(scope) {
                    (scope || document).querySelectorAll('.ts-color').forEach(init);
                }

                document.addEventListener('DOMContentLoaded', function () { initAll(); });
                initAll();

                return initAll;
            })();
        </script>
    @endpush
@endonce
