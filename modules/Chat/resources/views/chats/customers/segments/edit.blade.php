@extends('layouts.theme')

@section('title', 'Editar Segmento')

@section('content')

    @include('core::components.card', ['title' => 'Editar Segmento'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <form id="segmentForm" method="POST" action="{{ route('chat.customers.segments.update', $segment->id) }}">
            @csrf
            @method('PUT')

            <!-- Main Card -->
            <div class="card">
                <!-- Header Section -->
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Editar segmento</h5>
                            <p class="small mb-0 text-muted">Modifica los criterios y configuración del segmento</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('chat.customers.segments.show', $segment->id) }}" class="btn btn-light">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                            <form method="POST" action="{{ route('chat.customers.segments.destroy', $segment->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('¿Eliminar este segmento? Esta acción no se puede deshacer.')">
                                    <i class="fas fa-trash me-1"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card-body border-bottom">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-info-circle me-2"></i>Información básica
                    </h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">
                                Nombre del segmento <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Ej: Clientes VIP activos"
                                   value="{{ old('name', $segment->name) }}"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Un nombre descriptivo para identificar el segmento</small>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label fw-semibold">
                                Estado
                            </label>
                            <select id="is_active"
                                    name="is_active"
                                    class="form-control select2 @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $segment->is_active) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('is_active', $segment->is_active) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Activar o desactivar el segmento</small>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">
                                Descripción
                            </label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Describe el propósito de este segmento...">{{ old('description', $segment->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Opcional: Explica qué tipo de clientes agrupa este segmento</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Filter Builder Section -->
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fas fa-filter me-2"></i>Criterios de segmentación
                    </h6>

                    <div class="alert alert-info border-0 bg-info-subtle mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-lightbulb mt-1"></i>
                            <div>
                                <small class="fw-semibold">Consejo:</small>
                                <small class="d-block">Agrega múltiples filtros para crear segmentos más específicos. Los clientes deben cumplir TODOS los criterios para ser incluidos.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Builder Container -->
                    <div id="filterBuilder">
                        <!-- Filters will be added here dynamically -->
                    </div>

                    <button type="button" class="btn btn-outline-primary mt-3" id="addFilterBtn">
                        <i class="fas fa-plus"></i> Agregar filtro
                    </button>

                    <!-- Hidden input to store filters JSON -->
                    <input type="hidden" name="criteria" id="criteriaInput" value="{{ old('criteria', json_encode($segment->criteria ?? [])) }}">
                    @error('criteria')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Preview Section -->
                <div class="card-body border-top">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-eye me-2"></i>Vista previa
                    </h6>

                    <div class="alert alert-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Clientes que cumplen los criterios:</strong>
                                <span id="previewCount" class="badge bg-primary ms-2">{{ $segment->customers_count ?? 0 }}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" id="previewBtn">
                                <i class="fas fa-sync me-1"></i> Actualizar vista previa
                            </button>
                        </div>
                    </div>

                    <div id="previewResults" class="d-none mt-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Email</th>
                                        <th>País</th>
                                        <th>Última actividad</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                    <!-- Preview results will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Metadata Section -->
                <div class="card-body border-top bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <strong>Creado:</strong> {{ $segment->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <strong>Última actualización:</strong> {{ $segment->updated_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('chat.customers.segments.show', $segment->id) }}" class="btn btn-light">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Filter builder configuration
    const filterOptions = {
        'email': {
            label: 'Email',
            operators: ['contains', 'not_contains', 'equals', 'not_equals', 'ends_with', 'starts_with'],
            type: 'text'
        },
        'name': {
            label: 'Nombre',
            operators: ['contains', 'not_contains', 'equals', 'not_equals'],
            type: 'text'
        },
        'country': {
            label: 'País',
            operators: ['equals', 'not_equals', 'in', 'not_in'],
            type: 'text'
        },
        'language': {
            label: 'Idioma',
            operators: ['equals', 'not_equals', 'in', 'not_in'],
            type: 'text'
        },
        'is_banned': {
            label: 'Estado suspensión',
            operators: ['equals'],
            type: 'boolean'
        },
        'email_verified_at': {
            label: 'Email verificado',
            operators: ['is_null', 'is_not_null'],
            type: 'boolean'
        },
        'created_at': {
            label: 'Fecha de registro',
            operators: ['greater_than', 'less_than', 'between', 'equals'],
            type: 'date'
        },
        'last_seen_at': {
            label: 'Última actividad',
            operators: ['greater_than', 'less_than', 'between', 'is_null', 'is_not_null'],
            type: 'date'
        },
        'conversations_count': {
            label: 'Número de conversaciones',
            operators: ['equals', 'not_equals', 'greater_than', 'less_than', 'between'],
            type: 'number'
        }
    };

    const operatorLabels = {
        'equals': 'es igual a',
        'not_equals': 'no es igual a',
        'contains': 'contiene',
        'not_contains': 'no contiene',
        'starts_with': 'empieza con',
        'ends_with': 'termina con',
        'greater_than': 'mayor que',
        'less_than': 'menor que',
        'between': 'entre',
        'in': 'está en',
        'not_in': 'no está en',
        'is_null': 'está vacío',
        'is_not_null': 'no está vacío'
    };

    let filterCount = 0;
    let filters = [];

    // Load existing filters
    try {
        const existingCriteria = $('#criteriaInput').val();
        if (existingCriteria && existingCriteria !== '[]') {
            filters = JSON.parse(existingCriteria);
            filters.forEach(filter => addFilterRow(filter));
        }
    } catch (e) {
        console.error('Error loading existing criteria:', e);
    }

    // If no filters loaded, show message
    if (filterCount === 0) {
        $('#filterBuilder').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No hay filtros configurados. Agrega al menos un filtro para definir el segmento.
            </div>
        `);
    }

    // Add filter button
    $('#addFilterBtn').on('click', function() {
        // Remove empty state message if exists
        $('#filterBuilder .alert-warning').remove();
        addFilterRow();
    });

    // Add filter row
    function addFilterRow(filterData = null) {
        filterCount++;
        const filterId = `filter_${filterCount}`;

        const filterHtml = `
            <div class="filter-row card mb-3" data-filter-id="${filterId}">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Campo</label>
                            <select class="form-control select2 filter-field" data-filter-id="${filterId}">
                                <option value="">Seleccionar campo...</option>
                                ${Object.keys(filterOptions).map(key =>
                                    `<option value="${key}" ${filterData?.field === key ? 'selected' : ''}>${filterOptions[key].label}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Operador</label>
                            <select class="form-control select2 filter-operator" data-filter-id="${filterId}">
                                <option value="">Seleccionar operador...</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Valor</label>
                            <div class="filter-value-container" data-filter-id="${filterId}">
                                <input type="text" class="form-control filter-value" placeholder="Ingrese el valor...">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger w-100 remove-filter" data-filter-id="${filterId}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#filterBuilder').append(filterHtml);

        // If filter data exists, populate operators and value
        if (filterData) {
            updateOperators(filterId, filterData.field);
            $(`.filter-operator[data-filter-id="${filterId}"]`).val(filterData.operator);
            updateValueField(filterId, filterData.field, filterData.operator);

            // Set value based on type
            if (filterData.operator === 'between') {
                $(`.filter-value-from[data-filter-id="${filterId}"]`).val(filterData.value.from);
                $(`.filter-value-to[data-filter-id="${filterId}"]`).val(filterData.value.to);
            } else {
                $(`.filter-value[data-filter-id="${filterId}"]`).val(filterData.value);
            }
        }

        updateFiltersJSON();
    }

    // Field change handler
    $(document).on('change', '.filter-field', function() {
        const filterId = $(this).data('filter-id');
        const field = $(this).val();
        updateOperators(filterId, field);
        updateFiltersJSON();
    });

    // Operator change handler
    $(document).on('change', '.filter-operator', function() {
        const filterId = $(this).data('filter-id');
        const field = $(`.filter-field[data-filter-id="${filterId}"]`).val();
        const operator = $(this).val();
        updateValueField(filterId, field, operator);
        updateFiltersJSON();
    });

    // Value change handler
    $(document).on('change keyup', '.filter-value, .filter-value-from, .filter-value-to', function() {
        updateFiltersJSON();
    });

    // Remove filter handler
    $(document).on('click', '.remove-filter', function() {
        const filterId = $(this).data('filter-id');
        $(`.filter-row[data-filter-id="${filterId}"]`).remove();
        updateFiltersJSON();

        // Show message if no filters remain
        if ($('.filter-row').length === 0) {
            $('#filterBuilder').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No hay filtros configurados. Agrega al menos un filtro para definir el segmento.
                </div>
            `);
        }
    });

    // Update operators based on field
    function updateOperators(filterId, field) {
        if (!field) return;

        const operators = filterOptions[field].operators;
        const operatorSelect = $(`.filter-operator[data-filter-id="${filterId}"]`);

        operatorSelect.empty();
        operatorSelect.append('<option value="">Seleccionar operador...</option>');

        operators.forEach(op => {
            operatorSelect.append(`<option value="${op}">${operatorLabels[op]}</option>`);
        });

        const operator = operatorSelect.val();
        updateValueField(filterId, field, operator);
    }

    // Update value field based on field type and operator
    function updateValueField(filterId, field, operator) {
        if (!field || !operator) return;

        const fieldType = filterOptions[field].type;
        const container = $(`.filter-value-container[data-filter-id="${filterId}"]`);

        // Clear container
        container.empty();

        // No value needed for is_null and is_not_null
        if (operator === 'is_null' || operator === 'is_not_null') {
            container.append('<input type="text" class="form-control filter-value" value="N/A" readonly>');
            return;
        }

        // Between operator needs two inputs
        if (operator === 'between') {
            container.append(`
                <div class="input-group">
                    <input type="${fieldType === 'date' ? 'date' : fieldType === 'number' ? 'number' : 'text'}"
                           class="form-control filter-value-from"
                           data-filter-id="${filterId}"
                           placeholder="Desde...">
                    <span class="input-group-text">hasta</span>
                    <input type="${fieldType === 'date' ? 'date' : fieldType === 'number' ? 'number' : 'text'}"
                           class="form-control filter-value-to"
                           data-filter-id="${filterId}"
                           placeholder="Hasta...">
                </div>
            `);
            return;
        }

        // Boolean field
        if (fieldType === 'boolean') {
            container.append(`
                <select class="form-control select2 filter-value">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            `);
            return;
        }

        // Date field
        if (fieldType === 'date') {
            container.append('<input type="date" class="form-control filter-value" placeholder="Seleccione fecha...">');
            return;
        }

        // Number field
        if (fieldType === 'number') {
            container.append('<input type="number" class="form-control filter-value" placeholder="Ingrese número...">');
            return;
        }

        // Default text input
        container.append('<input type="text" class="form-control filter-value" placeholder="Ingrese el valor...">');
    }

    // Update filters JSON
    function updateFiltersJSON() {
        const filtersArray = [];

        $('.filter-row').each(function() {
            const filterId = $(this).data('filter-id');
            const field = $(`.filter-field[data-filter-id="${filterId}"]`).val();
            const operator = $(`.filter-operator[data-filter-id="${filterId}"]`).val();

            if (!field || !operator) return;

            let value;

            if (operator === 'between') {
                value = {
                    from: $(`.filter-value-from[data-filter-id="${filterId}"]`).val(),
                    to: $(`.filter-value-to[data-filter-id="${filterId}"]`).val()
                };
            } else if (operator === 'is_null' || operator === 'is_not_null') {
                value = null;
            } else {
                value = $(`.filter-value[data-filter-id="${filterId}"]`).val();
            }

            filtersArray.push({ field, operator, value });
        });

        $('#criteriaInput').val(JSON.stringify(filtersArray));
    }

    // Preview button
    $('#previewBtn').on('click', function() {
        const criteria = $('#criteriaInput').val();

        if (!criteria || criteria === '[]') {
            toastr.warning('Agrega al menos un filtro para ver la vista previa', 'Advertencia');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');

        axios.post('{{ route('chat.customers.segments.preview') }}', {
            criteria: JSON.parse(criteria)
        })
        .then(response => {
            const customers = response.data.customers;
            const count = response.data.count;

            $('#previewCount').text(count);

            if (count > 0) {
                let html = '';
                customers.forEach(customer => {
                    html += `
                        <tr>
                            <td>${customer.name}</td>
                            <td>${customer.email}</td>
                            <td>${customer.country || '-'}</td>
                            <td>${customer.last_seen_at || '-'}</td>
                        </tr>
                    `;
                });
                $('#previewTableBody').html(html);
                $('#previewResults').removeClass('d-none');
            } else {
                $('#previewTableBody').html('<tr><td colspan="4" class="text-center text-muted">No se encontraron clientes que cumplan los criterios</td></tr>');
                $('#previewResults').removeClass('d-none');
            }

            toastr.success('Vista previa actualizada', 'Éxito');
        })
        .catch(error => {
            const message = error.response?.data?.message || 'Error al cargar la vista previa';
            toastr.error(message, 'Error');
        })
        .finally(() => {
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-sync me-1"></i> Actualizar vista previa');
        });
    });

    // Form validation
    $('#segmentForm').on('submit', function(e) {
        const criteria = $('#criteriaInput').val();

        if (!criteria || criteria === '[]') {
            e.preventDefault();
            toastr.error('Debes agregar al menos un filtro', 'Error de validación');
            return false;
        }

        return true;
    });
});
</script>
@endpush
