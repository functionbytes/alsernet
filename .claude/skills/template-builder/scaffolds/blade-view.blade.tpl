{{--
    SCAFFOLD: Plantilla base de Blade view para un shortcode.

    USO:
    1. Copia a: modules/Template/Templates/{Name}/Resources/views/shortcodes/{shortcode-name}.blade.php
    2. Reemplaza:
       {shortcode-name} → kebab-case del shortcode
       {component-class} → Clase CSS principal del componente
       {Name} → Nombre del template (para messages: '{slug}::messages.X')
    3. Adapta los atributos según el shortcode específico
    4. Mantén las convenciones del proyecto:
       - NO style="" inline
       - FA6 only para iconos
       - jQuery + Bootstrap 5.3 nativo
       - @once + @push para JS de plugins
       - Multi-idioma con __()
       - loading="lazy" + dimensiones en imágenes
--}}

@php
    /* ────────────────────────────────────────
       1. EXTRACT — defaults seguros
       ──────────────────────────────────────── */
    $title = $attrs['title'] ?? null;
    $subtitle = $attrs['subtitle'] ?? null;
    $description = $attrs['description'] ?? null;
    $style = $attrs['style'] ?? 'default';
    $size = $attrs['size'] ?? 'md';
    $align = $attrs['align'] ?? 'left';
    $class = $attrs['class'] ?? '';
    $id = $attrs['id'] ?? null;
    $image = $attrs['image'] ?? null;
    $icon = $attrs['icon'] ?? null;
    $link = $attrs['link'] ?? $attrs['href'] ?? $attrs['url'] ?? null;
    $imageWidth = (int) ($attrs['image-width'] ?? 600);
    $imageHeight = (int) ($attrs['image-height'] ?? 400);

    /* ────────────────────────────────────────
       2. VALIDATE — enums + ranges
       ──────────────────────────────────────── */
    $validStyles = ['default', 'primary', 'outline', 'ghost', 'filled'];
    $style = in_array($style, $validStyles, true) ? $style : 'default';

    $validSizes = ['sm', 'md', 'lg', 'xl'];
    $size = in_array($size, $validSizes, true) ? $size : 'md';

    $validAligns = ['left', 'center', 'right'];
    $align = in_array($align, $validAligns, true) ? $align : 'left';

    /* ────────────────────────────────────────
       3. COERCE — string → bool/int/array
       ──────────────────────────────────────── */
    $dismissible = filter_var($attrs['dismissible'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $cols = max(1, min(12, (int) ($attrs['cols'] ?? 4)));
    $gap = max(0, min(5, (int) ($attrs['gap'] ?? 3)));

    /* JSON parsing seguro */
    $items = is_string($attrs['items'] ?? '[]')
        ? (json_decode($attrs['items'] ?? '[]', true) ?? [])
        : (array) ($attrs['items'] ?? []);

    /* ────────────────────────────────────────
       4. EARLY RETURN — si falta atributo crítico
       ──────────────────────────────────────── */
    if (empty($title) && trim($content) === '' && empty($items)) {
        return; // No render si no hay contenido útil
    }

    /* ────────────────────────────────────────
       5. COMPUTE — clases dinámicas
       ──────────────────────────────────────── */
    $wrapperClasses = collect([
        '{component-class}',
        '{component-class}--' . $style,
        '{component-class}--' . $size,
        'text-' . $align,
        $class,
    ])->filter()->implode(' ');
@endphp

<div class="{{ $wrapperClasses }}"
    @if($id) id="{{ htmlspecialchars($id) }}" @endif
    @if(!empty($attrs['bg-image'])) data-bg-image="{{ htmlspecialchars($attrs['bg-image']) }}" @endif
    role="region">

    {{-- ICONO opcional --}}
    @if($icon)
        <i class="{{ htmlspecialchars($icon) }} {component-class}__icon"></i>
    @endif

    {{-- IMAGEN opcional --}}
    @if($image)
        <figure class="{component-class}__media">
            @if($link)<a href="{{ htmlspecialchars($link) }}">@endif
            <img src="{{ htmlspecialchars($image) }}"
                 alt="{{ htmlspecialchars($title ?? '') }}"
                 width="{{ $imageWidth }}"
                 height="{{ $imageHeight }}"
                 loading="lazy"
                 decoding="async">
            @if($link)</a>@endif
        </figure>
    @endif

    {{-- TÍTULO --}}
    @if($title)
        <h3 class="{component-class}__title">
            {{ $title }}
        </h3>
    @endif

    {{-- SUBTÍTULO --}}
    @if($subtitle)
        <p class="{component-class}__subtitle">{{ $subtitle }}</p>
    @endif

    {{-- DESCRIPCIÓN o CONTENIDO --}}
    @if(!empty(trim($content)))
        <div class="{component-class}__content">
            {!! $content !!}
        </div>
    @elseif($description)
        <p class="{component-class}__description">{{ $description }}</p>
    @endif

    {{-- BOTÓN CTA opcional --}}
    @if(!empty($attrs['button-text']) && $link)
        <a href="{{ htmlspecialchars($link) }}"
           class="btn btn-{{ $style }}"
           @if(!empty($attrs['button-target'])) target="{{ $attrs['button-target'] }}" rel="noopener noreferrer" @endif>
            {{ $attrs['button-text'] }}
            @if(!empty($attrs['button-icon']))
                <i class="{{ htmlspecialchars($attrs['button-icon']) }} ms-2"></i>
            @endif
        </a>
    @endif
</div>

{{--
    JS plugins via @once + @push (cargan UNA vez aunque shortcode aparezca N veces)
    Descomentar y adaptar si el shortcode requiere JS:
--}}
@once
    @push('styles')
        <style>
            .{component-class} {
                /* CSS específico que NO está en tokens.css */
                /* Usar var(--token-name) para tokens del proyecto */
            }
            .{component-class}--primary {
                background-color: var(--color-primary, #b10100);
            }
        </style>
    @endpush

    {{-- @push('scripts') --}}
        {{-- <script>
            // JS init aquí
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.{component-class}').forEach(el => {
                    // initialization
                });
            });
        </script> --}}
    {{-- @endpush --}}
@endonce

{{--
    NOTA SOBRE data-bg-image:
    Para evitar inline styles dinámicos, usar este patrón global JS minimal:

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-bg-image]').forEach(el => {
                    el.style.backgroundImage = `url('${el.dataset.bgImage}')`;
                });
            </script>
        @endpush
    @endonce
--}}
