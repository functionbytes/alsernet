@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Reglas de Devolución</h1>
            <div>
                <a href="{{ route('admin.return-rules.export') }}" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> Exportar
                </a>
                <a href="{{ route('admin.return-rules.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Regla
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.return-rules.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="rule_type" class="form-control">
                                <option value="">Todos los tipos</option>
                                <option value="global" {{ request('rule_type') === 'global' ? 'selected' : '' }}>Global</option>
                                <option value="category" {{ request('rule_type') === 'category' ? 'selected' : '' }}>Categoría</option>
                                <option value="product" {{ request('rule_type') === 'product' ? 'selected' : '' }}>Producto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="category_id" class="form-control">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="is_active" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                            <a href="{{ route('admin.return-rules.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de reglas -->
        <div class="card">
            <div class="card-body">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Prioridad</th>
                                <th>Tipo</th>
                                <th>Alcance</th>
                                <th>Retornable</th>
                                <th>Período</th>
                                <th>Max %</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rules as $rule)
                                <tr class="{{ !$rule->is_active ? 'table-secondary' : '' }}">
                                    <td>
                                    <span class="badge badge-{{ $rule->priority > 50 ? 'danger' : ($rule->priority > 20 ? 'warning' : 'secondary') }}">
                                        {{ $rule->priority }}
                                    </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($rule->rule_type) }}</span>
                                    </td>
                                    <td>
                                        @if($rule->rule_type === 'category' && $rule->category)
                                            <i class="fas fa-folder"></i> {{ $rule->category->name }}
                                        @elseif($rule->rule_type === 'product' && $rule->product)
                                            <i class="fas fa-box"></i> {{ Str::limit($rule->product->name, 30) }}
                                        @else
                                            <i class="fas fa-globe"></i> Global
                                        @endif
                                    </td>
                                    <td>
                                        @if($rule->is_returnable)
                                            <span class="badge badge-success">Sí</span>
                                        @else
                                            <span class="badge badge-danger">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($rule->return_period_days)
                                            {{ $rule->return_period_days }} días
                                        @else
                                            <span class="text-muted">Sin límite</span>
                                        @endif
                                    </td>
                                    <td>{{ $rule->max_return_percentage }}%</td>
                                    <td>
                                        @if($rule->is_active)
                                            <span class="badge badge-success">Activa</span>
                                        @else
                                            <span class="badge badge-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.return-rules.show', $rule) }}"
                                               class="btn btn-sm btn-outline-primary" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('admin.return-rules.edit', $rule) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <button class="btn btn-sm btn-outline-info test-rule"
                                                    data-rule-id="{{ $rule->id }}" title="Probar">
                                                <i class="fas fa-vial"></i>
                                            </button>

                                            <form method="POST" action="{{ route('admin.return-rules.toggle', $rule) }}"
                                                  style="display: inline;">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-{{ $rule->is_active ? 'warning' : 'success' }}"
                                                        title="{{ $rule->is_active ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fas fa-{{ $rule->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>

                                            <a href="{{ route('admin.return-rules.clone', $rule) }}"
                                               class="btn btn-sm btn-outline-dark" title="Clonar">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $rules->links() }}
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-rules fa-3x text-muted mb-3"></i>
                        <h5>No hay reglas de devolución</h5>
                        <p class="text-muted">Crea tu primera regla de devolución usando el botón "Nueva Regla".</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para probar reglas -->
    <div class="modal fade" id="testRuleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Probar Regla de Devolución</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="testRuleForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de compra</label>
                                    <input type="date" name="purchase_date" class="form-control" value="{{ now()->subDays(15)->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" name="quantity" class="form-control" value="1" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="has_original_packaging" class="form-check-input" id="hasPackaging" checked>
                                    <label class="form-check-label" for="hasPackaging">Tiene empaque original</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="has_receipt" class="form-check-input" id="hasReceipt" checked>
                                    <label class="form-check-label" for="hasReceipt">Tiene recibo</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label>Razón de devolución</label>
                            <select name="reason" class="form-control">
                                <option value="defective">Defectuoso</option>
                                <option value="wrong_item">Artículo incorrecto</option>
                                <option value="not_as_described">No como se describía</option>
                                <option value="changed_mind">Cambié de opinión</option>
                            </select>
                        </div>
                    </form>

                    <div id="testResults" class="mt-4" style="display: none;">
                        <h6>Resultados de la prueba:</h6>
                        <div id="testResultsContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="runTest">Ejecutar Prueba</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let currentRuleId = null;

                $('.test-rule').click(function() {
                    currentRuleId = $(this).data('rule-id');
                    $('#testRuleModal').modal('show');
                    $('#testResults').hide();
                });

                $('#runTest').click(function() {
                    const formData = new FormData($('#testRuleForm')[0]);
                    const testData = {};

                    for (let [key, value] of formData.entries()) {
                        if (key.includes('checkbox')) {
                            testData[key] = $('#testRuleForm input[name="' + key + '"]').is(':checked');
                        } else {
                            testData[key] = value;
                        }
                    }

                    $.ajax({
                        url: `/admin/return-rules/${currentRuleId}/test`,
                        method: 'POST',
                        data: {
                            test_data: testData,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            let html = '';

                            if (response.results.valid) {
                                html += '<div class="alert alert-success"><i class="fas fa-check"></i> Devolución VÁLIDA</div>';
                            } else {
                                html += '<div class="alert alert-danger"><i class="fas fa-times"></i> Devolución INVÁLIDA</div>';
                            }

                            if (response.results.errors && response.results.errors.length > 0) {
                                html += '<div class="alert alert-danger"><strong>Errores:</strong><ul class="mb-0">';
                                response.results.errors.forEach(error => {
                                    html += `<li>${error}</li>`;
                                });
                                html += '</ul></div>';
                            }

                            if (response.results.warnings && response.results.warnings.length > 0) {
                                html += '<div class="alert alert-warning"><strong>Advertencias:</strong><ul class="mb-0">';
                                response.results.warnings.forEach(warning => {
                                    html += `<li>${warning}</li>`;
                                });
                                html += '</ul></div>';
                            }

                            html += `<div class="alert alert-info"><strong>Descripción de la regla:</strong><br>${response.rule_description}</div>`;

                            $('#testResultsContent').html(html);
                            $('#testResults').show();
                        },
                        error: function() {
                            $('#testResultsContent').html('<div class="alert alert-danger">Error ejecutando la prueba</div>');
                            $('#testResults').show();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
