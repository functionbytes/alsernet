@extends('layouts.theme')

@section('title', $pageTitle)

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/pricelabels/css/editor.css') }}">
@endpush

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">PDFs generados, con acceso a volver a descargarlos</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('pricelabels.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Plantillas
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Generaciones totales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Esta semana</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['this_week']) }}</h4>
                                <small class="text-muted">Desde el lunes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Vertical / Horizontal</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['vertical']) }} / {{ number_format($stats['horizontal']) }}</h4>
                                <small class="text-muted">Por formato</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Plantilla mas usada</h6>
                                <h4 class="mb-1 fw-bold text-truncate">{{ $stats['top_template'] ?? '—' }}</h4>
                                <small class="text-muted">{{ $stats['top_template_count'] }} generacion(es)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $activeFilters = collect([
                    request('template_id'), request('type'), request('date_from'), request('date_to'),
                ])->filter()->count();
            @endphp

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('pricelabels.history.index') }}" id="filterForm">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="flex-fill">
                            <span class="text-muted small">Usa el filtro para buscar por plantilla, tipo o rango de fechas.</span>
                        </div>
                        <button type="button" class="btn btn-outline-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-sliders me-1"></i> Filtros
                            @if($activeFilters > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    {{ $activeFilters }}
                                </span>
                            @endif
                        </button>
                        @if($activeFilters > 0)
                            <a href="{{ route('pricelabels.history.index') }}" class="btn btn-outline-secondary flex-shrink-0" title="Limpiar">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body">
                @if($generations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Plantilla</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Generado por</th>
                                    <th>Etiquetas</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($generations as $generation)
                                    <tr @if($generation->status === 'pending') data-generation-pending="{{ $generation->id }}" data-status-url="{{ route('pricelabels.history.status', $generation) }}" @endif>
                                        <td>
                                            <input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $generation->id }}">
                                        </td>
                                        <td>
                                            @if($generation->template)
                                                <a href="{{ route('pricelabels.edit', $generation->template) }}" class="small fw-semibold">
                                                    {{ $generation->template_name }}
                                                </a>
                                            @else
                                                <span class="small fw-semibold text-muted">{{ $generation->template_name }} (eliminada)</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ $generation->type === 'horizontal' ? 'Horizontal' : 'Vertical' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($generation->status === 'pending')
                                                <span class="badge pricelabels-status-badge status-pending">Procesando…</span>
                                            @elseif($generation->status === 'failed')
                                                <span class="badge pricelabels-status-badge status-failed pricelabels-error-badge" role="button"
                                                      data-error="{{ $generation->error_message }}">
                                                    <i class="fas fa-circle-info"></i> Fallo
                                                </span>
                                            @else
                                                <span class="badge pricelabels-status-badge status-completed">Completado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ $generation->generatedBy ? trim($generation->generatedBy->firstname.' '.$generation->generatedBy->lastname) : 'Sistema' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">{{ $generation->rows_count }}</div>
                                        </td>
                                        <td>
                                            <div class="small">{{ $generation->created_at->format('d/m/Y H:i') }}</div>
                                            <small class="text-muted">{{ $generation->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($generation->status === 'completed')
                                                        <li><a class="dropdown-item" href="{{ route('pricelabels.history.download', $generation) }}">Descargar</a></li>
                                                    @endif
                                                    @if($generation->source_excel_path)
                                                        <li>
                                                            <form action="{{ route('pricelabels.history.regenerate', $generation) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item">Regenerar</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-url="{{ route('pricelabels.history.destroy', $generation) }}"
                                                           data-title="Eliminar esta generacion">
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
                        <i class="fas fa-file-pdf fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">
                            @if($activeFilters > 0)
                                No se encontraron resultados
                            @else
                                Aun no hay generaciones
                            @endif
                        </h5>
                        <p class="text-muted mb-4">
                            @if($activeFilters > 0)
                                Prueba a cambiar o limpiar los filtros
                            @else
                                Los PDFs generados desde una plantilla apareceran aqui
                            @endif
                        </p>
                        @if($activeFilters > 0)
                            <a href="{{ route('pricelabels.history.index') }}" class="btn btn-secondary">Limpiar filtros</a>
                        @endif
                    </div>
                @endif
            </div>

            @if($generations->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $generations->firstItem() }} - {{ $generations->lastItem() }} de {{ $generations->total() }}
                        </div>
                        <div>
                            {{ $generations->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('pricelabels.history.index') }}" id="filterModalForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Filtros avanzados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Plantilla</label>
                            <select name="template_id" class="form-select select2" data-dropdown-parent="#filterModal">
                                <option value="">Todas</option>
                                @foreach($templates as $id => $name)
                                    <option value="{{ $id }}" {{ (string) request('template_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="type" class="form-select">
                                <option value="">Todos</option>
                                <option value="vertical" {{ request('type') === 'vertical' ? 'selected' : '' }}>Vertical</option>
                                <option value="horizontal" {{ request('type') === 'horizontal' ? 'selected' : '' }}>Horizontal</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rango de fechas</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                <span class="text-muted">—</span>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Aplicar filtros</button>
                        <a href="{{ route('pricelabels.history.index') }}" class="btn btn-secondary w-100">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Eliminar
        </button>
    </div>

    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar generaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-0">Se eliminaran <strong><span data-bulk-count>0</span> generacion(es)</strong> y sus PDFs asociados. Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer flex-column">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-2">Eliminar</button>
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
    $('.select2').select2({ width: '100%' });

    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-apply-btn').prop('disabled', false).text('Eliminar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const ids = bulk.getIds();

        if (!ids.length) { toastr.warning('Selecciona al menos una generacion.'); return; }
        if (!confirm('¿Eliminar las ' + ids.length + ' generacion(es)?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('pricelabels.history.bulk-action') }}',
            method: 'POST',
            data: JSON.stringify({ action: 'delete', ids: ids }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                $('#bulk-modal').modal('hide');
                toastr.success('Generaciones eliminadas correctamente.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Eliminar');
            },
        });
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
        $('#delete-modal').modal('show');
    });

    $(document).on('click', '.pricelabels-error-badge', function () {
        toastr.error($(this).data('error') || 'No hay detalle del error disponible.', 'Error al generar');
    });

    var $pending = $('tr[data-generation-pending]');
    if ($pending.length) {
        var pollInterval = setInterval(function () {
            var stillPending = false;

            $pending.each(function () {
                var $row = $(this);

                $.get($row.data('status-url'), function (res) {
                    if (res.status !== 'pending') {
                        location.reload();
                    }
                });

                stillPending = true;
            });

            if (!stillPending) {
                clearInterval(pollInterval);
            }
        }, 4000);
    }

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
