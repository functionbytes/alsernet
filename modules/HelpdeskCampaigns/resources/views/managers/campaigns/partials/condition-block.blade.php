{{-- Condition Block Partial --}}
@php
    $conditionField = $condition['field'] ?? '';
    $conditionOperator = $condition['operator'] ?? 'equals';
    $conditionValue = $condition['value'] ?? '';
    $conditionIndex = $index ?? 0;
@endphp

<div class="card mb-2 condition-block">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Campo</label>
                <select class="form-select form-select-sm condition-field"
                        name="conditions[{{ $conditionIndex }}][field]"
                        onchange="updateConditionOperators(this)">
                    <option value="">Seleccionar...</option>
                    <optgroup label="Visitante">
                        <option value="visitor_type" {{ $conditionField === 'visitor_type' ? 'selected' : '' }}>
                            Tipo de Visitante
                        </option>
                        <option value="visit_count" {{ $conditionField === 'visit_count' ? 'selected' : '' }}>
                            Número de Visitas
                        </option>
                        <option value="time_on_site" {{ $conditionField === 'time_on_site' ? 'selected' : '' }}>
                            Tiempo en Sitio
                        </option>
                        <option value="pages_visited" {{ $conditionField === 'pages_visited' ? 'selected' : '' }}>
                            Páginas Visitadas
                        </option>
                    </optgroup>
                    <optgroup label="Página">
                        <option value="current_url" {{ $conditionField === 'current_url' ? 'selected' : '' }}>
                            URL Actual
                        </option>
                        <option value="referrer" {{ $conditionField === 'referrer' ? 'selected' : '' }}>
                            Referrer (de dónde viene)
                        </option>
                        <option value="device_type" {{ $conditionField === 'device_type' ? 'selected' : '' }}>
                            Tipo de Dispositivo
                        </option>
                    </optgroup>
                    <optgroup label="Comportamiento">
                        <option value="exit_intent" {{ $conditionField === 'exit_intent' ? 'selected' : '' }}>
                            Intención de Salida
                        </option>
                        <option value="scroll_depth" {{ $conditionField === 'scroll_depth' ? 'selected' : '' }}>
                            Profundidad de Scroll
                        </option>
                        <option value="idle_time" {{ $conditionField === 'idle_time' ? 'selected' : '' }}>
                            Tiempo Inactivo
                        </option>
                    </optgroup>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1">Operador</label>
                <select class="form-select form-select-sm condition-operator"
                        name="conditions[{{ $conditionIndex }}][operator]">
                    <option value="equals" {{ $conditionOperator === 'equals' ? 'selected' : '' }}>
                        Es igual a
                    </option>
                    <option value="not_equals" {{ $conditionOperator === 'not_equals' ? 'selected' : '' }}>
                        No es igual a
                    </option>
                    <option value="contains" {{ $conditionOperator === 'contains' ? 'selected' : '' }}>
                        Contiene
                    </option>
                    <option value="not_contains" {{ $conditionOperator === 'not_contains' ? 'selected' : '' }}>
                        No contiene
                    </option>
                    <option value="greater_than" {{ $conditionOperator === 'greater_than' ? 'selected' : '' }}>
                        Mayor que
                    </option>
                    <option value="less_than" {{ $conditionOperator === 'less_than' ? 'selected' : '' }}>
                        Menor que
                    </option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small mb-1">Valor</label>
                <input type="text"
                       class="form-control form-control-sm condition-value"
                       name="conditions[{{ $conditionIndex }}][value]"
                       value="{{ $conditionValue }}"
                       placeholder="Valor a comparar">
            </div>

            <div class="col-md-1">
                <button type="button"
                        class="btn btn-sm btn-light-danger w-100"
                        onclick="removeCondition(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>
