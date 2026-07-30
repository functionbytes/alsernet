@extends('layouts.theme')

@section('title', 'Prompts de IA')

@section('page_header')
    @include('core::components.card', ['title' => 'Prompts de IA'])
@endsection

@section('content')
    <div class="widget-content">

        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Prompts de IA</h5>
                        <p class="small mb-0 text-muted">Gestiona plantillas de prompts para generación de contenido con IA</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if($filterSupplier)
                            <a href="{{ route('settings.suppliers.detail', $filterSupplier->uid) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                            </a>
                        @endif
                        <a href="{{ route('settings.suppliers.prompts.create') }}{{ request('supplier_id') ? '?supplier_id='.request('supplier_id').(request('category_id') ? '&category_id='.request('category_id') : '') : '' }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>

            @if($filterSupplier || $filterCategory)
            <div class="card-body border-bottom py-2 bg-light-secondary">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="fas fa-filter text-primary"></i>
                    <span class="text-muted">Filtrando por:</span>
                    @if($filterSupplier)
                        <span class="badge bg-primary-subtle text-primary">
                            <i class="fas fa-building me-1"></i>{{ $filterSupplier->label }}
                        </span>
                    @endif
                    @if($filterCategory)
                        <span class="badge bg-info-subtle text-info">
                            <i class="fas fa-tag me-1"></i>{{ $filterCategory->name }}
                        </span>
                    @endif
                </div>
            </div>
            @endif

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.suppliers.prompts.index') }}">
                    @if(request('supplier_id'))
                        <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">
                    @endif
                    @if(request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre..."
                               value="{{ request('search') }}">
                        <div style="width: 180px; flex-shrink: 0;">
                            <select class="form-control" name="status">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                        <div style="width: 180px; flex-shrink: 0;">
                            <select class="form-control" name="scope">
                                <option value="">Todos los alcances</option>
                                <option value="global" {{ request('scope') === 'global' ? 'selected' : '' }}>Global</option>
                                <option value="supplier" {{ request('scope') === 'supplier' ? 'selected' : '' }}>Proveedor</option>
                                <option value="category" {{ request('scope') === 'category' ? 'selected' : '' }}>Categoría</option>
                                <option value="supplier_category" {{ request('scope') === 'supplier_category' ? 'selected' : '' }}>Prov+Cat</option>
                            </select>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if(request('search') || request('status') || request('scope'))
                                <a href="{{ route('settings.suppliers.prompts.index', array_filter(['supplier_id' => request('supplier_id'), 'category_id' => request('category_id')])) }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Prompts List -->
            <div class="card-body">
                @if($prompts->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-circle-info me-2"></i>
                        Los prompts definen las plantillas de IA para generar contenido según alcance, tipo y tono
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th>Nombre</th>
                                <th>Alcance</th>
                                <th class="text-center">Prioridad</th>
                                <th class="text-center">Generados</th>
                                <th class="text-center">Aprobación</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($prompts as $prompt)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $prompt->uid }}"></td>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.suppliers.prompts.edit', $prompt->uid) }}" class="text-body fw-semibold text-decoration-none">{{ $prompt->label }}</a>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $scopeLabels = [
                                                'global' => ['label' => 'Global', 'color' => 'info'],
                                                'supplier' => ['label' => 'Proveedor', 'color' => 'info'],
                                                'category' => ['label' => 'Categoría', 'color' => 'info'],
                                                'supplier_category' => ['label' => 'Prov+Cat', 'color' => 'info'],
                                                'source' => ['label' => 'Fuente', 'color' => 'info']
                                            ];
                                            $scope = $scopeLabels[$prompt->scope] ?? ['label' => ucfirst($prompt->scope), 'color' => 'light'];
                                        @endphp
                                        <span class="badge bg-{{ $scope['color'] }}-subtle text-{{ $scope['color'] }}">{{ $scope['label'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $prompt->priority }}</span>
                                    </td>
                                    @php $pStats = $promptStats[$prompt->id] ?? ['total_generated' => 0, 'total_validated' => 0, 'approval_rate' => null]; @endphp
                                    <td class="text-center">
                                        @if($pStats['total_generated'] > 0)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $pStats['total_generated'] }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($pStats['approval_rate'] !== null)
                                            @php
                                                $rate = $pStats['approval_rate'];
                                                $badgeClass = $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}-subtle text-{{ $badgeClass }}">{{ $rate }}%</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($prompt->is_active)
                                            <span class="badge bg-success-subtle text-success">
                                                Activo
                                            </span>
                                        @else
                                            <span class="badge bg-info-subtle text-info">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.suppliers.prompts.edit', $prompt->uid) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('settings.suppliers.prompts.duplicate', $prompt->uid) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            Duplicar
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('settings.suppliers.prompts.toggle', $prompt->uid) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            @if($prompt->is_active)
                                                                Desactivar
                                                            @else
                                                                Activar
                                                            @endif
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a
                                                        class="dropdown-item delete-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#delete-modal"
                                                        data-url="{{ route('settings.suppliers.prompts.destroy', $prompt->uid) }}"
                                                        data-title="Eliminar prompt: {{ $prompt->label }}">
                                                        Eliminar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-wand-magic-sparkles fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay prompts para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados para "{{ request('search') }}"
                                @else
                                    Crea tu primer prompt para IA
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.suppliers.prompts.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer prompt
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($prompts->total() > 0)
                        <span class="text-muted small">
                            Mostrando {{ $prompts->firstItem() }}–{{ $prompts->lastItem() }} de {{ $prompts->total() }}
                        </span>
                        @endif
                        <form method="GET" action="{{ route('settings.suppliers.prompts.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $v)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label class="text-muted small mb-0">Por página:</label>
                            <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100] as $opt)
                                    <option value="{{ $opt }}" {{ request('per_page', 15) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                            </select>
                        </form>
                    </div>
                    @if($prompts->hasPages())
                    <nav>{{ $prompts->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> prompt(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="enable">Activar</option>
                            <option value="disable">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function() {
    // Delete modal functionality
    $('.delete-btn').on('click', function() {
        const deleteUrl = $(this).data('url');
        const deleteTitle = $(this).data('title');

        $('#delete-modal .modal-title').text(deleteTitle);
        $('#delete-form').attr('action', deleteUrl);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const uids   = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!uids.length) { toastr.warning('Selecciona al menos un prompt.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + uids.length + ' prompt(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("settings.suppliers.prompts.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({ action: action, uids: uids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
</script>
@endpush
