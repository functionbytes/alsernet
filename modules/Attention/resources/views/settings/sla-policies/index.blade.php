@extends('layouts.theme')

@section('title', 'Políticas SLA')

@section('page_header')
    @include('core::components.card', ['title' => 'Políticas SLA'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Políticas SLA</h5>
                        <p class="small mb-0 text-muted">Define los tiempos de respuesta, resolución y cierre para las peticiones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.attention.sla-policies.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva política
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $policies->count() }}</h4>
                                        <small class="text-muted">Políticas registradas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $policies->where('active', true)->count() }}</h4>
                                        <small class="text-muted">Políticas activas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Inactivas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $policies->where('active', false)->count() }}</h4>
                                        <small class="text-muted">Políticas inactivas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" id="sla-search" class="form-control -0 ps-0"
                                   placeholder="Buscar por nombre o descripción...">
                        </div>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <select id="sla-status-filter" class="form-select select2 h-100">
                            <option value="">Todos los estados</option>
                            <option value="activo">Activas</option>
                            <option value="inactivo">Inactivas</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($policies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="sla-table">
                            <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th>Nombre</th>
                                <th class="text-center">Respuesta</th>
                                <th class="text-center">Estado</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($policies as $policy)
                                <tr class="sla-row"
                                    data-name="{{ strtolower($policy->name) }}"
                                    data-desc="{{ strtolower($policy->description ?? '') }}"
                                    data-status="{{ $policy->active ? 'activo' : 'inactivo' }}">
                                    <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $policy->id }}"></td>
                                    <td>
                                        <div>
                                            <strong>{{ $policy->name }}</strong>
                                            @if($policy->is_default)
                                                <span class="badge bg-success-subtle text-primary ms-2">Por defecto</span>
                                            @endif
                                            @if($policy->description)
                                                <small class="d-block text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                            <span class="badge bg-info-subtle text-info">
                                                {{ round($policy->response_time / 60, 1) }}h
                                            </span>
                                    </td>
                                    <td class="text-center">
                                        @if($policy->active)
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                            <span class="badge bg-light text-black">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $policy->created_at->format('d/m/Y') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.attention.sla-policies.show', $policy->id) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.attention.sla-policies.edit', $policy->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item delete-btn"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#delete-modal"
                                                       data-url="{{ route('settings.attention.sla-policies.destroy', $policy->id) }}"
                                                       data-title="Eliminar política: {{ $policy->name }}">
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
                    <div id="sla-empty-search" class="text-center py-5" style="display: none;">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search fs-7"></i>
                            </div>
                            <h6 class="mb-1">No se encontraron políticas</h6>
                            <p class="text-muted mb-0">No hay resultados para los criterios de búsqueda</p>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-shield-alt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay políticas SLA</h6>
                            <p class="text-muted mb-3">Crea tu primera política para gestionar los tiempos de atención</p>
                            <a href="{{ route('settings.attention.sla-policies.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Nueva política
                            </a>
                        </div>
                    </div>
                @endif
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> elemento(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            @if(session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
            @endif
            @if(session('error'))
            toastr.error('{{ session('error') }}', 'Error');
            @endif

            // Delete modal
            $('.delete-btn').on('click', function () {
                $('#delete-modal .modal-title').text($(this).data('title'));
                $('#delete-form').attr('action', $(this).data('url'));
            });

            // Client-side filter
            function applyFilters() {
                const search = $('#sla-search').val().toLowerCase();
                const status = $('#sla-status-filter').val();
                let visible = 0;

                $('.sla-row').each(function () {
                    const matchSearch = !search || $(this).data('name').includes(search) || $(this).data('desc').includes(search);
                    const matchStatus = !status || $(this).data('status') === status;
                    const show = matchSearch && matchStatus;
                    $(this).toggle(show);
                    if (show) visible++;
                });

                $('#sla-empty-search').toggle(visible === 0 && $('.sla-row').length > 0);
            }

            $('#sla-search').on('input', applyFilters);
            $('#sla-status-filter').on('change', applyFilters);

            const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

            $('#bulk-modal').on('hide.bs.modal', function () {
                $('#bulk-action-select').val('');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                bulk.reset();
            });

            $('#bulk-apply-btn').on('click', function () {
                const action = $('#bulk-action-select').val();
                const ids    = bulk.getIds();

                if (!action) { toastr.warning('Selecciona una acción.'); return; }
                if (!ids.length) { toastr.warning('Selecciona al menos un elemento.'); return; }

                $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: '{{ route('settings.attention.sla-policies.bulk-action') }}',
                    method: 'POST',
                    data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        $('#bulk-modal').modal('hide');
                        toastr.success(res.count + ' elemento(s) actualizados.');
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
