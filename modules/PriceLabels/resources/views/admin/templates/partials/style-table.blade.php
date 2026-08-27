@php
    $showFieldActions = $showFieldActions ?? true;
@endphp

<h6 class="fw-bold mb-3 border-bottom pb-2">Estilo por campo</h6>
<p class="small text-muted mb-3">
    Si un campo queda tapado por otro en el lienzo, haz clic en su fila (fuera de los controles) para traerlo al frente y poder arrastrarlo.
</p>

<div class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div>
        <label class="form-label mb-0 small">Copiar estilo desde</label>
        <select id="apply-style-source" class="form-select form-select-sm">
            @foreach($fieldLabels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="button" id="apply-style-all" class="btn btn-sm btn-light">
        Aplicar a todos los campos
    </button>
</div>

<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>Campo</th>
                <th>Color</th>
                @if($showVertical)
                    <th>Fuente (vertical)</th>
                    <th>Tam.</th>
                @endif
                <th>Negrita</th>
                <th>Cursiva</th>
                @if($showHorizontal)
                    <th>Fuente (horizontal)</th>
                    <th>Tam.</th>
                @endif
                <th>Ancho caja</th>
                <th>Alto caja</th>
                @if($showFieldActions)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($fieldLabels as $key => $label)
                <tr data-field-key="{{ $key }}">
                    <td class="fw-semibold">{{ $label }}</td>
                    <td>
                        <input type="color" class="form-control form-control-color"
                               id="field-{{ $key }}-color"
                               name="fields[{{ $key }}][color]"
                               value="{{ $fields[$key]['color'] }}">
                    </td>
                    @if($showVertical)
                        <td>
                            <select class="form-select form-select-sm" id="field-{{ $key }}-font-family" name="fields[{{ $key }}][font_family]">
                                @foreach($fontOptions as $value => $font)
                                    <option value="{{ $value }}" {{ $fields[$key]['font_family'] === $value ? 'selected' : '' }}>{{ $font }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm pricelabels-input-narrow"
                                   id="field-{{ $key }}-font-size"
                                   name="fields[{{ $key }}][font_size]" min="6" max="72"
                                   value="{{ $fields[$key]['font_size'] }}">
                        </td>
                    @endif
                    <td>
                        <input type="hidden" name="fields[{{ $key }}][bold]" value="0">
                        <input type="checkbox" class="form-check-input" id="field-{{ $key }}-bold" name="fields[{{ $key }}][bold]" value="1"
                               {{ $fields[$key]['bold'] ? 'checked' : '' }}>
                    </td>
                    <td>
                        <input type="hidden" name="fields[{{ $key }}][italic]" value="0">
                        <input type="checkbox" class="form-check-input" id="field-{{ $key }}-italic" name="fields[{{ $key }}][italic]" value="1"
                               {{ $fields[$key]['italic'] ? 'checked' : '' }}>
                    </td>
                    @if($showHorizontal)
                        <td>
                            <select class="form-select form-select-sm" id="field-{{ $key }}-font-family-h" name="fields[{{ $key }}][font_family_h]">
                                @foreach($fontOptions as $value => $font)
                                    <option value="{{ $value }}" {{ $fields[$key]['font_family_h'] === $value ? 'selected' : '' }}>{{ $font }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm pricelabels-input-narrow"
                                   id="field-{{ $key }}-font-size-h"
                                   name="fields[{{ $key }}][font_size_h]" min="6" max="72"
                                   value="{{ $fields[$key]['font_size_h'] }}">
                        </td>
                    @endif
                    <td>
                        <input type="number" class="form-control form-control-sm pricelabels-input-medium"
                               id="field-{{ $key }}-box-w"
                               name="fields[{{ $key }}][box_w]" min="10" max="2000"
                               value="{{ $fields[$key]['box_w'] }}">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm pricelabels-input-medium"
                               id="field-{{ $key }}-box-h"
                               name="fields[{{ $key }}][box_h]" min="10" max="2000"
                               value="{{ $fields[$key]['box_h'] }}">
                    </td>
                    @if($showFieldActions)
                        <td>
                            @if($key !== 'label')
                                <button type="button" class="btn btn-sm btn-light delete-btn" title="Eliminar campo"
                                        data-bs-toggle="modal" data-bs-target="#delete-modal"
                                        data-title="Eliminar campo: {{ $label }}"
                                        data-url="{{ route('pricelabels.fields.destroy', [$template, $key]) }}">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
