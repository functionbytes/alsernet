@php
    $fId          = $field['id'] ?? '';
    $fLabel       = $field['label'] ?? '';
    $fType        = $field['type'] ?? 'text';
    $fPlaceholder = $field['placeholder'] ?? '';
    $fRows        = $field['rows'] ?? 4;
    $fOptions     = isset($field['options']) && is_array($field['options'])
        ? collect($field['options'])->map(fn($v, $k) => "$k:$v")->implode("\n")
        : '';
@endphp

<div class="field-row border rounded p-3 mb-2 bg-light">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="field-drag-handle text-muted" style="cursor:grab;" title="Arrastrar para reordenar">
                <i class="fas fa-grip-vertical"></i>
            </span>
            <span class="fw-semibold small text-secondary">Campo #{{ is_numeric($index) ? $index + 1 : '' }}</span>
        </div>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary copy-placeholder-btn" title="Copiar {id} al portapapeles">
                <i class="fas fa-copy"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">ID del campo</label>
            <input type="text" class="form-control form-control-sm field-id"
                   value="{{ $fId }}" placeholder="bc_texto" pattern="[a-z0-9_]+">
            <small class="text-muted" style="font-size:11px">Usar en shortcode: <code>{{"{{"}}{{""}}&nbsp;}}</code></small>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Etiqueta</label>
            <input type="text" class="form-control form-control-sm field-label"
                   value="{{ $fLabel }}" placeholder="Texto del botón">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Tipo</label>
            <select class="form-select form-select-sm field-type-select select2">
                @foreach(['text' => 'Texto', 'textarea' => 'Área de texto', 'url' => 'URL', 'number' => 'Número', 'select' => 'Selección'] as $val => $lbl)
                    <option value="{{ $val }}" {{ $fType === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">Placeholder</label>
            <input type="text" class="form-control form-control-sm field-placeholder"
                   value="{{ $fPlaceholder }}" placeholder="Texto de ejemplo...">
        </div>
        <div class="col-md-3 field-rows-group" style="{{ $fType !== 'textarea' ? 'display:none' : '' }}">
            <label class="form-label small fw-semibold mb-1">Filas</label>
            <input type="number" class="form-control form-control-sm field-rows"
                   value="{{ $fRows }}" min="2" max="20">
        </div>
        <div class="col-12 field-options-group" style="{{ $fType !== 'select' ? 'display:none' : '' }}">
            <label class="form-label small fw-semibold mb-1">Opciones <small class="text-muted">(una por línea, formato <code>valor:Etiqueta</code>)</small></label>
            <textarea class="form-control form-control-sm font-monospace field-options"
                      rows="3" placeholder="primary:Primary&#10;secondary:Secondary&#10;danger:Danger">{{ $fOptions }}</textarea>
        </div>
    </div>
</div>
