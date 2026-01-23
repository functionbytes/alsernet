@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formValidationCondition" method="POST" action="{{ route('settings.documents.conditions.update', $condition->uid) }}">

            {{ csrf_field() }}
            @method('PATCH')

            <div class="card-body">
                <div class="mb-4">
                    <h5 class="mb-0">Editar condición: {{ $condition->name }}</h5>
                    <p class="card-subtitle mb-0 mt-0">
                        Modifica la configuración de <code class="text-primary">{{ $condition->key }}</code>. Los cambios afectarán a todos los document types que la usen.
                    </p>
                </div>

                <!-- Tipo de Condición -->
                <div class="mb-4">
                    <h6 class="mb-0">
                        Tipo de condición
                        <span class="text-danger">*</span>
                    </h6>
                    <small class="text-muted d-block mb-3">Selecciona cómo se evaluará esta condición en los documentos</small>

                    <div class="row g-3">
                        @foreach($availableTypes as $typeKey => $typeLabel)
                            <div class="col-md-4">
                                <div class="card mb-0 condition-type-card {{ old('condition_type', $condition->condition_type) == $typeKey ? 'active' : '' }}"
                                     data-type="{{ $typeKey }}">
                                    <div class="card-body p-3">
                                        <input class="condition-type-input"
                                               type="radio"
                                               name="condition_type"
                                               id="type_{{ $typeKey }}"
                                               value="{{ $typeKey }}"
                                               {{ old('condition_type', $condition->condition_type) == $typeKey ? 'checked' : '' }}>
                                        <label class="condition-type-label w-100" for="type_{{ $typeKey }}">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-{{ $typeKey === 'sale_type_match' ? 'tags' : ($typeKey === 'model_field' ? 'database' : 'code') }} fa-2x me-3 text-primary"></i>
                                                <div>
                                                    <strong class="d-block mb-0">{{ $typeLabel }}</strong>
                                                    <p class="text-muted mb-0">
                                                        @if($typeKey === 'sale_type_match')
                                                            Mapea tipos de venta del producto
                                                        @elseif($typeKey === 'model_field')
                                                            Valida campos del modelo
                                                        @else
                                                            Expresiones y lógica personalizada
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('condition_type')
                        <div class="text-danger mt-2">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Información Básica -->
                <div class="border-top pt-4 mt-4">
                    <h6 class="mb-0">
                        Información básica
                    </h6>
                    <small class="text-muted d-block mb-3">Identificadores y datos principales que definen esta condición en el sistema</small>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    Clave <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('key') is-invalid @enderror"
                                       name="key"
                                       value="{{ old('key', $condition->key) }}"
                                       required
                                       placeholder="is_weapon, is_national, is_priority"
                                       pattern="[a-z0-9_\-]+"
                                       title="Solo letras minúsculas, números, guiones y guiones bajos">
                                @error('key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <small class="form-text text-muted">Identificador único (snake_case)</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name', $condition->name) }}"
                                       required
                                       placeholder="Es un arma, Cliente nacional, Pedido prioritario">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <small class="form-text text-muted">Nombre descriptivo para la interfaz</small>
                                @enderror
                            </div>
                        </div>


                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select select2 @error('is_active') is-invalid @enderror"
                                        name="is_active">
                                    <option value="1" {{ old('is_active', $condition->is_active) == '1' ? 'selected' : '' }}>
                                        Activa
                                    </option>
                                    <option value="0" {{ old('is_active', $condition->is_active) == '0' ? 'selected' : '' }}>
                                        Inactiva
                                    </option>
                                </select>
                                @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <small class="form-text text-muted">Solo las condiciones activas se evaluarán</small>
                                    @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Orden de visualización</label>
                                <input type="number"
                                       class="form-control @error('sort_order') is-invalid @enderror"
                                       name="sort_order"
                                       value="{{ old('sort_order', $condition->sort_order) }}"
                                       min="0"
                                       placeholder="0">
                                @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <small class="form-text text-muted">Orden en listados (menor primero)</small>
                                    @enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description"
                                          rows="2"
                                          placeholder="Describe cuándo se debe aplicar esta condición...">{{ old('description', $condition->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <small class="form-text text-muted">Explicación detallada (opcional)</small>
                                    @enderror
                            </div>
                        </div>


                    </div>
                </div>

                <hr>
                <!-- Campos Dinámicos según Tipo -->

                <!-- Sale Type Match Fields -->
                <div class="condition-fields border-top pt-4 mt-4" id="fields_sale_type_match" style="display: none;">
                    <h6 class="mb-0">
                        Configuración de etiquetas
                    </h6>
                    <small class="text-muted d-block mb-3">Define qué tipos de venta del producto deben cumplirse para aplicar esta condición</small>
                    <div class="mb-3">
                        <label class="form-label">
                            Etiquetas <span class="text-danger">*</span>
                        </label>
                        <select multiple
                                class="form-select select2 select2-tags"
                                id="sale_types"
                                name="sale_types[]"
                                data-placeholder="Seleccionar o agregar sale types...">
                            @foreach($validSaleTypes as $saleType)
                                <option value="{{ $saleType }}" {{ in_array($saleType, old('sale_types', $condition->sale_types ?? [])) ? 'selected' : '' }}>
                                    {{ $saleType }}
                                </option>
                            @endforeach
                            @if(!empty($condition->sale_types))
                                @foreach($condition->sale_types as $conditionSaleType)
                                    @if(!in_array($conditionSaleType, $validSaleTypes))
                                        <option value="{{ $conditionSaleType }}" selected>{{ $conditionSaleType }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                        <small class="form-text text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Tipos de venta que deben coincidir con <code>blockade_type</code> en <code>document_product_blockades</code>
                        </small>
                    </div>
                </div>

                <!-- Model Field Fields -->
                <div class="condition-fields border-top pt-4 mt-4" id="fields_model_field" style="display: none;">
                    <h6 class="mb-0">
                        Configuración de campo del modelo
                    </h6>
                    <small class="text-muted d-block mb-3">Valida condiciones basadas en campos específicos del modelo y sus valores esperados</small>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    Campo del modelo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       name="model_field"
                                       value="{{ old('model_field', $condition->model_field) }}"
                                       placeholder="customer.country_id, order.total">
                                <small class="form-text text-muted">
                                    Usa notación de punto para relaciones: <code>customer.country_id</code>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    Valor(es) esperado(s) <span class="text-danger">*</span>
                                </label>
                                @php
                                    // Try to get old value from different sources
                                    $expectedValueText = old('expected_value_text') ?: old('_expected_value_text');

                                    // If still empty, try from the expected_value array that was submitted
                                    if (!$expectedValueText && old('expected_value')) {
                                        $expectedValueText = is_array(old('expected_value'))
                                            ? implode(', ', old('expected_value'))
                                            : old('expected_value');
                                    }

                                    // Finally, fall back to the model's value
                                    if (!$expectedValueText && !empty($condition->expected_value)) {
                                        $expectedValueText = is_array($condition->expected_value)
                                            ? implode(', ', $condition->expected_value)
                                            : $condition->expected_value;
                                    }
                                @endphp
                                <input type="text"
                                       class="form-control"
                                       name="expected_value_text"
                                       value="{{ $expectedValueText }}"
                                       placeholder="1, 2, 3 (separados por coma)">
                                <small class="form-text text-muted">
                                    Valores separados por coma para comparación
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Ejemplo:</strong> Campo: <code>customer.country_id</code>, Valor: <code>1</code> → valida si el país del cliente es España (ID 1)
                    </div>
                </div>

                <!-- Custom Expression Fields -->
                <div class="condition-fields border-top pt-4 mt-4" id="fields_custom_expression" style="display: none;">
                    <h6 class="mb-0">
                        Configuración de expresión personalizada
                    </h6>
                    <small class="text-muted d-block mb-3">Crea expresiones avanzadas con lógica personalizada para validaciones complejas</small>
                    <div class="mb-3">
                        <label class="form-label">
                            Expresión de validación <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control font-monospace"
                                  name="validation_expression"
                                  rows="4"
                                  placeholder="order_total > 1000&#10;customer.vip == true&#10;product_count >= 5">{{ old('validation_expression', $condition->validation_expression) }}</textarea>
                        <small class="form-text text-muted">
                            Expresión que retorna verdadero/falso
                        </small>
                    </div>
                    <div class="alert bg-light mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Avanzado:</strong> Esta expresión se evaluará en el contexto del documento. Asegúrate de que la lógica sea correcta.
                    </div>
                </div>


            </div>

            <div class="card-body border-top">
                <button type="submit" class="btn btn-info px-4 w-100 mb-1">
                    Guardar
                </button>
                <a href="{{ route('settings.documents.conditions.index') }}" class="btn btn-light px-4 w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<style>
/* Ocultar radio button visualmente pero mantenerlo funcional para accesibilidad */
.condition-type-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Estilos de las tarjetas de selección */
.condition-type-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
}

.condition-type-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #90bb13;
}

.condition-type-card.active {
    border-color: #90bb13 !important;
    background-color: rgba(144, 187, 19, 0.05);
    box-shadow: 0 2px 8px rgba(144, 187, 19, 0.2);
}

.condition-type-label {
    cursor: pointer;
    margin: 0;
    display: block;
    width: 100%;
}
</style>

<script>
$(document).ready(function() {
    // Initialize Select2 for sale types with tags
    $('.select2-tags').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Seleccionar o agregar sale types...',
        allowClear: true
    });

    // Handle condition type selection
    function showConditionFields(type) {
        // Hide all condition fields
        $('.condition-fields').hide();

        // Show selected type fields
        $('#fields_' + type).show();

        // Update card styling
        $('.condition-type-card').removeClass('active');
        $('.condition-type-card[data-type="' + type + '"]').addClass('active');

        // Enable/disable required fields based on type
        updateRequiredFields(type);
    }

    function updateRequiredFields(type) {
        // Remove all required from dynamic fields first
        $('#sale_types, input[name="model_field"], input[name="expected_value_text"], textarea[name="validation_expression"]')
            .removeAttr('required');

        // Add required to relevant fields
        if (type === 'sale_type_match') {
            $('#sale_types').attr('required', 'required');
        } else if (type === 'model_field') {
            $('input[name="model_field"], input[name="expected_value_text"]').attr('required', 'required');
        } else if (type === 'custom_expression') {
            $('textarea[name="validation_expression"]').attr('required', 'required');
        }
    }

    // Handle radio button change
    $('input[name="condition_type"]').on('change', function() {
        showConditionFields($(this).val());
    });

    // Handle card click
    $('.condition-type-card').on('click', function() {
        const radio = $(this).find('input[type="radio"]');
        radio.prop('checked', true).trigger('change');
    });

    // Initialize on page load
    const initialType = $('input[name="condition_type"]:checked').val() || 'sale_type_match';
    showConditionFields(initialType);

    // Handle form submission - convert expected_value_text to array
    $('#formValidationCondition').on('submit', function(e) {
        const type = $('input[name="condition_type"]:checked').val();

        if (type === 'model_field') {
            const expectedValueText = $('input[name="expected_value_text"]').val();
            if (expectedValueText) {
                // Remove the text input from submission
                $('input[name="expected_value_text"]').attr('name', '_expected_value_text');

                // Create hidden inputs for each value
                const values = expectedValueText.split(',').map(v => v.trim()).filter(v => v);
                values.forEach(value => {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'expected_value[]',
                        value: value
                    }).appendTo('#formValidationCondition');
                });
            }
        }
    });
});
</script>
@endpush
