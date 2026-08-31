{{--
    Selector de color: un input[type=color] (el swatch) sincronizado con un
    input de texto para el hex, en ambas direcciones — asi quien prefiere
    escribir el codigo a mano puede hacerlo, y el swatch siempre refleja el
    valor real que se envia en el formulario (el input de texto lleva el
    `name`, el color es solo un ayudante visual).

    Parametros:
    - name (string, requerido): name del campo que se envia en el formulario.
    - value (string, opcional): valor inicial en formato "#rrggbb".
    - id (string, opcional): id base para los dos inputs; por defecto usa $name.
    - compact (bool, opcional): version angosta para columnas estrechas
      (swatch mas chico, sin label propia — la fila ya trae la suya al lado).
    - label (string, opcional): label visible encima del campo (se omite en
      modo compact, donde no hay sitio).
--}}
@php
    $id ??= $name;
    $value = $value ?? '#000000';
    $compact ??= false;
    $hexId = $id.'_hex';
@endphp

<div class="color-field d-flex align-items-center gap-2">
    @if(! empty($label) && ! $compact)
        <label class="form-label fw-bold me-1" for="{{ $hexId }}">{{ $label }}</label>
    @endif
    <input
        type="color"
        id="{{ $id }}"
        class="form-control form-control-color"
        value="{{ $value }}"
        title="{{ $label ?? 'Color' }}"
        @if($compact) style="width: 2.75rem; padding: 0.25rem;" @endif
    >
    <input
        type="text"
        id="{{ $hexId }}"
        name="{{ $name }}"
        class="form-control"
        value="{{ $value }}"
        maxlength="7"
        pattern="^#[0-9A-Fa-f]{6}$"
        autocomplete="off"
        @if($compact) style="max-width: 6.5rem;" @endif
    >
</div>

<script>
(function () {
    var colorInput = document.getElementById('{{ $id }}');
    var hexInput = document.getElementById('{{ $hexId }}');

    if (! colorInput || ! hexInput) {
        return;
    }

    // Aplicar valores de una plantilla (o cualquier otro script) solo
    // necesita poner colorInput.value y disparar 'input' sobre el color:
    // este listener propaga el cambio al campo de texto solo, sin que el
    // llamador tenga que conocer el id del input hex.
    colorInput.addEventListener('input', function () {
        hexInput.value = colorInput.value;
        hexInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    hexInput.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(hexInput.value)) {
            colorInput.value = hexInput.value;
        }
    });
})();
</script>
