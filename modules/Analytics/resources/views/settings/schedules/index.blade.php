@extends('layouts.theme')

@section('title', 'Reportes programados')

@section('page_header')
    @include('core::components.card', [
    'title' => 'Reportes programados',
    'breadcrumbs' => [
    ['label' => 'Dashboard', 'url' => url('/home')],
    ['label' => 'Analytics', 'url' => ''],
    ['label' => 'Reportes programados', 'active' => true],
    ]
    ])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="widget-content searchable-container list">

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Reportes programados</h5>
                        <p class="small mb-0 text-muted">Gestiona los reportes de analytics enviados automáticamente por email</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.analytics.schedules.create') }}">Nuevo reporte</a>
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
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Reportes configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Enviándose automáticamente</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Pausados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Próximos 7 días</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['pending'] }}</h4>
                                <small class="text-muted">Con envío pendiente</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search --}}
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.analytics.schedules.index') }}">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre o email..."
                                       value="{{ $search ?? '' }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search"></i>
                            </button>
                            @if($search)
                                <a href="{{ route('settings.analytics.schedules.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            @if($schedules->count() > 0)
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th>Nombre</th>
                                <th>Frecuencia</th>
                                <th>Email</th>
                                <th>Formato</th>
                                <th class="text-center">Estado</th>
                                <th>Ultimo envio</th>
                                <th>Próximo envio</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $schedule)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input schedule-checkbox" value="{{ $schedule->id }}">
                                    </td>
                                    <td class="fw-semibold">{{ $schedule->name }}</td>
                                    <td>
                                        @switch($schedule->frequency)
                                            @case('daily')
                                                <span class="badge bg-primary-subtle text-primary">Diario</span>
                                                @break
                                            @case('weekly')
                                                <span class="badge bg-success-subtle text-success">Semanal</span>
                                                @break
                                            @case('monthly')
                                                <span class="badge bg-warning-subtle text-warning">Mensual</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td><small class="text-muted">{{ $schedule->email }}</small></td>
                                    <td><small class="text-uppercase fw-semibold">{{ $schedule->format }}</small></td>
                                    <td class="text-center">
                                        @if($schedule->is_active)
                                            <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $schedule->last_sent_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                                    <td><small class="text-muted">{{ $schedule->next_run_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.analytics.schedules.edit', $schedule) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item toggle-btn"
                                                            data-id="{{ $schedule->id }}"
                                                            data-url="{{ route('settings.analytics.schedules.toggle', $schedule) }}"
                                                            data-active="{{ $schedule->is_active ? '1' : '0' }}">
                                                        {{ $schedule->is_active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item delete-btn"
                                                            data-url="{{ route('settings.analytics.schedules.destroy', $schedule) }}"
                                                            data-title="¿Eliminar el reporte {{ $schedule->name }}?">
                                                        Eliminar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-muted opacity-50"></i>
                    <h5 class="fw-bold mb-2">No hay reportes programados</h5>
                    <p class="text-muted mb-4">
                        @if($search)
                            No se encontraron resultados para la búsqueda.
                        @else
                            Crea el primer reporte para recibir datos automáticamente.
                        @endif
                    </p>
                    @if($search)
                        <a href="{{ route('settings.analytics.schedules.index') }}" class="btn btn-secondary">Ver todos</a>
                    @else
                        <a href="{{ route('settings.analytics.schedules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo reporte
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Pagination --}}
            @if($schedules->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $schedules->firstItem() }} - {{ $schedules->lastItem() }} de {{ $schedules->total() }} reportes
                        </div>
                        <div>
                            {{ $schedules->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span class="bulk-count-label">0</span> reporte(s)</strong> seleccionados.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
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

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ── Bulk actions ────────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.schedule-checkbox' });

    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un reporte.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' reporte(s)? Esta acción no se puede deshacer.')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.analytics.schedules.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' reporte(s) actualizados correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar la acción.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // ── Toggle individual ───────────────────────────────────────────────────
    $(document).on('click', '.toggle-btn', function () {
        const $btn = $(this);
        const url  = $btn.data('url');

        $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
            .done(function (res) {
                toastr.success('Reporte ' + (res.is_active ? 'activado' : 'desactivado') + ' correctamente.');
                setTimeout(() => location.reload(), 600);
            })
            .fail(function () {
                toastr.error('No se pudo actualizar el estado.');
            });
    });

    // ── Delete modal ────────────────────────────────────────────────────────
    $('.delete-btn').on('click', function () {
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });
});
</script>
@endpush
