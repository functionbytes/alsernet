@extends('layouts.theme')

@section('title', 'Registro de cambios')

@section('page_header')
    @include('core::components.card', ['title' => 'Registro de cambios'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Registro de cambios</h5>
                        <p class="small mb-0 text-muted">Historial completo de acciones realizadas sobre los modelos del sistema</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('activity.export', request()->query()) }}">Exportar CSV</a>
                                <div class="dropdown-divider"></div>
                                <button id="refresh-stats-btn" type="button" class="dropdown-item">
                                    <i class="fas fa-arrows-rotate me-1"></i> Refrescar stats
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total registros</h6>
                                <h4 class="mb-1 fw-bold" data-stat="total">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">En el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Creaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="created">{{ number_format($stats['created']) }}</h4>
                                <small class="text-muted">Registros creados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Actualizaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="updated">{{ number_format($stats['updated']) }}</h4>
                                <small class="text-muted">Registros modificados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Eliminaciones</h6>
                                <h4 class="mb-1 fw-bold" data-stat="deleted">{{ number_format($stats['deleted']) }}</h4>
                                <small class="text-muted">Registros eliminados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search & Filters --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('activity.logs') }}" id="logs-filter-form">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar en descripción..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="from" class="form-control h-100" value="{{ request('from') }}">
                        </div>
                        <div class="flex-shrink-0" style="min-width: 150px;">
                            <input type="date" name="to" class="form-control h-100" value="{{ request('to') }}">
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request()->hasAny(['search', 'from', 'to']))
                                <a href="{{ route('activity.logs') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($activities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Modelo</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $activity->id }}"></td>
                                        <td>
                                            <div class="small fw-semibold">{{ $activity->causer?->name ?? 'Sistema' }}</div>
                                            @if($activity->causer?->email)
                                                <small class="text-muted">{{ $activity->causer->email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $eventMap = ['created' => 'success', 'updated' => 'primary', 'deleted' => 'danger'];
                                                $color = $eventMap[$activity->event] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                {{ $activity->event ?? 'n/a' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ class_basename($activity->subject_type ?? '') ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <div class="small">{{ $activity->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('activity.logs.show', $activity->id) }}">
                                                            Ver detalle
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
                            <h6 class="mb-1">
                                @if(request()->hasAny(['search', 'from', 'to']))
                                    No se encontraron resultados
                                @else
                                    No hay registros de cambios
                                @endif
                            </h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No hay resultados para "{{ request('search') }}"
                                @else
                                    Aún no se han registrado acciones en el sistema
                                @endif
                            </p>
                            @if(request()->hasAny(['search', 'from', 'to']))
                                <a href="{{ route('activity.logs') }}" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($activities->hasPages())
                <div class="card-footer">{{ $activities->links() }}</div>
            @endif

        </div>
    </div>

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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> registro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Bulk actions
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    // Refresh stats
    $('#refresh-stats-btn').on('click', function () {
        const $btn  = $(this);
        const $icon = $btn.find('i');
        $btn.prop('disabled', true);
        $icon.addClass('fa-spin');

        $.getJSON('{{ route('activity.logs.stats') }}')
            .done(function (data) {
                $('[data-stat="total"]').text(new Intl.NumberFormat().format(data.total));
                $('[data-stat="created"]').text(new Intl.NumberFormat().format(data.created));
                $('[data-stat="updated"]').text(new Intl.NumberFormat().format(data.updated));
                $('[data-stat="deleted"]').text(new Intl.NumberFormat().format(data.deleted));
                toastr.success('Stats actualizados');
            })
            .fail(function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al refrescar.');
            })
            .always(function () {
                $btn.prop('disabled', false);
                $icon.removeClass('fa-spin');
            });
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' registro(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('activity.logs.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: action, ids: ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' registro(s) eliminados.');
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
